<?php
/**
 * DCTC Support Manager
 *
 * Core coordinator and bootstrap for Support Center services.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Manager
 */
class DCTC_Support_Manager {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
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
		$this->load_dependencies();
	}

	/**
	 * Load support service classes.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$dir = plugin_dir_path( __FILE__ );

		require_once $dir . 'class-dctc-support-db.php';
		require_once $dir . 'class-dctc-support-permission-service.php';
		require_once $dir . 'class-dctc-support-event-service.php';
		require_once $dir . 'class-dctc-support-note-service.php';
		require_once $dir . 'class-dctc-support-category-service.php';
		require_once $dir . 'class-dctc-support-tag-service.php';
		require_once $dir . 'class-dctc-support-product-service.php';
		require_once $dir . 'class-dctc-support-taxonomy-service.php';
		require_once $dir . 'class-dctc-support-agent-service.php';
		require_once $dir . 'class-dctc-support-ticket-service.php';
		require_once $dir . 'class-dctc-support-assignment-engine.php';
		require_once $dir . 'class-dctc-support-ai-handoff-service.php';
		require_once $dir . 'class-dctc-support-notification-service.php';
		require_once $dir . 'class-dctc-support-woocommerce-service.php';
		require_once $dir . 'class-dctc-support-ai-assist-service.php';
		require_once $dir . 'class-dctc-support-rest-controller.php';
		require_once $dir . 'class-dctc-support-portal.php';

		$rest_controller = new DCTC_Support_REST_Controller();
		add_action( 'rest_api_init', array( $rest_controller, 'register_routes' ) );

		DCTC_Support_Portal::init();

		// Admin Menu & Standalone Support Assets
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_print_scripts', array( $this, 'hide_unrelated_admin_notices' ) );
	}

	/**
	 * Register dedicated Top-Level Support Center menu and submenus.
	 *
	 * @return void
	 */
	public function register_admin_menus() {
		$settings   = get_option( 'dctc_support_settings', array() );
		$is_enabled = ! empty( $settings['enabled'] );

		if ( ! $is_enabled ) {
			return;
		}

		if ( ! class_exists( 'DCTC_Support_Permission_Service' ) ) {
			return;
		}

		// Only show menu if user can view tickets or is WP admin
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'view_tickets' ) ) {
			return;
		}

		$support_cap = 'read';

		// Top-level main menu (Positioned at 21, right after core Pages menu at 20)
		add_menu_page(
			esc_html__( 'Support Center', 'dragwyb-click-to-chat' ),
			esc_html__( 'Support Center', 'dragwyb-click-to-chat' ),
			$support_cap,
			'dragwyb-support-center',
			array( $this, 'render_support_admin_page' ),
			'dashicons-tickets-alt',
			21
		);

		// Submenu 1: Dashboard (Default)
		add_submenu_page(
			'dragwyb-support-center',
			esc_html__( 'Dashboard', 'dragwyb-click-to-chat' ),
			esc_html__( 'Dashboard', 'dragwyb-click-to-chat' ),
			$support_cap,
			'dragwyb-support-center',
			array( $this, 'render_support_admin_page' )
		);

		// Submenu 2: Tickets Workspace
		add_submenu_page(
			'dragwyb-support-center',
			esc_html__( 'Tickets', 'dragwyb-click-to-chat' ),
			esc_html__( 'Tickets', 'dragwyb-click-to-chat' ),
			$support_cap,
			'dragwyb-support-tickets',
			array( $this, 'render_support_admin_page' )
		);

		// Submenu 3: Agents & Staff (Requires manage_agents permission)
		if ( DCTC_Support_Permission_Service::current_user_can_support( 'manage_agents' ) ) {
			add_submenu_page(
				'dragwyb-support-center',
				esc_html__( 'Agents & Staff', 'dragwyb-click-to-chat' ),
				esc_html__( 'Agents & Staff', 'dragwyb-click-to-chat' ),
				$support_cap,
				'dragwyb-support-agents',
				array( $this, 'render_support_admin_page' )
			);
		}

		// Submenu 4: Categories & Tags (Categories, Tags, Products & Custom Taxonomies)
		if ( DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) || DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			add_submenu_page(
				'dragwyb-support-center',
				esc_html__( 'Categories & Tags', 'dragwyb-click-to-chat' ),
				esc_html__( 'Categories & Tags', 'dragwyb-click-to-chat' ),
				$support_cap,
				'dragwyb-support-taxonomies',
				array( $this, 'render_support_admin_page' )
			);
		}

		// Submenu 5: Support Settings (Requires manage_settings permission)
		if ( DCTC_Support_Permission_Service::current_user_can_support( 'manage_settings' ) ) {
			add_submenu_page(
				'dragwyb-support-center',
				esc_html__( 'Support Settings', 'dragwyb-click-to-chat' ),
				esc_html__( 'Support Settings', 'dragwyb-click-to-chat' ),
				$support_cap,
				'dragwyb-support-settings',
				array( $this, 'render_support_admin_page' )
			);
		}
	}

	/**
	 * Render the standalone Support Center admin container.
	 *
	 * @return void
	 */
	public function render_support_admin_page() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Server-side page permission checks
		if ( 'dragwyb-support-agents' === $page && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_agents' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access Agents & Staff settings.', 'dragwyb-click-to-chat' ), 403 );
		}

		if ( 'dragwyb-support-taxonomies' === $page && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access Categories & Tags.', 'dragwyb-click-to-chat' ), 403 );
		}

		if ( 'dragwyb-support-settings' === $page && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access Support Settings.', 'dragwyb-click-to-chat' ), 403 );
		}

		require_once DCTC_PLUGIN_DIR . 'admin/support/dctc-support-dashboard.php';
	}

	/**
	 * Enqueue standalone Support Center assets.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		$page            = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_support_page = (
			'dragwyb-support-center' === $page ||
			0 === strpos( $page, 'dragwyb-support-' ) ||
			false !== strpos( $hook, 'dragwyb-support' )
		);

		if ( ! $is_support_page ) {
			return;
		}

		// Verify user has permission to access the requested page before enqueuing script
		if ( 'dragwyb-support-agents' === $page && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_agents' ) ) {
			return;
		}
		if ( 'dragwyb-support-taxonomies' === $page && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return;
		}
		if ( 'dragwyb-support-settings' === $page && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_settings' ) ) {
			return;
		}

		wp_enqueue_media();
		if ( function_exists( 'wp_enqueue_editor' ) ) {
			wp_enqueue_editor();
		}

		// Standalone Support CSS
		$css_candidates = array(
			'build/ai/support/style-dctc-support-center.css',
			'build/support/style-dctc-support-center.css',
			'build/ai/support/style-dctc-support-dashboard.css',
			'build/ai/support/style-dctc-support-tickets.css',
		);
		foreach ( $css_candidates as $css_path ) {
			if ( file_exists( DCTC_PLUGIN_DIR . $css_path ) ) {
				wp_enqueue_style( 'dctc-support-center-style', DCTC_PLUGIN_URL . $css_path, array(), DCTC_VERSION );
				break;
			}
		}

		// Determine specific script handle and bundle path based on active page
		$script_slug = 'dctc-support-dashboard';
		if ( 'dragwyb-support-tickets' === $page ) {
			$script_slug = 'dctc-support-tickets';
		} elseif ( 'dragwyb-support-agents' === $page ) {
			$script_slug = 'dctc-support-agents';
		} elseif ( 'dragwyb-support-taxonomies' === $page ) {
			$script_slug = 'dctc-support-taxonomies';
		} elseif ( 'dragwyb-support-settings' === $page ) {
			$script_slug = 'dctc-support-settings';
		}

		// Fallback to legacy/all-in-one bundle if split bundle doesn't exist
		$js_relative_path    = "build/ai/support/{$script_slug}.js";
		$asset_relative_path = "build/ai/support/{$script_slug}.asset.php";
		if ( ! file_exists( DCTC_PLUGIN_DIR . $js_relative_path ) ) {
			$script_slug         = 'dctc-support-center';
			$js_relative_path    = 'build/ai/support/dctc-support-center.js';
			$asset_relative_path = 'build/ai/support/dctc-support-center.asset.php';
		}

		$asset_file = file_exists( DCTC_PLUGIN_DIR . $asset_relative_path )
			? require DCTC_PLUGIN_DIR . $asset_relative_path
			: array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
				'version'      => DCTC_VERSION,
			);

		$handle = "{$script_slug}-script";

		wp_enqueue_script(
			$handle,
			DCTC_PLUGIN_URL . $js_relative_path,
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		$user_id     = get_current_user_id();
		$permissions = class_exists( 'DCTC_Support_Permission_Service' )
			? DCTC_Support_Permission_Service::get_user_permissions( $user_id )
			: array(
				'view_tickets' => true,
				'is_admin'     => current_user_can( 'manage_options' ),
			);

		wp_localize_script(
			$handle,
			'dctc_support_data',
			array(
				'rest_url'              => esc_url_raw( rest_url() ),
				'nonce'                 => wp_create_nonce( 'wp_rest' ),
				'user_id'               => $user_id,
				'permissions'           => $permissions,
				'current_page'          => $page,
				'is_woocommerce_active' => class_exists( 'WooCommerce' ) || function_exists( 'WC' ),
			)
		);
	}

	/**
	 * Hide unrelated admin notices on the support settings page.
	 *
	 * @return void
	 */
	public function hide_unrelated_admin_notices() {
		// nonce verification is not required here because we are not using the nonce here.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( strpos( $page, 'dragwyb-support-' ) === false ) {
			return;
		}

		$current_page = str_replace( 'dragwyb-support-', '', $page );

		if ( ! in_array( $current_page, array( 'center', 'tickets', 'agents', 'taxonomies', 'settings' ), true ) ) {
			return;
		}

		global $wp_filter;

		$notice_hooks = array(
			'user_admin_notices',
			'admin_notices',
			'all_admin_notices',
		);

		foreach ( $notice_hooks as $hook_name ) {

			if (
			empty( $wp_filter[ $hook_name ] ) ||
			empty( $wp_filter[ $hook_name ]->callbacks ) ||
			! is_array( $wp_filter[ $hook_name ]->callbacks )
			) {
				continue;
			}

			foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {

				foreach ( $callbacks as $callback_id => $callback_data ) {

					if ( empty( $callback_data['function'] ) ) {
						continue;
					}

					$callback = $callback_data['function'];

					unset(
						$wp_filter[ $hook_name ]->callbacks[ $priority ][ $callback_id ]
					);
				}
			}
		}

		// Remove delayed admin notices generated by WordPress.
		if (
		isset( $wp_filter['admin_footer']->callbacks ) &&
		is_array( $wp_filter['admin_footer']->callbacks )
		) {
			foreach ( $wp_filter['admin_footer']->callbacks as $priority => $callbacks ) {

				foreach ( $callbacks as $callback_id => $callback_data ) {

					if (
					isset( $callback_data['function'] ) &&
					'render_delayed_admin_notices' === $callback_data['function']
					) {
						unset(
							$wp_filter['admin_footer']->callbacks[ $priority ][ $callback_id ]
						);
					}
				}
			}
		}

		// Register this plugin's notices after unrelated notices have been removed.
		add_action(
			'admin_notices',
			function () use ( $current_page ) {
				$this->display_admin_notices( $current_page );
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Display plugin-specific admin notices.
	 *
	 * @return void
	 */
	private function display_admin_notices( $current_page = '' ) {
		do_action( 'dctc_support_admin_notices' );
	}
}
