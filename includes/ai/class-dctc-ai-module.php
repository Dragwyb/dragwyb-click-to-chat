<?php
/**
 * DCTC AI Module bootstrap
 *
 * Isolated AI chatbot module. Does not touch the Channels
 * (multi-channel floating widget) settings or FAB. Opt-in via display.entire_site (default false).
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DCTC_AI_Module' ) ) :

	/**
	 * Core AI Module Singleton
	 */
	final class DCTC_AI_Module {


		/**
		 * Instance
		 *
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Cached hook suffix returned by add_submenu_page for AI Assistant.
		 *
		 * @var string|null
		 */
		private $admin_hook = null;

		/**
		 * Get Instance (Singleton)
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-settings-handler.php';
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-db.php';
			if ( file_exists( DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-manager.php' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-manager.php';
				DCTC_Support_Manager::get_instance();
			} elseif ( file_exists( DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php';
			}
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-site-analyzer.php';
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-memory-optimizer.php';
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';

			add_action(
				'plugins_loaded',
				function () {
					DCTC_AI_Abilities::init();
				}
			);

			// Initialize REST Handlers.
			new DCTC_AI_Settings_Handler();

			// Core Hooks.
			add_action( 'init', array( $this, 'dctc_ai_register_ai_client' ) );
			add_action( 'init', array( $this, 'dctc_ai_initialize_session_cookies' ) );
			add_action( 'init', array( $this, 'dctc_ai_init_rag_engine' ) );
			add_action( 'init', array( $this, 'dctc_ai_maybe_upgrade_schema' ) );
			add_action( 'admin_init', array( $this, 'dctc_ai_maybe_complete_wizard' ) );
			add_action( 'admin_init', array( $this, 'dctc_ai_maybe_activation_redirect' ) );
			add_action( 'admin_menu', array( $this, 'dctc_ai_add_admin_menu' ), 20 );
			add_action( 'admin_enqueue_scripts', array( $this, 'dctc_ai_enqueue_admin_assets' ) );
			add_action( 'wp_ajax_dctc_ai_dismiss_setup_notice', array( $this, 'dctc_ai_ajax_dismiss_setup_notice' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'dctc_ai_enqueue_frontend_assets' ) );
			add_action( 'wp_footer', array( $this, 'dctc_ai_render_global_chatbot' ) );
			add_shortcode( 'dctc_ai', array( $this, 'dctc_ai_shortcode_render' ) );

			$basename = defined( 'DCTC_BASENAME' ) ? DCTC_BASENAME : plugin_basename( DCTC_FILE );
			add_action( 'plugin_action_links_' . $basename, array( $this, 'dctc_ai_add_settings_link' ) );

			// Cache Invalidation & Knowledge Base Auto-Sync Hooks.
			add_action( 'save_post', array( $this, 'dctc_ai_handle_post_save' ), 20, 2 );
			add_action( 'before_delete_post', array( $this, 'dctc_ai_handle_post_delete' ), 20, 1 );
			add_action( 'wp_trash_post', array( $this, 'dctc_ai_handle_post_delete' ), 20, 1 );
			add_action( 'transition_post_status', array( $this, 'dctc_ai_handle_status_transition' ), 20, 3 );
			add_action( 'dctc_ai_async_index_post', array( $this, 'dctc_ai_process_async_index_post' ), 10, 1 );

			// Daily Maintenance & Retention Policy Cron.
			if ( ! wp_next_scheduled( 'dctc_ai_daily_cleanup_cron' ) ) {
				wp_schedule_event( time(), 'daily', 'dctc_ai_daily_cleanup_cron' );
			}
			add_action( 'dctc_ai_daily_cleanup_cron', array( $this, 'dctc_ai_run_daily_cleanups' ) );

			// Integrations & Helpers.
			add_action( 'init', array( $this, 'dctc_ai_init_integrations' ) );
			add_filter( 'http_request_timeout', array( $this, 'dctc_ai_increase_http_timeout' ), 9999, 2 );
		}

		/**
		 * Create AI tables and seed wizard status on plugin activation.
		 * Sets a one-shot redirect flag so the next admin load opens AI Assistant.
		 *
		 * @return void
		 */
		public static function activate() {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-db.php';
			DCTC_AI_DB::dctc_ai_create_tables();

			// For fresh new installs, enable the AI Chatbot by default without affecting existing users.
			$existing_ai_settings = get_option( 'dctc_ai_chat_assistant_settings', null );
			$is_existing_site     = get_option( 'dctc_settings' ) || get_option( 'dctc_ai_installed' );

			if ( null === $existing_ai_settings && ! $is_existing_site ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-settings-handler.php';
				$default_settings                           = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
				$default_settings['display']['entire_site'] = true;
				update_option( 'dctc_ai_chat_assistant_settings', $default_settings );
			}

			if ( ! get_option( 'dctc_ai_setup_wizard_status' ) ) {
				update_option( 'dctc_ai_setup_wizard_status', 'pending' );
			}

			update_option( 'dctc_ai_installed', '1' );
			update_option( 'dctc_ai_db_version', DCTC_VERSION );

			set_transient( 'dctc_ai_activation_redirect', 1, 60 );
		}

		/**
		 * After activation, send the activating admin to the AI Assistant screen.
		 *
		 * @return void
		 */
		public function dctc_ai_maybe_activation_redirect() {
			if ( ! get_transient( 'dctc_ai_activation_redirect' ) ) {
				return;
			}

			delete_transient( 'dctc_ai_activation_redirect' );

			if (
				! current_user_can( 'manage_options' ) ||
				wp_doing_ajax() ||
				( isset( $_GET['activate-multi'] ) && sanitize_text_field( wp_unslash( $_GET['activate-multi'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) {
				return;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) );
			exit;
		}

		/**
		 * Run dbDelta on admin if AI schema version is behind (plugin updates).
		 *
		 * @return void
		 */
		public function dctc_ai_maybe_upgrade_schema() {
			$installed = get_option( 'dctc_ai_db_version' );
			if ( $installed !== DCTC_VERSION ) {
				DCTC_AI_DB::dctc_ai_create_tables();
				update_option( 'dctc_ai_db_version', DCTC_VERSION );

				if ( ! get_option( 'dctc_ai_setup_wizard_status' ) ) {
					update_option( 'dctc_ai_setup_wizard_status', 'pending' );
				}
				update_option( 'dctc_ai_installed', '1' );
			}
		}

		/**
		 * Mark wizard completed when admin lands with valid nonce query args.
		 *
		 * @return void
		 */
		public function dctc_ai_maybe_complete_wizard() {
			$wizard_status_nonce           = isset( $_GET['dctc_ai_wizard_status_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['dctc_ai_wizard_status_nonce'] ) ) : '';
			$has_valid_wizard_status_nonce = wp_verify_nonce( $wizard_status_nonce, 'dctc_ai_wizard_status' );

			if ( $has_valid_wizard_status_nonce && current_user_can( 'manage_options' ) && isset( $_GET['page'] ) && 'dragwyb-click-to-chat-ai' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && isset( $_GET['dctc_ai_wizard_status'] ) && 'completed' === sanitize_text_field( wp_unslash( $_GET['dctc_ai_wizard_status'] ) ) ) {
				update_option( 'dctc_ai_setup_wizard_status', 'completed' );
			}
		}

		/**
		 * Increase HTTP Timeout for AI API Requests.
		 *
		 * @param int    $timeout Current timeout.
		 * @param string $url Request URL.
		 * @return int
		 */
		public function dctc_ai_increase_http_timeout( $timeout, $url ) {
			if (
				strpos( $url, 'generativelanguage.googleapis.com' ) !== false ||
				strpos( $url, 'api.openai.com' ) !== false ||
				strpos( $url, 'api.anthropic.com' ) !== false ||
				strpos( $url, 'openrouter.ai' ) !== false ||
				strpos( $url, 'api.groq.com' ) !== false ||
				strpos( $url, 'api.deepseek.com' ) !== false
			) {
				return 60;
			}
			return $timeout;
		}

		/**
		 * Enqueue the admin dashboard's script and styles.
		 *
		 * @param string $hook The current admin page's hook suffix.
		 * @return void
		 */
		public function dctc_ai_enqueue_admin_assets( $hook ) {
			$page       = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_ai_page = (
				( $this->admin_hook && $hook === $this->admin_hook ) ||
				'dragwyb-click-to-chat' === $page ||
				'dragwyb-click-to-chat-ai' === $page ||
				'toplevel_page_dragwyb-click-to-chat' === $hook ||
				'dragwyb-click-to-chat_page_dragwyb-click-to-chat-ai' === $hook
			);

			if ( ! $is_ai_page ) {
				return;
			}

			wp_enqueue_media();

			// Prefer webpack-emitted style-* CSS; fall back to legacy filename.
			$admin_css_candidates = array(
				'build/ai/admin/style-dctc-ai-dashboard.css',
				'build/ai/admin/dctc-ai-dashboard.css',
			);
			foreach ( $admin_css_candidates as $admin_css ) {
				if ( file_exists( DCTC_PLUGIN_DIR . $admin_css ) ) {
					wp_enqueue_style( 'dctc-ai-dashboard-style', DCTC_PLUGIN_URL . $admin_css, array(), DCTC_VERSION );
					break;
				}
			}

			$asset_file = file_exists( DCTC_PLUGIN_DIR . 'build/ai/admin/dctc-ai-dashboard.asset.php' ) ? require DCTC_PLUGIN_DIR . 'build/ai/admin/dctc-ai-dashboard.asset.php' : array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
				'version'      => DCTC_VERSION,
			);

			wp_enqueue_script( 'dctc-ai-dashboard-script', DCTC_PLUGIN_URL . 'build/ai/admin/dctc-ai-dashboard.js', $asset_file['dependencies'], $asset_file['version'], true );

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

			$providers   = DCTC_AI_Key_Store::get_supported_providers();
			$models_list = array();
			foreach ( $providers as $id ) {
				$key = DCTC_AI_Settings_Handler::dctc_ai_get_provider_key( $id );
				if ( ! empty( $key ) ) {
					if ( strlen( $key ) < 8 ) {
						$settings['api_keys'][ $id ] = '********';
					} else {
						$settings['api_keys'][ $id ] = substr( $key, 0, 4 ) . '...' . substr( $key, -4 );
					}
				}
				$models_list[ $id ] = DCTC_AI_Settings_Handler::dctc_ai_get_models( $id );
			}

			// Never expose the full Pinecone API key in page HTML / JS.
			if ( ! empty( $settings['rag']['vector_db']['api_key'] ) && is_string( $settings['rag']['vector_db']['api_key'] ) ) {
				$pinecone_key = $settings['rag']['vector_db']['api_key'];
				if ( strlen( $pinecone_key ) < 8 ) {
					$settings['rag']['vector_db']['api_key'] = '********';
				} else {
					$settings['rag']['vector_db']['api_key'] = substr( $pinecone_key, 0, 4 ) . '...' . substr( $pinecone_key, -4 );
				}
			}

			wp_localize_script(
				'dctc-ai-dashboard-script',
				'dctc_ai_data',
				array(
					'rest_url'          => esc_url_raw( rest_url( 'dctc-ai/v1/' ) ),
					'nonce'             => wp_create_nonce( 'wp_rest' ),
					'settings'          => $settings,
					'models_list'       => $models_list,
					'load_limit'        => get_user_meta( get_current_user_id(), 'dctc_ai_sessions_load_limit', true ) ?: '100',
					'sort_order'        => get_user_meta( get_current_user_id(), 'dctc_ai_sessions_sort_order', true ) ?: 'desc',
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'show_setup_wizard' => get_option( 'dctc_ai_setup_wizard_status' ) === 'pending' || ( isset( $_GET['dctc_ai_open_wizard'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['dctc_ai_open_wizard'] ) ) ),
					'is_support_enabled' => class_exists( 'DCTC_Support_Manager' ) && ! empty( get_option( 'dctc_support_settings', array() )['enabled'] ),
				)
			);
		}

		/**
		 * AJAX callback to dismiss the setup wizard notice
		 *
		 * @return void
		 */
		public function dctc_ai_ajax_dismiss_setup_notice() {
			check_ajax_referer( 'dctc_ai_dismiss_notice_nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					esc_html__( 'You do not have permission to do this.', 'dragwyb-click-to-chat' ),
					403
				);
			}

			update_option( 'dctc_ai_setup_wizard_status', 'completed' );
			wp_send_json_success();
		}

		/**
		 * Enqueue the frontend chat widget's script and styles when needed.
		 *
		 * @return void
		 */
		public function dctc_ai_enqueue_frontend_assets() {
			if ( ! $this->dctc_ai_should_load_frontend_assets() ) {
				return;
			}

			$this->dctc_ai_do_enqueue_frontend_assets();
		}

		/**
		 * Whether AI frontend assets should load on this request.
		 *
		 * @return bool
		 */
		private function dctc_ai_should_load_frontend_assets() {
			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			if ( ! empty( $settings['display']['entire_site'] ) ) {
				return true;
			}

			global $post;
			if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'dctc_ai' ) ) {
				return true;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Elementor preview flag; no state change.
			if ( isset( $_GET['elementor-preview'] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Register and localize AI frontend assets (idempotent).
		 *
		 * @return void
		 */
		public function dctc_ai_do_enqueue_frontend_assets() {
			if ( wp_script_is( 'dctc-ai-frontend-script', 'enqueued' ) ) {
				return;
			}

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_public_frontend_settings();

			$frontend_css = file_exists( DCTC_PLUGIN_DIR . 'build/ai/frontend/style-dctc-ai-frontend.css' )
				? 'build/ai/frontend/style-dctc-ai-frontend.css'
				: 'build/ai/frontend/dctc-ai-frontend.css';
			if ( file_exists( DCTC_PLUGIN_DIR . $frontend_css ) ) {
				wp_enqueue_style( 'dctc-ai-frontend-style', DCTC_PLUGIN_URL . $frontend_css, array( 'dashicons' ), DCTC_VERSION );
			}

			$asset_file = file_exists( DCTC_PLUGIN_DIR . 'build/ai/frontend/dctc-ai-frontend.asset.php' ) ? require DCTC_PLUGIN_DIR . 'build/ai/frontend/dctc-ai-frontend.asset.php' : array(
				'dependencies' => array( 'wp-element' ),
				'version'      => DCTC_VERSION,
			);
			wp_enqueue_script( 'dctc-ai-frontend-script', DCTC_PLUGIN_URL . 'build/ai/frontend/dctc-ai-frontend.js', $asset_file['dependencies'], $asset_file['version'], true );

			$session_id = isset( $_COOKIE['dctc_ai_session_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dctc_ai_session_id'] ) ) : '';
			$is_allowed = isset( $_COOKIE['dctc_ai_clear_allowed'] );

			$page_context = array(
				'url'           => esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ),
				'title'         => function_exists( 'wp_get_document_title' ) ? wp_get_document_title() : get_bloginfo( 'name' ),
				'post_id'       => get_queried_object_id(),
				'post_type'     => is_singular() ? get_post_type() : ( is_front_page() ? 'home' : ( is_archive() ? 'archive' : 'page' ) ),
				'is_front_page' => is_front_page(),
				'is_single'     => is_singular(),
				'is_logged_in'  => is_user_logged_in(),
			);

			if ( function_exists( 'is_product' ) && is_product() && function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( get_queried_object_id() );
				if ( $product ) {
					$page_context['product'] = array(
						'id'       => $product->get_id(),
						'name'     => $product->get_name(),
						'price'    => $product->get_price(),
						'in_stock' => $product->is_in_stock(),
						'sku'      => $product->get_sku(),
						'currency' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
					);
				}
			}

			wp_localize_script(
				'dctc-ai-frontend-script',
				'dctc_ai_frontend_data',
				array(
					'rest_url'      => esc_url_raw( rest_url( 'dctc-ai/v1/' ) ),
					'nonce'         => wp_create_nonce( 'wp_rest' ),
					'settings'      => $settings,
					'session_id'    => $session_id,
					'clear_allowed' => $is_allowed,
					'page_context'  => $page_context,
				)
			);
		}

		/**
		 * Initialize session cookies for AI chat guests.
		 *
		 * @return void
		 */
		public function dctc_ai_initialize_session_cookies() {
			if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
				return;
			}

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			if ( empty( $settings['display']['entire_site'] ) ) {
				// Still set cookies if shortcode may be used — cheap and needed for chat auth.
			}

			$session_id = isset( $_COOKIE['dctc_ai_session_id'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['dctc_ai_session_id'] ) ) : '';

			if ( empty( $session_id ) ) {
				$session_id = 'sess_' . wp_generate_password( 9, false );

				setcookie( 'dctc_ai_session_id', $session_id, time() + 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
				setcookie( 'dctc_ai_clear_allowed', 'true', time() + 1800, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );

				$_COOKIE['dctc_ai_session_id'] = $session_id;
			}
		}

		/**
		 * Print the chatbot into wp_footer when global display is enabled.
		 *
		 * @return void
		 */
		public function dctc_ai_render_global_chatbot() {
			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

			if ( empty( $settings['display']['entire_site'] ) ) {
				return;
			}

			if ( ! empty( $settings['display']['exclude_pages'] ) && is_singular() ) {
				$excluded_ids = array_filter( array_map( 'trim', explode( ',', $settings['display']['exclude_pages'] ) ) );
				if ( in_array( (string) get_queried_object_id(), $excluded_ids, true ) ) {
					return;
				}
			}

			// Device targeting
			$target_devices = ! empty( $settings['display']['target_devices'] ) ? $settings['display']['target_devices'] : 'all';
			if ( 'desktop_only' === $target_devices && wp_is_mobile() ) {
				return;
			}
			if ( 'mobile_only' === $target_devices && ! wp_is_mobile() ) {
				return;
			}
			if ( empty( $settings['display']['show_on_mobile'] ) && wp_is_mobile() ) {
				return;
			}

			// User targeting
			$target_users = ! empty( $settings['display']['target_users'] ) ? $settings['display']['target_users'] : 'all';
			if ( 'logged_in' === $target_users && ! is_user_logged_in() ) {
				return;
			}
			if ( 'guests' === $target_users && is_user_logged_in() ) {
				return;
			}

			// URL path rules
			if ( ! empty( $settings['display']['url_rules'] ) ) {
				$current_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
				$rules       = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $settings['display']['url_rules'] ) ) ) );
				$matched     = false;
				foreach ( $rules as $rule ) {
					if ( false !== strpos( $current_uri, $rule ) || fnmatch( $rule, $current_uri ) ) {
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					return;
				}
			}

			$this->dctc_ai_render_chatbot_ui();
		}

		/**
		 * Shortcode callback that renders the chatbot inline.
		 *
		 * @param array $atts Shortcode attributes (currently unused).
		 * @return string Chatbot root container markup.
		 */
		public function dctc_ai_shortcode_render( $atts ) {
			$this->dctc_ai_do_enqueue_frontend_assets();
			ob_start();
			$this->dctc_ai_render_chatbot_ui( true );
			return ob_get_clean();
		}

		/**
		 * Load optional third-party integrations (e.g. Elementor) if active.
		 *
		 * @return void
		 */
		public function dctc_ai_init_integrations() {
			if ( did_action( 'elementor/loaded' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/integrations/class-dctc-ai-elementor.php';
			}
		}

		/**
		 * Initialize RAG Engine if available.
		 *
		 * @return void
		 */
		public function dctc_ai_init_rag_engine() {
			if ( ! file_exists( DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php' ) ) {
				return;
			}

			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php';
			$rag_engine = DCTC_AI_RAG_Engine::get_instance();
			$rag_engine->init();
		}

		/**
		 * Print the empty root element the frontend script mounts into.
		 *
		 * @param bool $inline Whether to render the inline (shortcode) variant.
		 * @return void
		 */
		public function dctc_ai_render_chatbot_ui( $inline = false ) {
			$wrapper_class = $inline ? 'dctc-ai-chat-root-inline' : 'dctc-ai-chat-root-floating';
			?>
			<div id="dctc-ai-frontend-root" class="<?php echo esc_attr( $wrapper_class ); ?>"
				data-inline="<?php echo esc_attr( wp_json_encode( $inline ) ); ?>"></div>
			<?php
		}

		/**
		 * Register AI Assistant as a submenu under Click to Chat.
		 *
		 * @return void
		 */
		public function dctc_ai_add_admin_menu() {
			// Submenu structure is registered centrally in admin/settings.php.
		}

		/**
		 * Render the AI admin dashboard page.
		 *
		 * @return void
		 */
		public function dctc_ai_render_admin_page() {
			require_once DCTC_PLUGIN_DIR . 'admin/ai/dctc-ai-dashboard.php';
		}

		/**
		 * Add an "AI Assistant" link on the Plugins list page.
		 *
		 * @param array $links Existing plugin action links.
		 * @return array
		 */
		public function dctc_ai_add_settings_link( $links ) {
			$ai_link = '<a href="' . esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ) . '">' . esc_html__( 'AI Assistant', 'dragwyb-click-to-chat' ) . '</a>';
			$links[] = $ai_link;
			return $links;
		}

		/**
		 * Load the AI client SDK and register the OpenAI and Google providers.
		 *
		 * @return void
		 */
		public function dctc_ai_register_ai_client() {
			$is_wp_ai_client_70 = function_exists( 'wp_ai_client_prompt' );

			if ( ! $is_wp_ai_client_70 ) {
				// Skip if another plugin already loaded the AI Client SDK.
				if ( ! class_exists( '\WordPress\AI_Client\AI_Client', false ) && ! class_exists( '\WordPress\AiClient\AiClient', false ) ) {
					$sdk_autoload = DCTC_PLUGIN_DIR . 'vendor/wordpress/wp-ai-client/autoload.php';

					if ( file_exists( $sdk_autoload ) ) {
						require_once $sdk_autoload;
					}
				}

				if ( ! class_exists( '\WordPress\AI_Client\AI_Client' ) && ! class_exists( '\WordPress\AiClient\AiClient' ) ) {
					return;
				}
			}

			// Skip provider autoload if OpenAI/Google providers already exist (avoids
			// ComposerAutoloaderInit collisions when another AI plugin is also active).
			if (
				! class_exists( '\WordPress\OpenAiAiProvider\Provider\OpenAiProvider', false ) ||
				! class_exists( '\WordPress\GoogleAiProvider\Provider\GoogleProvider', false )
			) {
				$providers_autoload = DCTC_PLUGIN_DIR . 'includes/ai/ai-providers/vendor/autoload.php';
				if ( file_exists( $providers_autoload ) ) {
					require_once $providers_autoload;
				}
			}

			if ( ! class_exists( '\WordPress\AiClient\AiClient' ) ) {
				return;
			}

			$registry = \WordPress\AiClient\AiClient::defaultRegistry();

			$this->dctc_ai_register_ai_provider(
				$registry,
				'openai',
				'\WordPress\OpenAiAiProvider\Provider\OpenAiProvider'
			);

			$this->dctc_ai_register_ai_provider(
				$registry,
				'google',
				'\WordPress\GoogleAiProvider\Provider\GoogleProvider'
			);

			$this->dctc_ai_migrate_to_unified_settings();

			if ( ! $is_wp_ai_client_70 ) {
				\WordPress\AI_Client\AI_Client::init();

				try {
					$http_transporter = \WordPress\AiClient\Providers\Http\HttpTransporterFactory::createTransporter();
					$registry->setHttpTransporter( $http_transporter );
				} catch ( \Exception $e ) {
					// Silent failover.
				}
			}
		}

		/**
		 * Register a single AI provider class with the registry.
		 *
		 * @param object $registry AI client provider registry.
		 * @param string $name Provider identifier.
		 * @param string $class Fully-qualified provider class name.
		 * @return void
		 */
		private function dctc_ai_register_ai_provider( $registry, $name, $class ) {
			if ( class_exists( $class ) && ! $registry->hasProvider( $name ) ) {
				$registry->registerProvider( $class );
			}
		}

		/**
		 * One-time migration of legacy AI option names into unified settings.
		 * Fresh installs for this module — still keep the guard for parity.
		 *
		 * @return void
		 */
		private function dctc_ai_migrate_to_unified_settings() {
			if ( get_option( 'dctc_ai_chat_assistant_settings_migrated' ) ) {
				return;
			}

			$is_wp_ai_client_70 = function_exists( 'wp_ai_client_prompt' );
			$settings           = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			$migrated           = false;

			$providers = array( 'openai', 'google' );

			foreach ( $providers as $provider ) {
				$key = '';
				if ( $is_wp_ai_client_70 ) {
					$key = get_option( 'connectors_ai_' . $provider . '_api_key' );
				} else {
					$creds = get_option( 'wp_ai_client_provider_credentials', array() );
					$key   = isset( $creds[ $provider ] ) ? $creds[ $provider ] : '';
				}

				if ( ! empty( $key ) && empty( $settings['api_keys'][ $provider ] ) ) {
					$settings['api_keys'][ $provider ] = sanitize_text_field( $key );
					$migrated                          = true;
				}
			}

			$old_bot_settings = get_option( 'dctc_ai_chatbot_settings' );
			if ( ! empty( $old_bot_settings ) && is_array( $old_bot_settings ) ) {
				$settings['chatbot'] = wp_parse_args( $old_bot_settings, $settings['chatbot'] );
				$migrated            = true;
			}

			$old_models = get_option( 'dctc_ai_ai_selected_models' );
			if ( ! empty( $old_models ) && is_array( $old_models ) ) {
				$settings['models'] = wp_parse_args( $old_models, $settings['models'] );
				$migrated           = true;
			}

			if ( $migrated ) {
				DCTC_AI_Settings_Handler::dctc_ai_persist_settings( $settings );
			}

			update_option( 'dctc_ai_chat_assistant_settings_migrated', true );
		}

		/**
		 * Invalidate MCP site context cache when posts are saved or deleted.
		 *
		 * @return void
		 */
		public function dctc_ai_invalidate_mcp_cache() {
			wp_cache_delete( 'dctc_ai_site_context', 'dctc_ai_mcp' );
		}

		/**
		 * Handle post save: schedule async indexing if published and configured, or purge if unpublished.
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 * @return void
		 */
		public function dctc_ai_handle_post_save( $post_id, $post ) {
			$this->dctc_ai_invalidate_mcp_cache();

			if ( ! $post instanceof WP_Post ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
				return;
			}

			// Verify user capability if in admin context.
			if ( is_admin() && ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			if ( empty( $settings['rag'] ) || empty( $settings['rag']['enabled'] ) ) {
				return;
			}

			$auto_update = isset( $settings['rag']['indexing']['auto_update'] )
				? (bool) $settings['rag']['indexing']['auto_update']
				: ( isset( $settings['rag']['auto_update'] ) ? (bool) $settings['rag']['auto_update'] : true );

			if ( ! $auto_update ) {
				return;
			}

			$configured_types = ! empty( $settings['rag']['post_types'] ) && is_array( $settings['rag']['post_types'] )
				? $settings['rag']['post_types']
				: array( 'post', 'page' );

			if ( ! in_array( $post->post_type, $configured_types, true ) ) {
				return;
			}

			if ( 'publish' === $post->post_status ) {
				if ( ! wp_next_scheduled( 'dctc_ai_async_index_post', array( $post_id ) ) ) {
					wp_schedule_single_event( time(), 'dctc_ai_async_index_post', array( $post_id ) );
				}
			} else {
				$this->dctc_ai_handle_post_delete( $post_id );
			}
		}

		/**
		 * Handle post status transition (e.g. from publish to trash/draft).
		 *
		 * @param string  $new_status New status.
		 * @param string  $old_status Old status.
		 * @param WP_Post $post       Post object.
		 * @return void
		 */
		public function dctc_ai_handle_status_transition( $new_status, $old_status, $post ) {
			if ( 'publish' === $old_status && 'publish' !== $new_status && $post instanceof WP_Post ) {
				$this->dctc_ai_handle_post_delete( $post->ID );
			}
		}

		/**
		 * Purge document chunks and vectors when post is trashed or deleted.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public function dctc_ai_handle_post_delete( $post_id ) {
			$this->dctc_ai_invalidate_mcp_cache();

			if ( ! class_exists( 'DCTC_AI_Indexer' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-indexer.php';
			}

			try {
				$indexer = new DCTC_AI_Indexer();
				$indexer->remove_document( 'post_' . absint( $post_id ) );
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Dragwyb AI Auto-Sync Delete Error: ' . $e->getMessage() );
				}
			}
		}

		/**
		 * Process background async indexing of a post via cron.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public function dctc_ai_process_async_index_post( $post_id ) {
			$post = get_post( absint( $post_id ) );
			if ( ! $post || 'publish' !== $post->post_status ) {
				return;
			}

			$settings         = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			$configured_types = ! empty( $settings['rag']['post_types'] ) && is_array( $settings['rag']['post_types'] )
				? $settings['rag']['post_types']
				: array( 'post', 'page' );

			if ( ! in_array( $post->post_type, $configured_types, true ) ) {
				return;
			}

			if ( ! class_exists( 'DCTC_AI_Indexer' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-indexer.php';
			}

			try {
				$indexer = new DCTC_AI_Indexer();
				$indexer->index_post( $post );
				$this->dctc_ai_invalidate_mcp_cache();
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Dragwyb AI Auto-Sync Async Index Error: ' . $e->getMessage() );
				}
			}
		}

		/**
		 * Execute daily retention policy cleanups for chat sessions and error logs.
		 *
		 * @return void
		 */
		public function dctc_ai_run_daily_cleanups() {
			$settings            = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			$chat_retention_days = isset( $settings['chatbot']['chat_retention_days'] ) ? absint( $settings['chatbot']['chat_retention_days'] ) : 0;

			if ( $chat_retention_days > 0 && class_exists( 'DCTC_AI_DB' ) ) {
				try {
					DCTC_AI_DB::dctc_ai_clean_old_sessions( $chat_retention_days );
				} catch ( Exception $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Dragwyb AI Daily Retention Cleanup Error: ' . $e->getMessage() );
					}
				}
			}

			if ( class_exists( 'DCTC_Error_Logger' ) ) {
				try {
					DCTC_Error_Logger::clean_old_logs();
				} catch ( Exception $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Dragwyb AI Daily Error Log Cleanup Error: ' . $e->getMessage() );
					}
				}
			}

			// Feature 14: Clean temporary chat attachments older than 24h
			try {
				global $wpdb;
				$cutoff = time() - DAY_IN_SECONDS;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$temp_attachments = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_dctc_ai_temporary' AND meta_value < %d LIMIT 50",
						$cutoff
					)
				);
				if ( ! empty( $temp_attachments ) ) {
					foreach ( $temp_attachments as $att_id ) {
						wp_delete_attachment( (int) $att_id, true );
					}
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Dragwyb AI Temp Attachment Cleanup Error: ' . $e->getMessage() );
				}
			}
		}
	}

endif;

