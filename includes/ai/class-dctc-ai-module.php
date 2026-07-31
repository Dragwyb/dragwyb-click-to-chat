<?php
/**
 * DCTC AI Module bootstrap
 *
 * Isolated AI chatbot module. Does not touch the Channels
 * (multi-channel floating widget) settings or FAB. Opt-in via display.entire_site (default false).
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('DCTC_AI_Module')):

	/**
	 * Core AI Module Singleton
	 */
	final class DCTC_AI_Module
	{

		/**
		 * Instance
		 *
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Cached hook suffix returned by add_submenu_page.
		 *
		 * @var string|null
		 */
		private $admin_hook = null;

		/**
		 * Get Instance (Singleton)
		 *
		 * @return self
		 */
		public static function get_instance()
		{
			if (null === self::$instance) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct()
		{
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-settings-handler.php';
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-db.php';
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-site-analyzer.php';
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-memory-optimizer.php';

			// Initialize REST Handlers.
			new DCTC_AI_Settings_Handler();

			// Core Hooks.
			add_action('init', [$this, 'dctc_ai_register_ai_client']);
			add_action('init', [$this, 'dctc_ai_initialize_session_cookies']);
			add_action('init', [$this, 'dctc_ai_init_rag_engine']);
			add_action('admin_init', [$this, 'dctc_ai_maybe_complete_wizard']);
			add_action('admin_init', [$this, 'dctc_ai_maybe_upgrade_schema']);
			add_action('admin_init', [$this, 'dctc_ai_maybe_activation_redirect']);
			add_action('admin_menu', [$this, 'dctc_ai_add_admin_menu'], 20);
			add_action('admin_enqueue_scripts', [$this, 'dctc_ai_enqueue_admin_assets']);
			add_action('wp_ajax_dctc_ai_dismiss_setup_notice', [$this, 'dctc_ai_ajax_dismiss_setup_notice']);
			add_action('wp_enqueue_scripts', [$this, 'dctc_ai_enqueue_frontend_assets']);
			add_action('wp_footer', [$this, 'dctc_ai_render_global_chatbot']);
			add_shortcode('dctc_ai', [$this, 'dctc_ai_shortcode_render']);

			$basename = defined('DCTC_BASENAME') ? DCTC_BASENAME : plugin_basename(DCTC_FILE);
			add_action('plugin_action_links_' . $basename, [$this, 'dctc_ai_add_settings_link']);

			// Cache Invalidation Hooks.
			add_action('save_post', [$this, 'dctc_ai_invalidate_mcp_cache']);
			add_action('delete_post', [$this, 'dctc_ai_invalidate_mcp_cache']);

			// Integrations & Helpers.
			add_action('init', [$this, 'dctc_ai_init_integrations']);
			add_filter('http_request_timeout', [$this, 'dctc_ai_increase_http_timeout'], 9999, 2);
		}

		/**
		 * Create AI tables and seed wizard status on plugin activation.
		 * Sets a one-shot redirect flag so the next admin load opens AI Assistant.
		 *
		 * @return void
		 */
		public static function activate()
		{
			require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-db.php';
			DCTC_AI_DB::dctc_ai_create_tables();

			if (!get_option('dctc_ai_setup_wizard_status')) {
				update_option('dctc_ai_setup_wizard_status', 'pending');
			}

			update_option('dctc_ai_installed', '1');
			update_option('dctc_ai_db_version', DCTC_VERSION);

			set_transient('dctc_ai_activation_redirect', 1, 60);
		}

		/**
		 * After activation, send the activating admin to the AI Assistant screen.
		 *
		 * @return void
		 */
		public function dctc_ai_maybe_activation_redirect()
		{
			if (!get_transient('dctc_ai_activation_redirect')) {
				return;
			}

			delete_transient('dctc_ai_activation_redirect');

			if (
				!current_user_can('manage_options') ||
				wp_doing_ajax() ||
				(isset($_GET['activate-multi']) && sanitize_text_field(wp_unslash($_GET['activate-multi']))) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) {
				return;
			}

			wp_safe_redirect(admin_url('admin.php?page=dragwyb-click-to-chat-ai'));
			exit;
		}

		/**
		 * Run dbDelta on admin if AI schema version is behind (plugin updates).
		 *
		 * @return void
		 */
		public function dctc_ai_maybe_upgrade_schema()
		{
			$installed = get_option('dctc_ai_db_version');
			if ($installed === DCTC_VERSION) {
				return;
			}

			DCTC_AI_DB::dctc_ai_create_tables();
			update_option('dctc_ai_db_version', DCTC_VERSION);

			if (!get_option('dctc_ai_setup_wizard_status')) {
				update_option('dctc_ai_setup_wizard_status', 'pending');
			}
			update_option('dctc_ai_installed', '1');
		}

		/**
		 * Mark wizard completed when admin lands with valid nonce query args.
		 *
		 * @return void
		 */
		public function dctc_ai_maybe_complete_wizard()
		{
			$wizard_status_nonce = isset($_GET['dctc_ai_wizard_status_nonce']) ? sanitize_text_field(wp_unslash($_GET['dctc_ai_wizard_status_nonce'])) : '';
			$has_valid_wizard_status_nonce = wp_verify_nonce($wizard_status_nonce, 'dctc_ai_wizard_status');

			if ($has_valid_wizard_status_nonce && current_user_can('manage_options') && isset($_GET['page']) && 'dragwyb-click-to-chat-ai' === sanitize_text_field(wp_unslash($_GET['page'])) && isset($_GET['dctc_ai_wizard_status']) && 'completed' === sanitize_text_field(wp_unslash($_GET['dctc_ai_wizard_status']))) {
				update_option('dctc_ai_setup_wizard_status', 'completed');
			}
		}

		/**
		 * Increase HTTP Timeout for AI API Requests.
		 *
		 * @param int    $timeout Current timeout.
		 * @param string $url Request URL.
		 * @return int
		 */
		public function dctc_ai_increase_http_timeout($timeout, $url)
		{
			if (
				strpos($url, 'generativelanguage.googleapis.com') !== false ||
				strpos($url, 'api.openai.com') !== false
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
		public function dctc_ai_enqueue_admin_assets($hook)
		{
			$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_ai_page = (
				($this->admin_hook && $hook === $this->admin_hook) ||
				'dragwyb-click-to-chat-ai' === $page ||
				'dragwyb-click-to-chat_page_dragwyb-click-to-chat-ai' === $hook
			);

			if (!$is_ai_page) {
				return;
			}

			wp_enqueue_media();

			// Admin dashboard CSS (full stylesheet).
			$admin_css_candidates = [
				'build/ai/admin/dctc-ai-dashboard.css',
				'build/ai/admin/style-dctc-ai-dashboard.css',
			];
			foreach ($admin_css_candidates as $admin_css) {
				if (file_exists(DCTC_PLUGIN_DIR . $admin_css)) {
					wp_enqueue_style('dctc-ai-dashboard-style', DCTC_PLUGIN_URL . $admin_css, [], DCTC_VERSION);
					break;
				}
			}

			$asset_file = file_exists(DCTC_PLUGIN_DIR . 'build/ai/admin/dctc-ai-dashboard.asset.php') ? require DCTC_PLUGIN_DIR . 'build/ai/admin/dctc-ai-dashboard.asset.php' : ['dependencies' => ['wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch'], 'version' => DCTC_VERSION];

			wp_enqueue_script('dctc-ai-dashboard-script', DCTC_PLUGIN_URL . 'build/ai/admin/dctc-ai-dashboard.js', $asset_file['dependencies'], $asset_file['version'], true);

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

			$providers = ['openai', 'google'];
			$models_list = [];
			foreach ($providers as $id) {
				$key = DCTC_AI_Settings_Handler::dctc_ai_get_provider_key($id);
				if (!empty($key)) {
					if (strlen($key) < 8) {
						$settings['api_keys'][$id] = '********';
					} else {
						$settings['api_keys'][$id] = substr($key, 0, 4) . '...' . substr($key, -4);
					}
				}
				$models_list[$id] = DCTC_AI_Settings_Handler::dctc_ai_get_models($id);
			}

			wp_localize_script(
				'dctc-ai-dashboard-script',
				'dctc_ai_data',
				[
					'rest_url' => esc_url_raw(rest_url('dctc-ai/v1/')),
					'nonce' => wp_create_nonce('wp_rest'),
					'settings' => $settings,
					'models_list' => $models_list,
					'load_limit' => get_user_meta(get_current_user_id(), 'dctc_ai_sessions_load_limit', true) ?: '100',
					'sort_order' => get_user_meta(get_current_user_id(), 'dctc_ai_sessions_sort_order', true) ?: 'desc',
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'show_setup_wizard' => get_option('dctc_ai_setup_wizard_status') === 'pending' || (isset($_GET['dctc_ai_open_wizard']) && 'true' === sanitize_text_field(wp_unslash($_GET['dctc_ai_open_wizard']))),
				]
			);
		}

		/**
		 * AJAX callback to dismiss the setup wizard notice
		 *
		 * @return void
		 */
		public function dctc_ai_ajax_dismiss_setup_notice()
		{
			check_ajax_referer('dctc_ai_dismiss_notice_nonce');

			if (!current_user_can('manage_options')) {
				wp_send_json_error(
					esc_html__('You do not have permission to do this.', 'dragwyb-click-to-chat'),
					403
				);
			}

			update_option('dctc_ai_setup_wizard_status', 'completed');
			wp_send_json_success();
		}

		/**
		 * Enqueue the frontend chat widget's script and styles when needed.
		 *
		 * @return void
		 */
		public function dctc_ai_enqueue_frontend_assets()
		{
			if (!$this->dctc_ai_should_load_frontend_assets()) {
				return;
			}

			$this->dctc_ai_do_enqueue_frontend_assets();
		}

		/**
		 * Whether AI frontend assets should load on this request.
		 *
		 * @return bool
		 */
		private function dctc_ai_should_load_frontend_assets()
		{
			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			if (!empty($settings['display']['entire_site'])) {
				return true;
			}

			global $post;
			if ($post instanceof WP_Post && has_shortcode($post->post_content, 'dctc_ai')) {
				return true;
			}

			if (isset($_GET['elementor-preview'])) {
				return true;
			}

			return false;
		}

		/**
		 * Register and localize AI frontend assets (idempotent).
		 *
		 * @return void
		 */
		public function dctc_ai_do_enqueue_frontend_assets()
		{
			if (wp_script_is('dctc-ai-frontend-script', 'enqueued')) {
				return;
			}

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

			$frontend_css = file_exists(DCTC_PLUGIN_DIR . 'build/ai/frontend/style-dctc-ai-frontend.css')
				? 'build/ai/frontend/style-dctc-ai-frontend.css'
				: 'build/ai/frontend/dctc-ai-frontend.css';
			if (file_exists(DCTC_PLUGIN_DIR . $frontend_css)) {
				wp_enqueue_style('dctc-ai-frontend-style', DCTC_PLUGIN_URL . $frontend_css, ['dashicons'], DCTC_VERSION);
			}

			$asset_file = file_exists(DCTC_PLUGIN_DIR . 'build/ai/frontend/dctc-ai-frontend.asset.php') ? require DCTC_PLUGIN_DIR . 'build/ai/frontend/dctc-ai-frontend.asset.php' : ['dependencies' => ['wp-element'], 'version' => DCTC_VERSION];
			wp_enqueue_script('dctc-ai-frontend-script', DCTC_PLUGIN_URL . 'build/ai/frontend/dctc-ai-frontend.js', $asset_file['dependencies'], $asset_file['version'], true);

			$session_id = isset($_COOKIE['dctc_ai_session_id']) ? sanitize_text_field(wp_unslash($_COOKIE['dctc_ai_session_id'])) : '';
			$is_allowed = isset($_COOKIE['dctc_ai_clear_allowed']);

			wp_localize_script(
				'dctc-ai-frontend-script',
				'dctc_ai_frontend_data',
				[
					'rest_url' => esc_url_raw(rest_url('dctc-ai/v1/')),
					'nonce' => wp_create_nonce('wp_rest'),
					'settings' => $settings,
					'session_id' => $session_id,
					'clear_allowed' => $is_allowed,
				]
			);
		}

		/**
		 * Initialize session cookies for AI chat guests.
		 *
		 * @return void
		 */
		public function dctc_ai_initialize_session_cookies()
		{
			if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
				return;
			}

			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			if (empty($settings['display']['entire_site'])) {
				// Still set cookies if shortcode may be used — cheap and needed for chat auth.
			}

			$session_id = isset($_COOKIE['dctc_ai_session_id']) ? sanitize_text_field(wp_unslash($_COOKIE['dctc_ai_session_id'])) : '';

			if (empty($session_id)) {
				$session_id = 'sess_' . wp_generate_password(9, false);

				setcookie('dctc_ai_session_id', $session_id, time() + 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false);
				setcookie('dctc_ai_clear_allowed', 'true', time() + 1800, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false);

				$_COOKIE['dctc_ai_session_id'] = $session_id;
			}
		}

		/**
		 * Print the chatbot into wp_footer when global display is enabled.
		 *
		 * @return void
		 */
		public function dctc_ai_render_global_chatbot()
		{
			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

			if (empty($settings['display']['entire_site'])) {
				return;
			}

			if (!empty($settings['display']['exclude_pages']) && is_singular()) {
				$excluded_ids = array_filter(array_map('trim', explode(',', $settings['display']['exclude_pages'])));
				if (in_array((string) get_queried_object_id(), $excluded_ids, true)) {
					return;
				}
			}

			if (empty($settings['display']['show_on_mobile']) && wp_is_mobile()) {
				return;
			}

			$this->dctc_ai_render_chatbot_ui();
		}

		/**
		 * Shortcode callback that renders the chatbot inline.
		 *
		 * @param array $atts Shortcode attributes (currently unused).
		 * @return string Chatbot root container markup.
		 */
		public function dctc_ai_shortcode_render($atts)
		{
			$this->dctc_ai_do_enqueue_frontend_assets();
			ob_start();
			$this->dctc_ai_render_chatbot_ui(true);
			return ob_get_clean();
		}

		/**
		 * Load optional third-party integrations (e.g. Elementor) if active.
		 *
		 * @return void
		 */
		public function dctc_ai_init_integrations()
		{
			if (did_action('elementor/loaded')) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/integrations/class-dctc-ai-elementor.php';
			}
		}

		/**
		 * Initialize RAG Engine if available.
		 *
		 * @return void
		 */
		public function dctc_ai_init_rag_engine()
		{
			if (!file_exists(DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-engine.php')) {
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
		public function dctc_ai_render_chatbot_ui($inline = false)
		{
			$wrapper_class = $inline ? 'dctc-ai-chat-root-inline' : 'dctc-ai-chat-root-floating';
			?>
			<div id="dctc-ai-frontend-root" class="<?php echo esc_attr($wrapper_class); ?>"
				data-inline="<?php echo esc_attr(wp_json_encode($inline)); ?>"></div>
			<?php
		}

		/**
		 * Register AI Assistant as a submenu under Click to Chat.
		 *
		 * @return void
		 */
		public function dctc_ai_add_admin_menu()
		{
			$this->admin_hook = add_submenu_page(
				'dragwyb-click-to-chat',
				esc_html__('AI Assistant', 'dragwyb-click-to-chat'),
				esc_html__('AI Assistant', 'dragwyb-click-to-chat'),
				'manage_options',
				'dragwyb-click-to-chat-ai',
				[$this, 'dctc_ai_render_admin_page']
			);
		}

		/**
		 * Render the AI admin dashboard page.
		 *
		 * @return void
		 */
		public function dctc_ai_render_admin_page()
		{
			require_once DCTC_PLUGIN_DIR . 'admin/ai/dctc-ai-dashboard.php';
		}

		/**
		 * Add an "AI Assistant" link on the Plugins list page.
		 *
		 * @param array $links Existing plugin action links.
		 * @return array
		 */
		public function dctc_ai_add_settings_link($links)
		{
			$ai_link = '<a href="' . esc_url(admin_url('admin.php?page=dragwyb-click-to-chat-ai')) . '">' . esc_html__('AI Assistant', 'dragwyb-click-to-chat') . '</a>';
			$links[] = $ai_link;
			return $links;
		}

		/**
		 * Load the AI client SDK and register the OpenAI and Google providers.
		 *
		 * @return void
		 */
		public function dctc_ai_register_ai_client()
		{
			$is_wp_ai_client_70 = function_exists('wp_ai_client_prompt');

			if (!$is_wp_ai_client_70) {
				// Skip if another plugin already loaded the AI Client SDK.
				if (!class_exists('\WordPress\AI_Client\AI_Client', false) && !class_exists('\WordPress\AiClient\AiClient', false)) {
					$sdk_autoload = DCTC_PLUGIN_DIR . 'vendor/wordpress/wp-ai-client/autoload.php';

					if (file_exists($sdk_autoload)) {
						require_once $sdk_autoload;
					}
				}

				if (!class_exists('\WordPress\AI_Client\AI_Client') && !class_exists('\WordPress\AiClient\AiClient')) {
					return;
				}
			}

			// Skip provider autoload if OpenAI/Google providers already exist (avoids
			// ComposerAutoloaderInit collisions when another AI plugin is also active).
			if (
				!class_exists('\WordPress\OpenAiAiProvider\Provider\OpenAiProvider', false) ||
				!class_exists('\WordPress\GoogleAiProvider\Provider\GoogleProvider', false)
			) {
				$providers_autoload = DCTC_PLUGIN_DIR . 'includes/ai/ai-providers/vendor/autoload.php';
				if (file_exists($providers_autoload)) {
					require_once $providers_autoload;
				}
			}

			if (!class_exists('\WordPress\AiClient\AiClient')) {
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

			if (!$is_wp_ai_client_70) {
				\WordPress\AI_Client\AI_Client::init();

				try {
					$http_transporter = \WordPress\AiClient\Providers\Http\HttpTransporterFactory::createTransporter();
					$registry->setHttpTransporter($http_transporter);
				} catch (\Exception $e) {
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
		private function dctc_ai_register_ai_provider($registry, $name, $class)
		{
			if (class_exists($class) && !$registry->hasProvider($name)) {
				$registry->registerProvider($class);
			}
		}

		/**
		 * One-time migration of legacy AI option names into unified settings.
		 * Fresh installs for this module — still keep the guard for parity.
		 *
		 * @return void
		 */
		private function dctc_ai_migrate_to_unified_settings()
		{
			if (get_option('dctc_ai_chat_assistant_settings_migrated')) {
				return;
			}

			$is_wp_ai_client_70 = function_exists('wp_ai_client_prompt');
			$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
			$migrated = false;

			$providers = ['openai', 'google'];

			foreach ($providers as $provider) {
				$key = '';
				if ($is_wp_ai_client_70) {
					$key = get_option('connectors_ai_' . $provider . '_api_key');
				} else {
					$creds = get_option('wp_ai_client_provider_credentials', []);
					$key = isset($creds[$provider]) ? $creds[$provider] : '';
				}

				if (!empty($key) && empty($settings['api_keys'][$provider])) {
					$settings['api_keys'][$provider] = sanitize_text_field($key);
					$migrated = true;
				}
			}

			$old_bot_settings = get_option('dctc_ai_chatbot_settings');
			if (!empty($old_bot_settings) && is_array($old_bot_settings)) {
				$settings['chatbot'] = wp_parse_args($old_bot_settings, $settings['chatbot']);
				$migrated = true;
			}

			$old_models = get_option('dctc_ai_ai_selected_models');
			if (!empty($old_models) && is_array($old_models)) {
				$settings['models'] = wp_parse_args($old_models, $settings['models']);
				$migrated = true;
			}

			if ($migrated) {
				DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);
			}

			update_option('dctc_ai_chat_assistant_settings_migrated', true);
		}

		/**
		 * Invalidate MCP site context cache when posts are saved or deleted.
		 *
		 * @return void
		 */
		public function dctc_ai_invalidate_mcp_cache()
		{
			wp_cache_delete('dctc_ai_site_context', 'dctc_ai_mcp');
		}
	}

endif;
