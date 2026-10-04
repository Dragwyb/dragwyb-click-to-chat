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

		// Submenu 4: Taxonomies (Categories, Tags, Products & Custom Taxonomies)
		if ( DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) || DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			add_submenu_page(
				'dragwyb-support-center',
				esc_html__( 'Taxonomies', 'dragwyb-click-to-chat' ),
				esc_html__( 'Taxonomies', 'dragwyb-click-to-chat' ),
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
		require_once DCTC_PLUGIN_DIR . 'admin/support/dctc-support-dashboard.php';
	}

	/**
	 * Enqueue standalone Support Center assets.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_support_page = (
			'dragwyb-support-center' === $page ||
			0 === strpos( $page, 'dragwyb-support-' ) ||
			false !== strpos( $hook, 'dragwyb-support' )
		);

		if ( ! $is_support_page ) {
			return;
		}

		wp_enqueue_media();

		// Standalone Support CSS
		$css_candidates = array(
			'build/ai/support/style-dctc-support-center.css',
			'build/support/style-dctc-support-center.css',
		);
		foreach ( $css_candidates as $css_path ) {
			if ( file_exists( DCTC_PLUGIN_DIR . $css_path ) ) {
				wp_enqueue_style( 'dctc-support-center-style', DCTC_PLUGIN_URL . $css_path, array(), DCTC_VERSION );
				break;
			}
		}

		// Standalone Support JS
		$asset_file = file_exists( DCTC_PLUGIN_DIR . 'build/ai/support/dctc-support-center.asset.php' )
			? require DCTC_PLUGIN_DIR . 'build/ai/support/dctc-support-center.asset.php'
			: array(
				'dependencies' => array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
				'version'      => DCTC_VERSION,
			);

		wp_enqueue_script(
			'dctc-support-center-script',
			DCTC_PLUGIN_URL . 'build/ai/support/dctc-support-center.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		$user_id     = get_current_user_id();
		$permissions = class_exists( 'DCTC_Support_Permission_Service' )
			? DCTC_Support_Permission_Service::get_user_permissions( $user_id )
			: array( 'view_tickets' => true, 'is_admin' => current_user_can( 'manage_options' ) );

		wp_localize_script(
			'dctc-support-center-script',
			'dctc_support_data',
			array(
				'rest_url'    => esc_url_raw( rest_url() ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'user_id'     => $user_id,
				'permissions' => $permissions,
			)
		);
	}
}
