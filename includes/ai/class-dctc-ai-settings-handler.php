<?php
/**
 * DCTC AI Settings Handler
 *
 * Composition root and REST route registrar. Owns the plugin's settings
 * option (get/persist) and the handful of generic settings endpoints
 * (chatbot config, display config, setup wizard, uploads). Chat, RAG, MCP,
 * and API-key concerns each live in their own collaborator class — see
 * class-dctc-ai-chat-controller.php, class-dctc-ai-rag-controller.php,
 * class-dctc-ai-mcp-controller.php, and class-dctc-ai-key-store.php.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once DCTC_PLUGIN_DIR . 'includes/ai/trait-dctc-ai-rest-helpers.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-key-store.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-mcp-controller.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-rag-controller.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-chat-controller.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-usage-tracker.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-leads-controller.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-woocommerce.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-tool-registry.php';
require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-data-sanitizer.php';

/**
 * Class DCTC_AI_Settings_Handler
 */
class DCTC_AI_Settings_Handler {

	use DCTC_AI_REST_Helpers;

	/**
	 * Key Store
	 *
	 * @var DCTC_AI_Key_Store
	 */
	private $key_store;

	/**
	 * RAG Controller
	 *
	 * @var DCTC_AI_RAG_Controller
	 */
	private $rag_controller;

	/**
	 * MCP Controller
	 *
	 * @var DCTC_AI_MCP_Controller
	 */
	private $mcp_controller;

	/**
	 * Chat Controller
	 *
	 * @var DCTC_AI_Chat_Controller
	 */
	private $chat_controller;

	/**
	 * Leads Controller
	 *
	 * @var DCTC_AI_Leads_Controller
	 */
	private $leads_controller;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->key_store        = new DCTC_AI_Key_Store();
		$this->rag_controller   = new DCTC_AI_RAG_Controller();
		$this->mcp_controller   = new DCTC_AI_MCP_Controller( $this->key_store );
		$this->chat_controller  = new DCTC_AI_Chat_Controller( $this->rag_controller, $this->mcp_controller );
		$this->leads_controller = new DCTC_AI_Leads_Controller();

		add_action( 'rest_api_init', array( $this, 'dctc_ai_register_routes' ) );
	}

	/**
	 * Register REST Routes
	 *
	 * Registers all core settings and chat capability endpoints under the dragwyb-click-to-chat namespace.
	 *
	 * @return void
	 */
	public function dctc_ai_register_routes() {
		register_rest_route(
			'dctc-ai/v1',
			'/save-settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->key_store, 'save_provider_keys' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/save-bot-settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_save_bot_settings' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/onboarding/complete',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_complete_onboarding' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/onboarding/skip',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_skip_onboarding' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/setup-wizard',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_update_setup_wizard_status' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/reset-key',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->key_store, 'reset_key' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/save-display-settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_save_display_settings' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/upload',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_upload_file' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_upload' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/chat',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->chat_controller, 'handle' ),
				'permission_callback' => array( $this->chat_controller, 'permission_check' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/chat/sync',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->chat_controller, 'sync_session' ),
				'permission_callback' => array( $this->chat_controller, 'permission_check' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/delete-session',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->chat_controller, 'delete_session' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/sessions',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->chat_controller, 'get_sessions' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/sessions/(?P<session_id>[a-zA-Z0-9_-]+)/summarize',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->chat_controller, 'summarize_session' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/analytics',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->chat_controller, 'get_analytics' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/error-logs',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'dctc_ai_get_error_logs' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/error-logs',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'dctc_ai_clear_error_logs' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/error-logs/retention',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dctc_ai_update_error_logs_retention' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/clear-session',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->chat_controller, 'clear_session' ),
				'permission_callback' => array( $this->chat_controller, 'permission_check' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/rag/settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->rag_controller, 'save_settings' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/rag/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->rag_controller, 'get_post_types' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/rag/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->rag_controller, 'get_stats' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/rag/index',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->rag_controller, 'index_content' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/rag/index/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->rag_controller, 'get_index_status' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/servers',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->mcp_controller, 'get_servers' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/servers',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->mcp_controller, 'save_server' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/servers/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this->mcp_controller, 'update_server' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/servers/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this->mcp_controller, 'delete_server' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/all-content',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_content' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/usage-stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'dctc_ai_get_usage_stats' ),
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		// AI Lead Capture endpoints
		register_rest_route(
			'dctc-ai/v1',
			'/leads/capture',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->leads_controller, 'capture_lead' ),
				'permission_callback' => array( $this->leads_controller, 'permission_check_capture' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/leads',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->leads_controller, 'get_leads' ),
				'permission_callback' => array( $this->leads_controller, 'permission_check_admin' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/leads/(?P<id>\d+)/status',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->leads_controller, 'update_status' ),
				'permission_callback' => array( $this->leads_controller, 'permission_check_admin' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/leads/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this->leads_controller, 'delete_lead' ),
				'permission_callback' => array( $this->leads_controller, 'permission_check_admin' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/leads/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->leads_controller, 'export_csv' ),
				'permission_callback' => array( $this->leads_controller, 'permission_check_admin' ),
			)
		);

		// WooCommerce AI Sales Assistant endpoints
		$wc_controller = new DCTC_AI_WooCommerce();
		register_rest_route(
			'dctc-ai/v1',
			'/woocommerce/products',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $wc_controller, 'rest_search_products' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/woocommerce/cart',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $wc_controller, 'rest_get_cart' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/woocommerce/order-status',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $wc_controller, 'rest_lookup_order' ),
				'permission_callback' => function ( $request ) {
					return is_user_logged_in() || current_user_can( 'read' );
				},
			)
		);

		// AI Agents, Tools & Automation Workflows endpoints
		register_rest_route(
			'dctc-ai/v1',
			'/tools',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function () {
					return new \WP_REST_Response(
						array(
							'success' => true,
							'tools'   => DCTC_AI_Tool_Registry::get_all_tools(),
						),
						200
					);
				},
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/tools/execute',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => function ( $request ) {
					$params    = $request->get_json_params();
					$tool_name = sanitize_key( $params['tool'] ?? '' );
					$args      = (array) ( $params['arguments'] ?? array() );
					$context   = array(
						'session_id' => sanitize_text_field( $params['session_id'] ?? '' ),
						'email'      => sanitize_email( $params['email'] ?? '' ),
					);
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-tool-registry.php';
					$res       = DCTC_AI_Tool_Registry::execute_tool( $tool_name, $args, $context );
					return new \WP_REST_Response( $res, ! empty( $res['success'] ) ? 200 : 400 );
				},
				'permission_callback' => function ( $request ) {
					$params    = $request->get_json_params();
					$tool_name = sanitize_key( $params['tool'] ?? '' );
					if ( empty( $tool_name ) ) {
						return false;
					}
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-tool-registry.php';
					$tools = DCTC_AI_Tool_Registry::get_all_tools();
					if ( ! isset( $tools[ $tool_name ] ) || empty( $tools[ $tool_name ]['enabled'] ) ) {
						return false;
					}
					$tool = $tools[ $tool_name ];
					if ( ! empty( $tool['requires_admin'] ) && ! current_user_can( 'manage_options' ) ) {
						return false;
					}
					if ( is_callable( $tool['permission_callback'] ) ) {
						return (bool) call_user_func( $tool['permission_callback'], (array) ( $params['arguments'] ?? array() ), array() );
					}
					return true;
				},
			)
		);

		// Admin AI Copilot endpoints
		register_rest_route(
			'dctc-ai/v1',
			'/copilot',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => function ( $request ) {
					$params = $request->get_json_params();
					$prompt = sanitize_textarea_field( $params['prompt'] ?? '' );
					if ( empty( $prompt ) ) {
						return new \WP_REST_Response(
							array(
								'success' => false,
								'message' => esc_html__( 'Prompt cannot be empty.', 'dragwyb-click-to-chat' ),
							),
							400
						);
					}
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-admin-copilot.php';
					try {
						$res = DCTC_AI_Admin_Copilot::execute_copilot_chat( $prompt );
						return new \WP_REST_Response(
							array(
								'success'  => true,
								'message'  => $res['message'],
								'provider' => $res['provider'],
								'model'    => $res['model'],
								'stats'    => $res['stats'],
							),
							200
						);
					} catch ( \Throwable $e ) {
						return new \WP_REST_Response(
							array(
								'success' => false,
								'message' => $e->getMessage(),
							),
							500
						);
					}
				},
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/copilot/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function () {
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-admin-copilot.php';
					$stats = DCTC_AI_Admin_Copilot::get_analytics_summary();
					return new \WP_REST_Response(
						array(
							'success' => true,
							'stats'   => $stats,
						),
						200
					);
				},
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);

		// Feature 15: WordPress Abilities & Developer API endpoints
		register_rest_route(
			'dctc-ai/v1',
			'/abilities',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function ( $request ) {
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';
					$is_admin  = current_user_can( 'manage_options' );
					$abilities = DCTC_AI_Abilities::get_abilities( ! $is_admin );
					return new \WP_REST_Response(
						array(
							'success'   => true,
							'abilities' => array_values( $abilities ),
						),
						200
					);
				},
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/abilities/(?P<ability>[a-zA-Z0-9_\-\/]+)/execute',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => function ( $request ) {
					$ability_name = sanitize_text_field( $request->get_param( 'ability' ) );
					$params       = $request->get_json_params() ?: array();
					$args         = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : $params;
					$context      = array(
						'session_id' => sanitize_text_field( $params['session_id'] ?? '' ),
						'email'      => sanitize_email( $params['email'] ?? '' ),
					);
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';
					$result = DCTC_AI_Abilities::execute_ability( $ability_name, $args, $context );
					return new \WP_REST_Response( $result, ! empty( $result['success'] ) ? 200 : 400 );
				},
				'permission_callback' => function ( $request ) {
					$ability_name = sanitize_text_field( $request->get_param( 'ability' ) );
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';
					$ability = DCTC_AI_Abilities::get_ability( $ability_name );
					if ( ! $ability ) {
						return false;
					}
					if ( ! empty( $ability['requires_auth'] ) && ! is_user_logged_in() ) {
						return false;
					}
					if ( is_callable( $ability['permission_callback'] ) ) {
						$params = $request->get_json_params() ?: array();
						$args   = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : $params;
						return (bool) call_user_func( $ability['permission_callback'], $args, array() );
					}
					return true;
				},
			)
		);

		// Feature 15: MCP (Model Context Protocol) Server endpoints
		register_rest_route(
			'dctc-ai/v1',
			'/mcp/manifest',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->mcp_controller, 'get_manifest' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/tools',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this->mcp_controller, 'get_tools' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/tools/(?P<tool>[a-zA-Z0-9_\-]+)',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->mcp_controller, 'execute_tool' ),
				'permission_callback' => function ( $request ) {
					$tool_name = sanitize_text_field( $request->get_param( 'tool' ) );
					require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-abilities.php';
					$ability = DCTC_AI_Abilities::get_ability( $tool_name );
					if ( ! $ability ) {
						return false;
					}
					if ( ! empty( $ability['requires_auth'] ) && ! is_user_logged_in() ) {
						return false;
					}
					if ( is_callable( $ability['permission_callback'] ) ) {
						$params = $request->get_json_params() ?: array();
						$args   = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : $params;
						return (bool) call_user_func( $ability['permission_callback'], $args, array() );
					}
					return true;
				},
			)
		);

		register_rest_route(
			'dctc-ai/v1',
			'/mcp/rpc',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this->mcp_controller, 'handle_jsonrpc' ),
				'permission_callback' => '__return_true',
			)
		);

		// Feature 15: Developer Meta & Diagnostics endpoint
		register_rest_route(
			'dctc-ai/v1',
			'/developer/info',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function () {
					$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
					$bot      = $settings['chatbot'] ?? array();
					return new \WP_REST_Response(
						array(
							'success'         => true,
							'plugin'          => 'Dragwyb Click to Chat AI',
							'version'         => defined( 'DCTC_VERSION' ) ? DCTC_VERSION : '1.1.0',
							'schema_version'  => '2.0.0',
							'active_provider' => $bot['default_provider'] ?? 'openai',
							'endpoints'       => array(
								'chat'      => rest_url( 'dctc-ai/v1/chat' ),
								'abilities' => rest_url( 'dctc-ai/v1/abilities' ),
								'mcp_rpc'   => rest_url( 'dctc-ai/v1/mcp/rpc' ),
								'mcp_tools' => rest_url( 'dctc-ai/v1/mcp/tools' ),
								'leads'     => rest_url( 'dctc-ai/v1/leads' ),
								'copilot'   => rest_url( 'dctc-ai/v1/copilot' ),
							),
						),
						200
					);
				},
				'permission_callback' => array( $this, 'dctc_ai_permission_only_admins' ),
			)
		);
	}

	/**
	 * Get all public content (pages, posts, CPTs) for exclusion dropdown.
	 */
	public function get_all_content( $request ) {
		$post_types = get_post_types(
			array(
				'public'              => true,
				'exclude_from_search' => false,
			),
			'names'
		);
		unset( $post_types['attachment'] );

		$args = array(
			'post_type'      => array_values( $post_types ),
			'post_status'    => 'any',
			'posts_per_page' => 500,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = new \WP_Query( $args );
		$items = array();
		foreach ( $query->posts as $post ) {
			$type_obj = get_post_type_object( $post->post_type );
			$items[]  = array(
				'id'    => $post->ID,
				'title' => $post->post_title ? $post->post_title : 'ID ' . $post->ID,
				'type'  => $type_obj && isset( $type_obj->labels->singular_name ) ? $type_obj->labels->singular_name : ucfirst( $post->post_type ),
			);
		}

		return new \WP_REST_Response( $items, 200 );
	}

	/**
	 * Main Permission Check
	 *
	 * @return bool True if current user is an admin.
	 */
	public function dctc_ai_permission_only_admins() {
		return current_user_can( 'manage_options' );
	}


	/**
	 * Return recent plugin error logs for the AI dashboard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_get_error_logs( $request ) {
		$limit = absint( $request->get_param( 'limit' ) );
		if ( $limit <= 0 ) {
			$limit = 50;
		}
		$limit  = min( $limit, 500 );
		$offset = absint( $request->get_param( 'offset' ) );

		$logs           = class_exists( 'DCTC_Error_Logger' ) ? DCTC_Error_Logger::get_logs( $limit, $offset ) : array();
		$total          = class_exists( 'DCTC_Error_Logger' ) ? DCTC_Error_Logger::get_total_count() : count( $logs );
		$retention_days = class_exists( 'DCTC_Error_Logger' ) ? DCTC_Error_Logger::get_retention_days() : 0;
		$enabled        = class_exists( 'DCTC_Error_Logger' ) ? DCTC_Error_Logger::is_enabled() : false;

		return new \WP_REST_Response(
			array(
				'success'        => true,
				'logs'           => $logs,
				'total'          => $total,
				'retention_days' => $retention_days,
				'enabled'        => $enabled,
			),
			200
		);
	}

	/**
	 * Clear plugin error logs or delete a single entry from the AI dashboard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_clear_error_logs( $request ) {
		$id = $request->get_param( 'id' );
		if ( ! empty( $id ) ) {
			if ( class_exists( 'DCTC_Error_Logger' ) ) {
				DCTC_Error_Logger::delete_log( absint( $id ) );
			}
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Log entry deleted.', 'dragwyb-click-to-chat' ),
				),
				200
			);
		}

		if ( class_exists( 'DCTC_Error_Logger' ) ) {
			DCTC_Error_Logger::clear_logs();
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'All error logs cleared.', 'dragwyb-click-to-chat' ),
			),
			200
		);
	}

	/**
	 * Update error log automatic retention period (cron setting).
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_update_error_logs_retention( $request ) {
		$days = $request->get_param( 'retention_days' );
		if ( is_null( $days ) ) {
			$days = $request->get_param( 'days' );
		}

		$days = absint( $days );

		if ( class_exists( 'DCTC_Error_Logger' ) ) {
			DCTC_Error_Logger::set_retention_days( $days );
		}

		return new \WP_REST_Response(
			array(
				'success'        => true,
				'retention_days' => $days,
				'message'        => __( 'Log retention setting updated successfully.', 'dragwyb-click-to-chat' ),
			),
			200
		);
	}

	/**
	 * Return AI usage metrics and budget status for the admin dashboard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_get_usage_stats( $request ) {
		$stats = class_exists( 'DCTC_AI_Usage_Tracker' ) ? DCTC_AI_Usage_Tracker::get_usage_stats() : array();
		return new \WP_REST_Response(
			array(
				'success' => true,
				'stats'   => $stats,
			),
			200
		);
	}

	/**
	 * Get Unified Settings
	 *
	 * @return array The complete multidimensional array of plugin settings.
	 */
	public static function dctc_ai_get_all_settings() {
		$defaults = array(
			'models'      => array(
				'openai'     => 'gpt-4o-mini',
				'google'     => 'gemini-3.5-flash-lite',
				'anthropic'  => 'claude-3-5-sonnet-20241022',
				'openrouter' => 'anthropic/claude-3.5-sonnet',
				'groq'       => 'llama-3.3-70b-versatile',
				'deepseek'   => 'deepseek-chat',
			),
			'chatbot'     => array(
				'bot_name'                       => 'Dragwyb AI',
				'primary_color'                  => '#6366f1',
				'greeting_msg'                   => 'Hello! I am your AI assistant. How can I help you today?',
				'bot_avatar'                     => '',
				'bubble_style'                   => 'rounded',
				'api_error_msg'                  => 'There is some error on server, please contact our [support agent]({support_url}).',
				'support_url'                    => home_url( '/support' ),
				'pre_question_1'                 => '',
				'pre_question_2'                 => '',
				'pre_question_3'                 => '',
				'pre_question_4'                 => '',
				'pre_questions_bg_color'         => '#ffffff',
				'pre_questions_text_color'       => '#475569',
				'pre_questions_border_color'     => '#e2e8f0',
				'pre_questions_border_radius'    => 'rounded',
				'system_prompt'                  => 'You are a knowledgeable expert helping people. Rules: 1) NEVER use phrases like "Based on provided content", "According to", "The information shows" - just answer directly like a human. 2) Greetings → "How can I help you today?" 3) If user asks for links/posts → GIVE ALL links immediately. 4) If user mentions topic → GIVE that post\'s link automatically. 5) Answer naturally, full sentences, conversational. 6) Include links in responses when relevant. 7) Be proactive - solve problems, don\'t ask questions. 8) Sound genuinely knowledgeable and helpful, like a real expert talking to you. 9) FOR "HOW TO" OR "HOW DO I" QUESTIONS: Use concise structure - Title, brief description (1-2 sentences), then mention relevant documentation/guides. 10) Keep "what is" explanations concise and natural (2-3 sentences max).',
				'temperature'                    => 0.7,
				'max_tokens'                     => 500,
				'save_chat'                      => false,
				'chat_retention_days'            => 0,
				'memory_window_size'             => 10,
				'ask_email'                      => false,
				'enable_pre_questions'           => false,
				'enable_uploads'                 => false,
				'allowed_file_types'             => 'jpg, jpeg, png, webp, gif, pdf, txt, doc, docx',
				'excluded_file_types'            => 'php, php3, php4, php5, phtml, phar, cgi, pl, py, sh, exe, bat, cmd, js, html, htm, svg',
				'max_upload_size'                => 5,
				'max_files_per_message'          => 3,
				'store_chat_attachments'         => 'temp',
				'show_bot_avatar_in_chat'        => true,
				'show_user_avatar_in_chat'       => true,
				'show_sources'                   => true,
				'bot_icon_preset'                => 'bot',
				'user_avatar'                    => '',
				'user_icon_preset'               => 'user',
				'action_buttons'                 => array(),
				'default_provider'               => 'openai',
				'fallback_provider'              => '',
				'fallback_model'                 => '',
				'enable_failover'                => true,
				'knowledge_text'                 => '',
				'knowledge_urls'                 => array(),
				'training_files'                 => array(),
				'rate_limit_per_minute'          => 20,
				'enable_usage_limits'            => true,
				'visitor_daily_message_limit'    => 50,
				'monthly_request_budget'         => 5000,
				'budget_limit_message'           => 'You have reached the daily chat limit. Please connect with our team directly via WhatsApp or Support.',
				'enable_budget_email_alerts'     => true,
				'alert_email'                    => '',
				'enable_error_log'               => true,
				'error_log_retention_days'       => 0,
				'enable_lead_capture'            => false,
				'lead_trigger_type'              => 'manual',
				'lead_trigger_delay'             => 30,
				'lead_trigger_message_count'     => 3,
				'lead_form_title'                => 'Contact Our Team',
				'lead_form_subtitle'             => 'Leave your details and our team will get back to you shortly.',
				'lead_fields'                    => array(
					'name'         => true,
					'email'        => true,
					'phone'        => true,
					'company'      => false,
					'company_size' => false,
					'budget'       => true,
					'timeline'     => true,
					'interest'     => true,
					'requirement'  => true,
				),
				'lead_qualification_threshold'   => 70,
				'enable_ai_intent_scoring'       => true,
				'enable_lead_email_alerts'       => true,
				'lead_notification_email'        => '',
				'lead_webhook_url'               => '',
				'enable_support_escalation'      => true,
				'auto_assign_support_tickets'    => true,
				'auto_pause_ai_on_ticket'        => true,
				'human_agent_max_wait_time'      => 60,
				'human_agent_waiting_message'    => 'Sorry to keep you waiting...',
				'enable_ai_tools'                => true,
				'enabled_tools'                  => array( 'search_products', 'get_order_status', 'create_support_ticket', 'book_appointment', 'search_website_content' ),
				'workflow_webhook_url'           => '',
				'ticket_notification_email'      => '',
				'appointment_notification_email' => '',
				'enable_page_context'            => true,
				'enable_multilingual'            => true,
				'preferred_language'             => 'auto',
				'visitor_language_override'      => true,
				'enable_voice_input'             => true,
				'enable_voice_output'            => false,
				'voice_language'                 => 'auto',
				'enable_vision_understanding'    => true,
				'support_ticket_msg'             => 'I have logged your inquiry with our support team and created a support ticket for this session. A support specialist will review your message and assist you shortly.',
				'order_tracking_prompt_msg'      => 'Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.',
				'order_tracking_login_msg'       => 'To securely track your order status, please [log in to your account]({login_url}) first.',
				'order_mismatch_msg'             => 'This order was purchased with a different email address. For privacy and security reasons, order details cannot be displayed.',
				'no_data_message'                => 'I don\'t have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.',
			),
			'display'     => array(
				'entire_site'              => false,
				'exclude_pages'            => '',
				'position'                 => 'bottom-right',
				'widget_size'              => 64,
				'widget_size_unit'         => 'px',
				'custom_vertical_align'    => 'bottom',
				'custom_vertical'          => 24,
				'custom_vertical_unit'     => 'px',
				'custom_side'              => 'right',
				'custom_horizontal'        => 24,
				'custom_horizontal_unit'   => 'px',
				'show_on_mobile'           => true,
				'trigger_type'             => 'click',
				'trigger_delay'            => 5,
				'time_delay'               => 0,
				'launcher_text'            => 'Chat with us',
				'assistant_icon'           => '',
				'launcher_icon_preset'     => 'chat',
				'enable_smart_triggers'    => false,
				'trigger_scroll_depth'     => 50,
				'trigger_exit_intent'      => false,
				'trigger_inactivity'       => 30,
				'trigger_action'           => 'show_bubble',
				'proactive_bubble_message' => '👋 Hi there! Have a question about this page? Let me know if I can help!',
				'target_devices'           => 'all',
				'target_users'             => 'all',
				'url_rules'                => '',
			),
			'rag'         => array(
				'enabled'              => true,
				'post_types'           => array( 'post', 'page' ),
				'chunk_size'           => 1000,
				'max_results'          => 5,
				'min_confidence'       => 0.65,
				'auto_index'           => true,
				'require_indexed_data' => false,
				'no_data_message'      => 'I don\'t have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.',
				'vector_db'            => array( 'provider' => 'sqlite' ),
				'indexing'             => array(
					'chunk_size'    => 1000,
					'chunk_overlap' => 100,
					'auto_update'   => true,
				),
				'embeddings'           => array(
					'provider' => '',
					'model'    => '',
				),
				'max_tokens'           => '',
			),
			'mcp_servers' => array(),
		);

		$settings = get_option( 'dctc_ai_chat_assistant_settings', array() );

		foreach ( $defaults as $key => $default_value ) {
			if ( ! isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $default_value;
			} elseif ( is_array( $default_value ) && is_array( $settings[ $key ] ) ) {
				$settings[ $key ] = wp_parse_args( $settings[ $key ], $default_value );
			}
		}

		return $settings;
	}

	/**
	 * Settings safe to expose on the public frontend (no secrets / prompts / RAG).
	 *
	 * @return array{
	 *   chatbot: array<string, mixed>,
	 *   display: array<string, mixed>
	 * }
	 */
	public static function dctc_ai_get_public_frontend_settings() {
		$settings = self::dctc_ai_get_all_settings();
		$chatbot  = isset( $settings['chatbot'] ) && is_array( $settings['chatbot'] ) ? $settings['chatbot'] : array();
		$display  = isset( $settings['display'] ) && is_array( $settings['display'] ) ? $settings['display'] : array();

		$public_chatbot_keys = array(
			'bot_name',
			'primary_color',
			'greeting_msg',
			'bot_avatar',
			'bubble_style',
			'api_error_msg',
			'support_url',
			'pre_question_1',
			'pre_question_2',
			'pre_question_3',
			'pre_question_4',
			'pre_questions_bg_color',
			'pre_questions_text_color',
			'pre_questions_border_color',
			'pre_questions_border_radius',
			'save_chat',
			'ask_email',
			'enable_pre_questions',
			'show_bot_avatar_in_chat',
			'show_user_avatar_in_chat',
			'bot_icon_preset',
			'user_avatar',
			'user_icon_preset',
			'enable_uploads',
			'allowed_file_types',
			'excluded_file_types',
			'max_upload_size',
			'max_files_per_message',
			'store_chat_attachments',
			'action_buttons',
			'enable_lead_capture',
			'lead_trigger_type',
			'lead_trigger_delay',
			'lead_trigger_message_count',
			'lead_form_title',
			'lead_form_subtitle',
			'lead_fields',
			'lead_qualification_threshold',
			'enable_ai_intent_scoring',
			'enable_support_escalation',
			'auto_assign_support_tickets',
			'auto_pause_ai_on_ticket',
			'human_agent_max_wait_time',
			'human_agent_waiting_message',
			'enable_ai_tools',
			'enabled_tools',
			'enable_page_context',
			'enable_multilingual',
			'preferred_language',
			'visitor_language_override',
			'enable_voice_input',
			'enable_voice_output',
			'voice_language',
			'enable_vision_understanding',
		);

		$public_chatbot = array();
		foreach ( $public_chatbot_keys as $key ) {
			if ( array_key_exists( $key, $chatbot ) ) {
				$public_chatbot[ $key ] = $chatbot[ $key ];
			}
		}

		// Pull fallback contact channels from parent Click to Chat settings if blank in AI settings
		$parent_settings = get_option( 'dctc_settings', array() );
		if ( empty( $public_chatbot['handoff_whatsapp_number'] ) && ! empty( $parent_settings['whatsapp_value'] ) ) {
			$public_chatbot['handoff_whatsapp_number'] = $parent_settings['whatsapp_value'];
		}
		if ( empty( $public_chatbot['handoff_phone_number'] ) && ! empty( $parent_settings['phone_value'] ) ) {
			$public_chatbot['handoff_phone_number'] = $parent_settings['phone_value'];
		}
		if ( empty( $public_chatbot['handoff_email_address'] ) ) {
			$public_chatbot['handoff_email_address'] = ! empty( $parent_settings['email_value'] ) ? $parent_settings['email_value'] : get_option( 'admin_email' );
		}

		$is_within_hours                            = class_exists( 'DCTC_AI_Chat_Controller' )
			? DCTC_AI_Chat_Controller::is_within_business_hours( $chatbot )
			: true;
		$public_chatbot['is_within_business_hours'] = $is_within_hours;

		$is_admin     = current_user_can( 'manage_options' );
		$is_wc_active = class_exists( 'DCTC_AI_WooCommerce' ) && DCTC_AI_WooCommerce::is_active();

		return array(
			'chatbot'               => $public_chatbot,
			'display'               => $display,
			'has_api_key'           => DCTC_AI_Key_Store::has_configured_provider(),
			'is_admin'              => $is_admin,
			'is_woocommerce_active' => $is_wc_active,
			'settings_url'          => $is_admin ? admin_url( 'admin.php?page=dragwyb-click-to-chat-ai' ) : '',
		);
	}

	/**
	 * Persist Settings
	 *
	 * Single write path for the plugin's settings option, used in place of
	 * calling update_option() directly. Two things worth knowing about this
	 * option:
	 *
	 * - It's stored with autoload disabled. It's a fairly large array (all
	 *   provider/RAG/chatbot/MCP config) that's only actually needed on this
	 *   plugin's own admin screen and REST requests — update_option()'s
	 *   default of autoloading it would otherwise pull the whole blob into
	 *   the alloptions cache on every single request, including the public
	 *   frontend. (WordPress 6.6+ honors the autoload param here even when
	 *   the option already exists; on older versions this is a no-op and
	 *   the option keeps whatever autoload it was first created with.)
	 * - AI provider API keys (DCTC_AI_Key_Store::get_provider_key()) are stored
	 *   in this option in plaintext, the same way most WordPress plugins
	 *   store third-party credentials. They're read on every chat request to
	 *   authenticate outbound API calls, so keeping them in plaintext here
	 *   (rather than encrypted, as done for the MCP server API key via
	 * Single write path for the plugin's settings option, used in place of
	 * calling update_option() directly. Two things worth knowing about this
	 * option:
	 *
	 * - It's stored with autoload disabled. It's a fairly large array (all
	 *   provider/RAG/chatbot/MCP config) that's only actually needed on this
	 *   plugin's own admin screen and REST requests — update_option()'s
	 *   default of autoloading it would otherwise pull the whole blob into
	 *   the alloptions cache on every single request, including the public
	 *   frontend. (WordPress 6.6+ honors the autoload param here even when
	 *   the option already exists; on older versions this is a no-op and
	 *   the option keeps whatever autoload it was first created with.)
	 * - AI provider API keys (DCTC_AI_Key_Store::get_provider_key()) are stored
	 *   in this option in plaintext, the same way most WordPress plugins
	 *   store third-party credentials. They're read on every chat request to
	 *   authenticate outbound API calls, so keeping them in plaintext here
	 *   (rather than encrypted, as done for the MCP server API key via
	 *   DCTC_AI_Key_Store::encrypt_secret()) is a deliberate simplicity/
	 *   performance tradeoff, not an oversight — protecting them is a matter
	 *   of standard WP database access control, same as any other stored
	 *   credential.
	 *
	 * @param array $settings Full settings array to persist.
	 * @return void
	 */
	public static function dctc_ai_persist_settings( $settings ) {
		update_option( 'dctc_ai_chat_assistant_settings', $settings, false );
	}

	/**
	 * Get specific provider key.
	 *
	 * Thin proxy to DCTC_AI_Key_Store, kept here (and public static) because
	 * other classes across the plugin already call it as
	 * DCTC_AI_Settings_Handler::dctc_ai_get_provider_key().
	 *
	 * @param string $provider Identifier for the AI provider.
	 * @return string The raw API key string if available.
	 */
	public static function dctc_ai_get_provider_key( $provider ) {
		return DCTC_AI_Key_Store::get_provider_key( $provider );
	}

	/**
	 * Get the list of available models for an AI provider.
	 *
	 * Thin proxy to DCTC_AI_Key_Store, kept here (and public static) because
	 * other classes across the plugin already call it as
	 * DCTC_AI_Settings_Handler::dctc_ai_get_models().
	 *
	 * @param string $provider Provider identifier (e.g. 'openai').
	 * @return array Map of model ID => model display name.
	 */
	public static function dctc_ai_get_models( $provider ) {
		return DCTC_AI_Key_Store::get_models( $provider );
	}

	/**
	 * Save Chatbot Config Profile Settings
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Standard API response.
	 */
	public function dctc_ai_save_bot_settings( $request ) {
		$params = $request->get_json_params();

		// Clean knowledge URLs array safely.
		$urls = array();
		if ( isset( $params['knowledge_urls'] ) ) {
			foreach ( (array) $params['knowledge_urls'] as $url ) {
				$clean = esc_url_raw( $url );
				if ( ! empty( $clean ) ) {
					$urls[] = $clean;
				}
			}
		}

		// Clean training files safe ID array.
		$files = array();
		if ( isset( $params['training_files'] ) ) {
			foreach ( (array) $params['training_files'] as $file_id ) {
				$files[] = absint( $file_id );
			}
		}

		$settings         = self::dctc_ai_get_all_settings();
		$existing_chatbot = isset( $settings['chatbot'] ) ? $settings['chatbot'] : array();

		$chatbot_settings = array(
			'bot_name'                       => isset( $params['bot_name'] ) ? sanitize_text_field( $params['bot_name'] ) : ( isset( $existing_chatbot['bot_name'] ) ? $existing_chatbot['bot_name'] : 'Dragwyb AI' ),
			'primary_color'                  => isset( $params['primary_color'] ) ? sanitize_hex_color( $params['primary_color'] ) : ( isset( $existing_chatbot['primary_color'] ) ? $existing_chatbot['primary_color'] : '#6366f1' ),
			'greeting_msg'                   => isset( $params['greeting_msg'] ) ? sanitize_textarea_field( $params['greeting_msg'] ) : ( isset( $existing_chatbot['greeting_msg'] ) ? $existing_chatbot['greeting_msg'] : '' ),
			'bot_avatar'                     => isset( $params['bot_avatar'] ) ? esc_url_raw( $params['bot_avatar'] ) : ( isset( $existing_chatbot['bot_avatar'] ) ? $existing_chatbot['bot_avatar'] : '' ),
			'bubble_style'                   => isset( $params['bubble_style'] ) ? sanitize_text_field( $params['bubble_style'] ) : ( isset( $existing_chatbot['bubble_style'] ) ? $existing_chatbot['bubble_style'] : 'rounded' ),
			'api_error_msg'                  => isset( $params['api_error_msg'] ) ? sanitize_textarea_field( $params['api_error_msg'] ) : ( isset( $existing_chatbot['api_error_msg'] ) ? $existing_chatbot['api_error_msg'] : 'There is some error on server, please contact our [support agent]({support_url}).' ),
			'support_url'                    => isset( $params['support_url'] ) ? esc_url_raw( $params['support_url'] ) : ( isset( $existing_chatbot['support_url'] ) ? $existing_chatbot['support_url'] : home_url( '/support' ) ),
			'pre_question_1'                 => isset( $params['pre_question_1'] ) ? sanitize_text_field( $params['pre_question_1'] ) : ( isset( $existing_chatbot['pre_question_1'] ) ? $existing_chatbot['pre_question_1'] : '' ),
			'pre_question_2'                 => isset( $params['pre_question_2'] ) ? sanitize_text_field( $params['pre_question_2'] ) : ( isset( $existing_chatbot['pre_question_2'] ) ? $existing_chatbot['pre_question_2'] : '' ),
			'pre_question_3'                 => isset( $params['pre_question_3'] ) ? sanitize_text_field( $params['pre_question_3'] ) : ( isset( $existing_chatbot['pre_question_3'] ) ? $existing_chatbot['pre_question_3'] : '' ),
			'pre_question_4'                 => isset( $params['pre_question_4'] ) ? sanitize_text_field( $params['pre_question_4'] ) : ( isset( $existing_chatbot['pre_question_4'] ) ? $existing_chatbot['pre_question_4'] : '' ),
			'pre_questions_bg_color'         => isset( $params['pre_questions_bg_color'] ) ? sanitize_hex_color( $params['pre_questions_bg_color'] ) : ( isset( $existing_chatbot['pre_questions_bg_color'] ) ? $existing_chatbot['pre_questions_bg_color'] : '#ffffff' ),
			'pre_questions_text_color'       => isset( $params['pre_questions_text_color'] ) ? sanitize_hex_color( $params['pre_questions_text_color'] ) : ( isset( $existing_chatbot['pre_questions_text_color'] ) ? $existing_chatbot['pre_questions_text_color'] : '#475569' ),
			'pre_questions_border_color'     => isset( $params['pre_questions_border_color'] ) ? sanitize_hex_color( $params['pre_questions_border_color'] ) : ( isset( $existing_chatbot['pre_questions_border_color'] ) ? $existing_chatbot['pre_questions_border_color'] : '#e2e8f0' ),
			'pre_questions_border_radius'    => isset( $params['pre_questions_border_radius'] ) ? sanitize_text_field( $params['pre_questions_border_radius'] ) : ( isset( $existing_chatbot['pre_questions_border_radius'] ) ? $existing_chatbot['pre_questions_border_radius'] : 'rounded' ),
			'system_prompt'                  => isset( $params['system_prompt'] ) ? sanitize_textarea_field( $params['system_prompt'] ) : ( isset( $existing_chatbot['system_prompt'] ) ? $existing_chatbot['system_prompt'] : '' ),
			'temperature'                    => isset( $params['temperature'] ) ? floatval( $params['temperature'] ) : ( isset( $existing_chatbot['temperature'] ) ? floatval( $existing_chatbot['temperature'] ) : 0.7 ),
			'max_tokens'                     => isset( $params['max_tokens'] ) ? intval( $params['max_tokens'] ) : ( isset( $existing_chatbot['max_tokens'] ) ? intval( $existing_chatbot['max_tokens'] ) : 500 ),
			'save_chat'                      => isset( $params['save_chat'] ) ? (bool) $params['save_chat'] : ( isset( $existing_chatbot['save_chat'] ) ? (bool) $existing_chatbot['save_chat'] : false ),
			'chat_retention_days'            => isset( $params['chat_retention_days'] ) ? max( 0, intval( $params['chat_retention_days'] ) ) : ( isset( $existing_chatbot['chat_retention_days'] ) ? intval( $existing_chatbot['chat_retention_days'] ) : 0 ),
			'memory_window_size'             => isset( $params['memory_window_size'] ) ? min( 50, max( 2, intval( $params['memory_window_size'] ) ) ) : ( isset( $existing_chatbot['memory_window_size'] ) ? intval( $existing_chatbot['memory_window_size'] ) : 10 ),
			'ask_email'                      => isset( $params['ask_email'] ) ? (bool) $params['ask_email'] : ( isset( $existing_chatbot['ask_email'] ) ? (bool) $existing_chatbot['ask_email'] : false ),
			'enable_pre_questions'           => isset( $params['enable_pre_questions'] ) ? (bool) $params['enable_pre_questions'] : ( isset( $existing_chatbot['enable_pre_questions'] ) ? (bool) $existing_chatbot['enable_pre_questions'] : false ),
			'show_bot_avatar_in_chat'        => isset( $params['show_bot_avatar_in_chat'] ) ? (bool) $params['show_bot_avatar_in_chat'] : ( isset( $existing_chatbot['show_bot_avatar_in_chat'] ) ? (bool) $existing_chatbot['show_bot_avatar_in_chat'] : true ),
			'show_user_avatar_in_chat'       => isset( $params['show_user_avatar_in_chat'] ) ? (bool) $params['show_user_avatar_in_chat'] : ( isset( $existing_chatbot['show_user_avatar_in_chat'] ) ? (bool) $existing_chatbot['show_user_avatar_in_chat'] : true ),
			'show_sources'                   => isset( $params['show_sources'] ) ? (bool) $params['show_sources'] : ( isset( $existing_chatbot['show_sources'] ) ? (bool) $existing_chatbot['show_sources'] : true ),
			'bot_icon_preset'                => isset( $params['bot_icon_preset'] ) ? sanitize_text_field( $params['bot_icon_preset'] ) : ( isset( $existing_chatbot['bot_icon_preset'] ) ? $existing_chatbot['bot_icon_preset'] : 'bot' ),
			'user_avatar'                    => isset( $params['user_avatar'] ) ? esc_url_raw( $params['user_avatar'] ) : ( isset( $existing_chatbot['user_avatar'] ) ? $existing_chatbot['user_avatar'] : '' ),
			'user_icon_preset'               => isset( $params['user_icon_preset'] ) ? sanitize_text_field( $params['user_icon_preset'] ) : ( isset( $existing_chatbot['user_icon_preset'] ) ? $existing_chatbot['user_icon_preset'] : 'user' ),
			'action_buttons'                 => isset( $params['action_buttons'] ) && is_array( $params['action_buttons'] ) ? array_values(
				array_filter(
					array_map(
						function ( $btn ) {
							if ( ! is_array( $btn ) || empty( $btn['label'] ) ) {
								return null;
							}
							return array(
								'id'     => isset( $btn['id'] ) ? sanitize_text_field( $btn['id'] ) : uniqid( 'btn_' ),
								'label'  => sanitize_text_field( $btn['label'] ),
								'url'    => isset( $btn['url'] ) ? esc_url_raw( $btn['url'] ) : '',
								'target' => ( isset( $btn['target'] ) && $btn['target'] === '_self' ) ? '_self' : '_blank',
								'type'   => isset( $btn['type'] ) ? sanitize_text_field( $btn['type'] ) : 'link',
							);
						},
						$params['action_buttons']
					)
				)
			) : ( isset( $existing_chatbot['action_buttons'] ) ? $existing_chatbot['action_buttons'] : array() ),
			'enable_uploads'                 => isset( $params['enable_uploads'] ) ? (bool) $params['enable_uploads'] : ( isset( $existing_chatbot['enable_uploads'] ) ? (bool) $existing_chatbot['enable_uploads'] : false ),
			'allowed_file_types'             => isset( $params['allowed_file_types'] ) ? sanitize_text_field( $params['allowed_file_types'] ) : ( isset( $existing_chatbot['allowed_file_types'] ) ? $existing_chatbot['allowed_file_types'] : 'jpg, jpeg, png, webp, gif, pdf, txt, doc, docx' ),
			'excluded_file_types'            => isset( $params['excluded_file_types'] ) ? sanitize_text_field( $params['excluded_file_types'] ) : ( isset( $existing_chatbot['excluded_file_types'] ) ? $existing_chatbot['excluded_file_types'] : 'php, php3, php4, php5, phtml, phar, cgi, pl, py, sh, exe, bat, cmd, js, html, htm, svg' ),
			'max_upload_size'                => isset( $params['max_upload_size'] ) ? max( 1, min( 50, intval( $params['max_upload_size'] ) ) ) : ( isset( $existing_chatbot['max_upload_size'] ) ? intval( $existing_chatbot['max_upload_size'] ) : 5 ),
			'max_files_per_message'          => isset( $params['max_files_per_message'] ) ? max( 1, min( 10, intval( $params['max_files_per_message'] ) ) ) : ( isset( $existing_chatbot['max_files_per_message'] ) ? intval( $existing_chatbot['max_files_per_message'] ) : 3 ),
			'store_chat_attachments'         => isset( $params['store_chat_attachments'] ) && in_array( $params['store_chat_attachments'], array( 'do_not_store', 'temp', 'save_with_history' ), true ) ? $params['store_chat_attachments'] : ( isset( $existing_chatbot['store_chat_attachments'] ) ? $existing_chatbot['store_chat_attachments'] : 'temp' ),
			'default_provider'               => isset( $params['default_provider'] ) ? sanitize_text_field( $params['default_provider'] ) : ( isset( $existing_chatbot['default_provider'] ) ? $existing_chatbot['default_provider'] : 'openai' ),
			'fallback_provider'              => isset( $params['fallback_provider'] ) ? sanitize_text_field( $params['fallback_provider'] ) : ( isset( $existing_chatbot['fallback_provider'] ) ? $existing_chatbot['fallback_provider'] : '' ),
			'fallback_model'                 => isset( $params['fallback_model'] ) ? sanitize_text_field( $params['fallback_model'] ) : ( isset( $existing_chatbot['fallback_model'] ) ? $existing_chatbot['fallback_model'] : '' ),
			'enable_failover'                => isset( $params['enable_failover'] ) ? (bool) $params['enable_failover'] : ( isset( $existing_chatbot['enable_failover'] ) ? (bool) $existing_chatbot['enable_failover'] : true ),
			'knowledge_text'                 => isset( $params['knowledge_text'] ) ? sanitize_textarea_field( $params['knowledge_text'] ) : ( isset( $existing_chatbot['knowledge_text'] ) ? $existing_chatbot['knowledge_text'] : '' ),
			'knowledge_urls'                 => isset( $params['knowledge_urls'] ) ? $urls : ( isset( $existing_chatbot['knowledge_urls'] ) ? $existing_chatbot['knowledge_urls'] : array() ),
			'training_files'                 => isset( $params['training_files'] ) ? $files : ( isset( $existing_chatbot['training_files'] ) ? $existing_chatbot['training_files'] : array() ),
			'rate_limit_per_minute'          => isset( $params['rate_limit_per_minute'] ) ? max( 1, min( 300, intval( $params['rate_limit_per_minute'] ) ) ) : ( isset( $existing_chatbot['rate_limit_per_minute'] ) ? intval( $existing_chatbot['rate_limit_per_minute'] ) : 20 ),
			'enable_usage_limits'            => isset( $params['enable_usage_limits'] ) ? (bool) $params['enable_usage_limits'] : ( isset( $existing_chatbot['enable_usage_limits'] ) ? (bool) $existing_chatbot['enable_usage_limits'] : true ),
			'visitor_daily_message_limit'    => isset( $params['visitor_daily_message_limit'] ) ? max( 0, intval( $params['visitor_daily_message_limit'] ) ) : ( isset( $existing_chatbot['visitor_daily_message_limit'] ) ? intval( $existing_chatbot['visitor_daily_message_limit'] ) : 50 ),
			'monthly_request_budget'         => isset( $params['monthly_request_budget'] ) ? max( 0, intval( $params['monthly_request_budget'] ) ) : ( isset( $existing_chatbot['monthly_request_budget'] ) ? intval( $existing_chatbot['monthly_request_budget'] ) : 5000 ),
			'budget_limit_message'           => isset( $params['budget_limit_message'] ) ? sanitize_textarea_field( $params['budget_limit_message'] ) : ( isset( $existing_chatbot['budget_limit_message'] ) ? $existing_chatbot['budget_limit_message'] : 'You have reached the daily chat limit. Please connect with our team directly via WhatsApp or Support.' ),
			'enable_budget_email_alerts'     => isset( $params['enable_budget_email_alerts'] ) ? (bool) $params['enable_budget_email_alerts'] : ( isset( $existing_chatbot['enable_budget_email_alerts'] ) ? (bool) $existing_chatbot['enable_budget_email_alerts'] : true ),
			'alert_email'                    => isset( $params['alert_email'] ) ? sanitize_email( $params['alert_email'] ) : ( isset( $existing_chatbot['alert_email'] ) ? $existing_chatbot['alert_email'] : '' ),
			'enable_error_log'               => isset( $params['enable_error_log'] ) ? (bool) $params['enable_error_log'] : ( isset( $existing_chatbot['enable_error_log'] ) ? (bool) $existing_chatbot['enable_error_log'] : true ),
			'error_log_retention_days'       => isset( $params['error_log_retention_days'] ) ? max( 0, intval( $params['error_log_retention_days'] ) ) : ( isset( $existing_chatbot['error_log_retention_days'] ) ? intval( $existing_chatbot['error_log_retention_days'] ) : 0 ),
			'enable_lead_capture'            => isset( $params['enable_lead_capture'] ) ? (bool) $params['enable_lead_capture'] : ( isset( $existing_chatbot['enable_lead_capture'] ) ? (bool) $existing_chatbot['enable_lead_capture'] : false ),
			'lead_trigger_type'              => isset( $params['lead_trigger_type'] ) && in_array( $params['lead_trigger_type'], array( 'manual', 'time_delay', 'message_count', 'intent' ), true ) ? $params['lead_trigger_type'] : ( isset( $existing_chatbot['lead_trigger_type'] ) ? $existing_chatbot['lead_trigger_type'] : 'manual' ),
			'lead_trigger_delay'             => isset( $params['lead_trigger_delay'] ) ? max( 5, min( 300, intval( $params['lead_trigger_delay'] ) ) ) : ( isset( $existing_chatbot['lead_trigger_delay'] ) ? intval( $existing_chatbot['lead_trigger_delay'] ) : 30 ),
			'lead_trigger_message_count'     => isset( $params['lead_trigger_message_count'] ) ? max( 1, min( 20, intval( $params['lead_trigger_message_count'] ) ) ) : ( isset( $existing_chatbot['lead_trigger_message_count'] ) ? intval( $existing_chatbot['lead_trigger_message_count'] ) : 3 ),
			'lead_form_title'                => isset( $params['lead_form_title'] ) ? sanitize_text_field( $params['lead_form_title'] ) : ( isset( $existing_chatbot['lead_form_title'] ) ? $existing_chatbot['lead_form_title'] : 'Contact Our Team' ),
			'lead_form_subtitle'             => isset( $params['lead_form_subtitle'] ) ? sanitize_textarea_field( $params['lead_form_subtitle'] ) : ( isset( $existing_chatbot['lead_form_subtitle'] ) ? $existing_chatbot['lead_form_subtitle'] : 'Leave your details and our team will get back to you shortly.' ),
			'lead_fields'                    => isset( $params['lead_fields'] ) && is_array( $params['lead_fields'] ) ? array(
				'name'         => isset( $params['lead_fields']['name'] ) ? (bool) $params['lead_fields']['name'] : true,
				'email'        => isset( $params['lead_fields']['email'] ) ? (bool) $params['lead_fields']['email'] : true,
				'phone'        => isset( $params['lead_fields']['phone'] ) ? (bool) $params['lead_fields']['phone'] : true,
				'company'      => isset( $params['lead_fields']['company'] ) ? (bool) $params['lead_fields']['company'] : false,
				'company_size' => isset( $params['lead_fields']['company_size'] ) ? (bool) $params['lead_fields']['company_size'] : false,
				'budget'       => isset( $params['lead_fields']['budget'] ) ? (bool) $params['lead_fields']['budget'] : true,
				'timeline'     => isset( $params['lead_fields']['timeline'] ) ? (bool) $params['lead_fields']['timeline'] : true,
				'interest'     => isset( $params['lead_fields']['interest'] ) ? (bool) $params['lead_fields']['interest'] : true,
				'requirement'  => isset( $params['lead_fields']['requirement'] ) ? (bool) $params['lead_fields']['requirement'] : true,
			) : ( isset( $existing_chatbot['lead_fields'] ) ? $existing_chatbot['lead_fields'] : array(
				'name'         => true,
				'email'        => true,
				'phone'        => true,
				'company'      => false,
				'company_size' => false,
				'budget'       => true,
				'timeline'     => true,
				'interest'     => true,
				'requirement'  => true,
			) ),
			'lead_qualification_threshold'   => isset( $params['lead_qualification_threshold'] ) ? max( 0, min( 100, intval( $params['lead_qualification_threshold'] ) ) ) : ( isset( $existing_chatbot['lead_qualification_threshold'] ) ? intval( $existing_chatbot['lead_qualification_threshold'] ) : 70 ),
			'enable_ai_intent_scoring'       => isset( $params['enable_ai_intent_scoring'] ) ? (bool) $params['enable_ai_intent_scoring'] : ( isset( $existing_chatbot['enable_ai_intent_scoring'] ) ? (bool) $existing_chatbot['enable_ai_intent_scoring'] : true ),
			'enable_lead_email_alerts'       => isset( $params['enable_lead_email_alerts'] ) ? (bool) $params['enable_lead_email_alerts'] : ( isset( $existing_chatbot['enable_lead_email_alerts'] ) ? (bool) $existing_chatbot['enable_lead_email_alerts'] : true ),
			'lead_notification_email'        => isset( $params['lead_notification_email'] ) ? sanitize_email( $params['lead_notification_email'] ) : ( isset( $existing_chatbot['lead_notification_email'] ) ? $existing_chatbot['lead_notification_email'] : '' ),
			'enable_support_escalation'      => isset( $params['enable_support_escalation'] ) ? (bool) $params['enable_support_escalation'] : ( isset( $existing_chatbot['enable_support_escalation'] ) ? (bool) $existing_chatbot['enable_support_escalation'] : true ),
			'auto_assign_support_tickets'    => isset( $params['auto_assign_support_tickets'] ) ? (bool) $params['auto_assign_support_tickets'] : ( isset( $existing_chatbot['auto_assign_support_tickets'] ) ? (bool) $existing_chatbot['auto_assign_support_tickets'] : true ),
			'auto_pause_ai_on_ticket'        => isset( $params['auto_pause_ai_on_ticket'] ) ? (bool) $params['auto_pause_ai_on_ticket'] : ( isset( $existing_chatbot['auto_pause_ai_on_ticket'] ) ? (bool) $existing_chatbot['auto_pause_ai_on_ticket'] : true ),
			'human_agent_max_wait_time'      => isset( $params['human_agent_max_wait_time'] ) ? max( 10, min( 1800, intval( $params['human_agent_max_wait_time'] ) ) ) : ( isset( $existing_chatbot['human_agent_max_wait_time'] ) ? intval( $existing_chatbot['human_agent_max_wait_time'] ) : 60 ),
			'human_agent_waiting_message'    => isset( $params['human_agent_waiting_message'] ) ? sanitize_textarea_field( $params['human_agent_waiting_message'] ) : ( isset( $existing_chatbot['human_agent_waiting_message'] ) ? $existing_chatbot['human_agent_waiting_message'] : 'Sorry to keep you waiting...' ),
			'enable_ai_tools'                => isset( $params['enable_ai_tools'] ) ? (bool) $params['enable_ai_tools'] : ( isset( $existing_chatbot['enable_ai_tools'] ) ? (bool) $existing_chatbot['enable_ai_tools'] : true ),
			'enabled_tools'                  => isset( $params['enabled_tools'] ) && is_array( $params['enabled_tools'] ) ? array_values( array_map( 'sanitize_key', $params['enabled_tools'] ) ) : ( isset( $existing_chatbot['enabled_tools'] ) ? $existing_chatbot['enabled_tools'] : array( 'search_products', 'get_order_status', 'create_support_ticket', 'book_appointment', 'search_website_content' ) ),
			'workflow_webhook_url'           => isset( $params['workflow_webhook_url'] ) ? esc_url_raw( $params['workflow_webhook_url'] ) : ( isset( $existing_chatbot['workflow_webhook_url'] ) ? $existing_chatbot['workflow_webhook_url'] : '' ),
			'ticket_notification_email'      => isset( $params['ticket_notification_email'] ) ? sanitize_email( $params['ticket_notification_email'] ) : ( isset( $existing_chatbot['ticket_notification_email'] ) ? $existing_chatbot['ticket_notification_email'] : '' ),
			'appointment_notification_email' => isset( $params['appointment_notification_email'] ) ? sanitize_email( $params['appointment_notification_email'] ) : ( isset( $existing_chatbot['appointment_notification_email'] ) ? $existing_chatbot['appointment_notification_email'] : '' ),
			'enable_page_context'            => isset( $params['enable_page_context'] ) ? (bool) $params['enable_page_context'] : ( isset( $existing_chatbot['enable_page_context'] ) ? (bool) $existing_chatbot['enable_page_context'] : true ),
			'enable_multilingual'            => isset( $params['enable_multilingual'] ) ? (bool) $params['enable_multilingual'] : ( isset( $existing_chatbot['enable_multilingual'] ) ? (bool) $existing_chatbot['enable_multilingual'] : true ),
			'preferred_language'             => isset( $params['preferred_language'] ) ? sanitize_text_field( $params['preferred_language'] ) : ( isset( $existing_chatbot['preferred_language'] ) ? $existing_chatbot['preferred_language'] : 'auto' ),
			'visitor_language_override'      => isset( $params['visitor_language_override'] ) ? (bool) $params['visitor_language_override'] : ( isset( $existing_chatbot['visitor_language_override'] ) ? (bool) $existing_chatbot['visitor_language_override'] : true ),
			'enable_voice_input'             => isset( $params['enable_voice_input'] ) ? (bool) $params['enable_voice_input'] : ( isset( $existing_chatbot['enable_voice_input'] ) ? (bool) $existing_chatbot['enable_voice_input'] : true ),
			'enable_voice_output'            => isset( $params['enable_voice_output'] ) ? (bool) $params['enable_voice_output'] : ( isset( $existing_chatbot['enable_voice_output'] ) ? (bool) $existing_chatbot['enable_voice_output'] : false ),
			'voice_language'                 => isset( $params['voice_language'] ) ? sanitize_text_field( $params['voice_language'] ) : ( isset( $existing_chatbot['voice_language'] ) ? $existing_chatbot['voice_language'] : 'auto' ),
			'enable_vision_understanding'    => isset( $params['enable_vision_understanding'] ) ? (bool) $params['enable_vision_understanding'] : ( isset( $existing_chatbot['enable_vision_understanding'] ) ? (bool) $existing_chatbot['enable_vision_understanding'] : true ),
			'support_ticket_msg'             => isset( $params['support_ticket_msg'] ) ? sanitize_textarea_field( $params['support_ticket_msg'] ) : ( isset( $existing_chatbot['support_ticket_msg'] ) ? $existing_chatbot['support_ticket_msg'] : 'I have logged your inquiry with our support team and created a support ticket for this session. A support specialist will review your message and assist you shortly.' ),
			'order_tracking_prompt_msg'      => isset( $params['order_tracking_prompt_msg'] ) ? sanitize_textarea_field( $params['order_tracking_prompt_msg'] ) : ( isset( $existing_chatbot['order_tracking_prompt_msg'] ) ? $existing_chatbot['order_tracking_prompt_msg'] : 'Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.' ),
			'order_tracking_login_msg'       => isset( $params['order_tracking_login_msg'] ) ? sanitize_textarea_field( $params['order_tracking_login_msg'] ) : ( isset( $existing_chatbot['order_tracking_login_msg'] ) ? $existing_chatbot['order_tracking_login_msg'] : 'To securely track your order status, please [log in to your account]({login_url}) first.' ),
			'order_mismatch_msg'             => isset( $params['order_mismatch_msg'] ) ? sanitize_textarea_field( $params['order_mismatch_msg'] ) : ( isset( $existing_chatbot['order_mismatch_msg'] ) ? $existing_chatbot['order_mismatch_msg'] : 'This order was purchased with a different email address. For privacy and security reasons, order details cannot be displayed.' ),
			'no_data_message'                => isset( $params['no_data_message'] ) ? sanitize_textarea_field( $params['no_data_message'] ) : ( isset( $existing_chatbot['no_data_message'] ) ? $existing_chatbot['no_data_message'] : 'I don\'t have information about your question in my knowledge base. Please rephrase or ask about topics I have knowledge of.' ),
		);

		$settings['chatbot'] = $chatbot_settings;
		self::dctc_ai_persist_settings( $settings );

		if ( class_exists( 'DCTC_Error_Logger' ) ) {
			DCTC_Error_Logger::set_retention_days( $chatbot_settings['error_log_retention_days'] );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => esc_html__( 'Chatbot settings saved successfully!', 'dragwyb-click-to-chat' ),
			),
			200
		);
	}

	/**
	 * Save Display Control Settings
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Standard API response.
	 */
	public function dctc_ai_save_display_settings( $request ) {
		$params = $request->get_json_params();

		$allowed_positions = array( 'bottom-right', 'bottom-left', 'custom' );
		$position          = isset( $params['position'] ) ? sanitize_text_field( $params['position'] ) : 'bottom-right';
		if ( ! in_array( $position, $allowed_positions, true ) ) {
			$position = 'bottom-right';
		}

		$allowed_units = array( 'px', 'rem', 'em', '%' );
		$sanitize_unit = static function ( $value, $fallback = 'px' ) use ( $allowed_units ) {
			$value = sanitize_text_field( (string) $value );
			return in_array( $value, $allowed_units, true ) ? $value : $fallback;
		};

		$widget_size_unit = $sanitize_unit( $params['widget_size_unit'] ?? 'px' );
		$widget_size      = isset( $params['widget_size'] ) ? floatval( $params['widget_size'] ) : 64;
		if ( $widget_size < 24 ) {
			$widget_size = 24;
		} elseif ( $widget_size > 120 ) {
			$widget_size = 120;
		}

		$custom_vertical_align = isset( $params['custom_vertical_align'] ) ? sanitize_text_field( $params['custom_vertical_align'] ) : 'bottom';
		if ( ! in_array( $custom_vertical_align, array( 'top', 'bottom' ), true ) ) {
			$custom_vertical_align = 'bottom';
		}

		$custom_side = isset( $params['custom_side'] ) ? sanitize_text_field( $params['custom_side'] ) : 'right';
		if ( ! in_array( $custom_side, array( 'left', 'right' ), true ) ) {
			$custom_side = 'right';
		}

		$custom_vertical   = isset( $params['custom_vertical'] ) ? max( 0, floatval( $params['custom_vertical'] ) ) : 24;
		$custom_horizontal = isset( $params['custom_horizontal'] ) ? max( 0, floatval( $params['custom_horizontal'] ) ) : 24;

		$allowed_target_devices = array( 'all', 'desktop_only', 'mobile_only' );
		$target_devices         = isset( $params['target_devices'] ) && in_array( $params['target_devices'], $allowed_target_devices, true ) ? $params['target_devices'] : 'all';

		$allowed_target_users = array( 'all', 'logged_in', 'guests' );
		$target_users         = isset( $params['target_users'] ) && in_array( $params['target_users'], $allowed_target_users, true ) ? $params['target_users'] : 'all';

		$allowed_trigger_actions = array( 'show_bubble', 'open_chat' );
		$trigger_action          = isset( $params['trigger_action'] ) && in_array( $params['trigger_action'], $allowed_trigger_actions, true ) ? $params['trigger_action'] : 'show_bubble';

		$display_settings = array(
			'entire_site'              => isset( $params['entire_site'] ) ? (bool) $params['entire_site'] : false,
			'exclude_pages'            => isset( $params['exclude_pages'] ) ? sanitize_text_field( $params['exclude_pages'] ) : '',
			'position'                 => $position,
			'widget_size'              => $widget_size,
			'widget_size_unit'         => $widget_size_unit,
			'custom_vertical_align'    => $custom_vertical_align,
			'custom_vertical'          => $custom_vertical,
			'custom_vertical_unit'     => $sanitize_unit( $params['custom_vertical_unit'] ?? 'px' ),
			'custom_side'              => $custom_side,
			'custom_horizontal'        => $custom_horizontal,
			'custom_horizontal_unit'   => $sanitize_unit( $params['custom_horizontal_unit'] ?? 'px' ),
			'show_on_mobile'           => isset( $params['show_on_mobile'] ) ? (bool) $params['show_on_mobile'] : true,
			'trigger_type'             => isset( $params['trigger_type'] ) ? sanitize_text_field( $params['trigger_type'] ) : 'click',
			'trigger_delay'            => isset( $params['trigger_delay'] ) ? intval( $params['trigger_delay'] ) : 5,
			'time_delay'               => isset( $params['time_delay'] ) ? max( 0, min( 60, intval( $params['time_delay'] ) ) ) : 0,
			'launcher_text'            => isset( $params['launcher_text'] ) ? sanitize_text_field( $params['launcher_text'] ) : 'Chat with us',
			'assistant_icon'           => isset( $params['assistant_icon'] ) ? esc_url_raw( $params['assistant_icon'] ) : '',
			'launcher_icon_preset'     => isset( $params['launcher_icon_preset'] ) ? sanitize_text_field( $params['launcher_icon_preset'] ) : 'chat',
			'enable_smart_triggers'    => isset( $params['enable_smart_triggers'] ) ? (bool) $params['enable_smart_triggers'] : false,
			'trigger_scroll_depth'     => isset( $params['trigger_scroll_depth'] ) ? max( 10, min( 100, intval( $params['trigger_scroll_depth'] ) ) ) : 50,
			'trigger_exit_intent'      => isset( $params['trigger_exit_intent'] ) ? (bool) $params['trigger_exit_intent'] : false,
			'trigger_inactivity'       => isset( $params['trigger_inactivity'] ) ? max( 5, min( 300, intval( $params['trigger_inactivity'] ) ) ) : 30,
			'trigger_action'           => $trigger_action,
			'proactive_bubble_message' => isset( $params['proactive_bubble_message'] ) ? sanitize_text_field( $params['proactive_bubble_message'] ) : '👋 Hi there! Have a question about this page? Let me know if I can help!',
			'target_devices'           => $target_devices,
			'target_users'             => $target_users,
			'url_rules'                => isset( $params['url_rules'] ) ? sanitize_textarea_field( $params['url_rules'] ) : '',
		);

		$settings            = self::dctc_ai_get_all_settings();
		$settings['display'] = $display_settings;
		self::dctc_ai_persist_settings( $settings );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => esc_html__( 'Display settings saved successfully!', 'dragwyb-click-to-chat' ),
			),
			200
		);
	}

	/**
	 * Complete full-plugin unified onboarding wizard.
	 * Persists settings for Social Chat, AI Assistant, and Support Center,
	 * creates database tables for Support Center on demand, auto-provisions
	 * support portal page if requested, and marks onboarding completed.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_complete_onboarding( $request ) {
		$params = $request->get_json_params() ?: array();

		$social_data  = isset( $params['social_chat'] ) && is_array( $params['social_chat'] ) ? $params['social_chat'] : array();
		$ai_data      = isset( $params['ai_assistant'] ) && is_array( $params['ai_assistant'] ) ? $params['ai_assistant'] : array();
		$support_data = isset( $params['support_center'] ) && is_array( $params['support_center'] ) ? $params['support_center'] : array();

		// 1. Handle Social Chat Settings
		$social_settings = get_option( 'dctc_settings', array() );
		if ( ! is_array( $social_settings ) ) {
			$social_settings = array();
		}

		$social_enabled                      = ! empty( $social_data['enabled'] );
		$social_settings['channels_enabled'] = $social_enabled ? '1' : '0';

		if ( isset( $social_data['whatsapp_enabled'] ) ) {
			$social_settings['whatsapp_enabled'] = ! empty( $social_data['whatsapp_enabled'] ) ? '1' : '0';
		}
		if ( isset( $social_data['whatsapp_number'] ) ) {
			$social_settings['whatsapp_value'] = sanitize_text_field( $social_data['whatsapp_number'] );
		}
		if ( isset( $social_data['whatsapp_message'] ) ) {
			$social_settings['whatsapp_message'] = sanitize_text_field( $social_data['whatsapp_message'] );
		}

		if ( isset( $social_data['phone_enabled'] ) ) {
			$social_settings['phone_enabled'] = ! empty( $social_data['phone_enabled'] ) ? '1' : '0';
		}
		if ( isset( $social_data['phone_number'] ) ) {
			$social_settings['phone_value'] = sanitize_text_field( $social_data['phone_number'] );
		}

		if ( isset( $social_data['email_enabled'] ) ) {
			$social_settings['email_enabled'] = ! empty( $social_data['email_enabled'] ) ? '1' : '0';
		}
		if ( isset( $social_data['email_address'] ) ) {
			$social_settings['email_value'] = sanitize_email( $social_data['email_address'] );
		}

		if ( isset( $social_data['custom_link_enabled'] ) ) {
			$social_settings['contact_enabled'] = ! empty( $social_data['custom_link_enabled'] ) ? '1' : '0';
		}
		if ( isset( $social_data['custom_link_url'] ) ) {
			$social_settings['contact_value'] = esc_url_raw( $social_data['custom_link_url'] );
		}

		$ai_enabled = ! empty( $ai_data['enabled'] );

		// If user enables both social chat widget and AI assistant, default social chat widget position to left
		if ( $social_enabled && $ai_enabled ) {
			$social_settings['widget_position'] = 'left';
			$social_settings['position']        = 'left';
		} elseif ( isset( $social_data['widget_position'] ) && in_array( $social_data['widget_position'], array( 'left', 'right', 'custom' ), true ) ) {
			$social_settings['widget_position'] = sanitize_text_field( $social_data['widget_position'] );
		}

		update_option( 'dctc_settings', $social_settings );
		update_option( 'dragwyb_click_to_chat_settings', $social_settings );

		// 2. Handle AI Assistant Settings
		$ai_settings = self::dctc_ai_get_all_settings();

		if ( $ai_enabled ) {
			$ai_settings['display']['entire_site'] = true;

			if ( ! empty( $ai_data['provider'] ) ) {
				$ai_settings['chatbot']['default_provider'] = sanitize_key( $ai_data['provider'] );
			}
			if ( ! empty( $ai_data['model'] ) ) {
				$ai_settings['chatbot']['default_model'] = sanitize_text_field( $ai_data['model'] );
			}
			if ( ! empty( $ai_data['assistant_name'] ) ) {
				$ai_settings['chatbot']['bot_name'] = sanitize_text_field( $ai_data['assistant_name'] );
			}
			if ( ! empty( $ai_data['welcome_message'] ) ) {
				$ai_settings['chatbot']['welcome_message'] = sanitize_textarea_field( $ai_data['welcome_message'] );
			}

			// Store API key if provided and not masked
			if ( ! empty( $ai_data['api_key'] ) && ! empty( $ai_data['provider'] ) ) {
				$raw_key = trim( $ai_data['api_key'] );
				if ( strpos( $raw_key, '****' ) === false && strlen( $raw_key ) > 3 ) {
					$this->key_store->save_provider_key( sanitize_key( $ai_data['provider'] ), $raw_key );
				}
			}
		} else {
			$ai_settings['display']['entire_site'] = false;
		}

		update_option( 'dctc_ai_chat_assistant_settings', $ai_settings );

		// 3. Handle Support Center Settings & Tables
		$support_enabled  = ! empty( $support_data['enabled'] );
		$support_settings = get_option( 'dctc_support_settings', array() );
		if ( ! is_array( $support_settings ) ) {
			$support_settings = array();
		}

		$created_portal_url = '';
		if ( $support_enabled ) {
			$support_settings['enabled']          = true;
			$support_settings['default_priority'] = ! empty( $support_data['default_priority'] ) ? sanitize_key( $support_data['default_priority'] ) : 'normal';
			$support_settings['support_email']    = ! empty( $support_data['support_email'] ) ? sanitize_email( $support_data['support_email'] ) : get_option( 'admin_email' );
			$support_settings['customer_portal']  = ! empty( $support_data['customer_portal'] );
			if ( ! empty( $support_data['default_category'] ) ) {
				$support_settings['default_category'] = sanitize_text_field( $support_data['default_category'] );
			}

			// Provision Support DB Tables on demand
			if ( file_exists( DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-db.php';
				\DCTC_Support_DB::create_tables();
			}

			// Auto-create Support Portal Page if requested
			if ( ! empty( $support_data['auto_create_portal_page'] ) ) {
				$existing_page_id = get_option( 'dctc_support_portal_page_id' );
				if ( ! $existing_page_id || ! get_post( $existing_page_id ) ) {
					$page_obj = get_page_by_path( 'support-portal' );
					if ( $page_obj ) {
						$existing_page_id = $page_obj->ID;
					} else {
						$new_page_id = wp_insert_post(
							array(
								'post_title'   => 'Support Portal',
								'post_name'    => 'support-portal',
								'post_content' => '<!-- wp:shortcode -->[dctc_support_portal]<!-- /wp:shortcode -->',
								'post_status'  => 'publish',
								'post_type'    => 'page',
							)
						);
						if ( ! is_wp_error( $new_page_id ) && $new_page_id > 0 ) {
							$existing_page_id = $new_page_id;
						}
					}
				}
				if ( $existing_page_id ) {
					update_option( 'dctc_support_portal_page_id', $existing_page_id );
					$created_portal_url = get_permalink( $existing_page_id );
				}
			}
		} else {
			$support_settings['enabled'] = false;
		}

		update_option( 'dctc_support_settings', $support_settings );

		return new \WP_REST_Response(
			array(
				'success'         => true,
				'message'         => esc_html__( 'Setup completed successfully!', 'dragwyb-click-to-chat' ),
				'portal_url'      => $created_portal_url,
				'social_enabled'  => $social_enabled,
				'ai_enabled'      => $ai_enabled,
				'support_enabled' => $support_enabled,
			),
			200
		);
	}

	/**
	 * Skip onboarding wizard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_skip_onboarding( $request ) {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => esc_html__( 'Onboarding skipped.', 'dragwyb-click-to-chat' ),
			),
			200
		);
	}

	/**
	 * Mark the first-run setup wizard status (legacy endpoint compatibility).
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function dctc_ai_update_setup_wizard_status( $request ) {
		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Upload Permission Check
	 *
	 * Allows admins or public chat users when enable_uploads is active.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool True if authorized to upload.
	 */
	public function dctc_ai_permission_upload( $request ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$settings = self::dctc_ai_get_all_settings();
		if ( empty( $settings['chatbot']['enable_uploads'] ) ) {
			return false;
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Handle a file upload via the WordPress media library with complete validation.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Uploaded attachment metadata.
	 */
	public function dctc_ai_upload_file( $request ) {
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['file'] ) || ! is_array( $_FILES['file'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'INVALID_FILE',
					'message' => esc_html__( 'No file was uploaded.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Check PHP upload error code
		if ( isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'dragwyb-click-to-chat' ),
				UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the maximum file size specified.', 'dragwyb-click-to-chat' ),
				UPLOAD_ERR_PARTIAL    => __( 'The file was only partially uploaded.', 'dragwyb-click-to-chat' ),
				UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'dragwyb-click-to-chat' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.', 'dragwyb-click-to-chat' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.', 'dragwyb-click-to-chat' ),
				UPLOAD_ERR_EXTENSION  => __( 'A PHP extension stopped the file upload.', 'dragwyb-click-to-chat' ),
			);
			$msg            = $error_messages[ $file['error'] ] ?? __( 'File upload error occurred.', 'dragwyb-click-to-chat' );
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'UPLOAD_FAILED',
					'message' => esc_html( $msg ),
				),
				400
			);
		}

		$settings     = self::dctc_ai_get_all_settings();
		$bot_settings = isset( $settings['chatbot'] ) && is_array( $settings['chatbot'] ) ? $settings['chatbot'] : array();

		// Max size check (effective limit is lower of plugin setting and server max)
		$plugin_max_mb       = isset( $bot_settings['max_upload_size'] ) ? max( 1, intval( $bot_settings['max_upload_size'] ) ) : 5;
		$server_max_bytes    = wp_max_upload_size();
		$plugin_max_bytes    = $plugin_max_mb * 1024 * 1024;
		$effective_max_bytes = min( $plugin_max_bytes, $server_max_bytes );

		if ( isset( $file['size'] ) && $file['size'] > $effective_max_bytes ) {
			$effective_mb = round( $effective_max_bytes / ( 1024 * 1024 ), 1 );
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'FILE_TOO_LARGE',
					'message' => sprintf(
						/* translators: %s: Maximum allowed file size in MB */
						esc_html__( 'This file is too large. Maximum allowed size is %s MB.', 'dragwyb-click-to-chat' ),
						$effective_mb
					),
				),
				400
			);
		}

		// Filename validation and sanitization
		$raw_name = isset( $file['name'] ) ? wp_unslash( $file['name'] ) : '';
		if ( empty( $raw_name ) || strpos( $raw_name, "\0" ) !== false ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'INVALID_FILENAME',
					'message' => esc_html__( 'Invalid filename provided.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		$clean_name = sanitize_file_name( $raw_name );
		$file_ext   = strtolower( pathinfo( $clean_name, PATHINFO_EXTENSION ) );

		if ( empty( $file_ext ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'FILE_TYPE_NOT_ALLOWED',
					'message' => esc_html__( 'Files without an extension are not supported.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		// Hardcoded dangerous extensions that can NEVER be allowed under any circumstances
		$dangerous_exts = array(
			'php',
			'php3',
			'php4',
			'php5',
			'php7',
			'php8',
			'phtml',
			'phar',
			'cgi',
			'pl',
			'py',
			'sh',
			'bash',
			'exe',
			'com',
			'bat',
			'cmd',
			'msi',
			'scr',
			'dll',
			'so',
			'js',
			'mjs',
			'html',
			'htm',
			'shtml',
			'svg',
			'vbs',
			'ps1',
			'jar',
			'apk',
			'htaccess',
			'htpasswd',
			'ini',
			'config',
			'asp',
			'aspx',
		);

		// Excluded types from admin settings merged with dangerous list
		$configured_excluded_raw = isset( $bot_settings['excluded_file_types'] ) ? (string) $bot_settings['excluded_file_types'] : '';
		$configured_excluded     = array_filter( array_map( 'trim', explode( ',', strtolower( $configured_excluded_raw ) ) ) );
		$all_excluded            = array_unique( array_merge( $dangerous_exts, $configured_excluded ) );

		if ( in_array( $file_ext, $all_excluded, true ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'FILE_TYPE_EXCLUDED',
					'message' => esc_html__( 'Sorry, this file type is not permitted for security reasons.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		// Allowed types from admin settings
		$configured_allowed_raw = isset( $bot_settings['allowed_file_types'] ) ? (string) $bot_settings['allowed_file_types'] : 'jpg, jpeg, png, webp, gif, pdf, txt, doc, docx';
		$configured_allowed     = array_filter( array_map( 'trim', explode( ',', strtolower( $configured_allowed_raw ) ) ) );
		$effective_allowed      = array_diff( $configured_allowed, $all_excluded );

		if ( ! in_array( $file_ext, $effective_allowed, true ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'FILE_TYPE_NOT_ALLOWED',
					'message' => sprintf(
						/* translators: %s: Comma-separated list of allowed file types */
						esc_html__( 'This file type is not supported. Allowed types: %s', 'dragwyb-click-to-chat' ),
						implode( ', ', $effective_allowed )
					),
				),
				400
			);
		}

		// Controlled MIME mapping
		$controlled_mimes = array(
			'jpg'  => array( 'image/jpeg', 'image/pjpeg' ),
			'jpeg' => array( 'image/jpeg', 'image/pjpeg' ),
			'png'  => array( 'image/png' ),
			'webp' => array( 'image/webp' ),
			'gif'  => array( 'image/gif' ),
			'pdf'  => array( 'application/pdf' ),
			'txt'  => array( 'text/plain' ),
			'doc'  => array( 'application/msword' ),
			'docx' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
		);

		// WordPress filetype verification
		$wp_check = wp_check_filetype_and_ext( $file['tmp_name'], $clean_name );
		if ( empty( $wp_check['ext'] ) || empty( $wp_check['type'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'SECURITY_CHECK_FAILED',
					'message' => esc_html__( 'File verification failed. Please try a different file.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		// MIME spoofing check: Ensure file contents match extension
		if ( isset( $controlled_mimes[ $file_ext ] ) ) {
			if ( ! in_array( $wp_check['type'], $controlled_mimes[ $file_ext ], true ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'code'    => 'MIME_SPOOFING_DETECTED',
						'message' => esc_html__( 'File content does not match its file extension.', 'dragwyb-click-to-chat' ),
					),
					400
				);
			}
		}

		// Process upload via WordPress Media Library
		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'UPLOAD_FAILED',
					'message' => $attachment_id->get_error_message(),
				),
				500
			);
		}

		$url      = wp_get_attachment_url( $attachment_id );
		$mime     = get_post_mime_type( $attachment_id ) ?: ( $wp_check['type'] ?: 'application/octet-stream' );
		$is_image = str_starts_with( $mime, 'image/' );

		// Tag temporary upload for scheduled retention cleanup
		$settings   = self::dctc_ai_get_all_settings();
		$store_mode = $settings['chatbot']['store_chat_attachments'] ?? 'temp';
		if ( $store_mode === 'temp' || $store_mode === 'do_not_store' ) {
			update_post_meta( $attachment_id, '_dctc_ai_temporary', time() );
		}

		$attachment_data = array(
			'id'           => 'att_' . wp_generate_uuid4(),
			'attachmentId' => $attachment_id,
			'type'         => $is_image ? 'image' : 'file',
			'name'         => $clean_name,
			'size'         => isset( $file['size'] ) ? intval( $file['size'] ) : 0,
			'mime'         => $mime,
			'url'          => esc_url_raw( $url ),
		);

		return new \WP_REST_Response(
			array(
				'success'    => true,
				'attachment' => $attachment_data,
				// Legacy fields for backward compatibility
				'id'         => $attachment_id,
				'url'        => esc_url_raw( $url ),
				'name'       => $clean_name,
			),
			200
		);
	}
}
