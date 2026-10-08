<?php
/**
 * DCTC Support REST Controller
 *
 * Exposes REST API endpoints for Staff Support Center and Customer Portal.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_REST_Controller
 */
class DCTC_Support_REST_Controller {

	/**
	 * REST namespace
	 */
	const REST_NAMESPACE = 'dctc-ai/v1';

	/**
	 * Register all Support REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Staff: Dashboard & Metrics
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/dashboard',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dashboard' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/agents/me/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_my_status' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		// AI Chatbot Session Keepalive / Heartbeat
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/session/heartbeat',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'session_heartbeat' ),
				'permission_callback' => '__return_true',
			)
		);

		// Staff: Tickets
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tickets' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_ticket' ),
					'permission_callback' => array( $this, 'permission_staff_create' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ticket' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_ticket' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_ticket' ),
					'permission_callback' => array( $this, 'permission_staff_delete' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'change_status' ),
				'permission_callback' => array( $this, 'permission_staff_status' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/priority',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'change_priority' ),
				'permission_callback' => array( $this, 'permission_staff_priority' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/assign',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'assign_ticket' ),
				'permission_callback' => array( $this, 'permission_staff_assign' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/take-control',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'take_control' ),
				'permission_callback' => array( $this, 'permission_staff_take_control' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/release-control',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'release_control' ),
				'permission_callback' => array( $this, 'permission_staff_release_control' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/control',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_control' ),
				'permission_callback' => array( $this, 'permission_staff_take_control' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/presence',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_viewing_presence' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/reply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_reply' ),
				'permission_callback' => array( $this, 'permission_staff_reply' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/note',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_note' ),
				'permission_callback' => array( $this, 'permission_staff_note' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/note/(?P<note_id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_note' ),
				'permission_callback' => array( $this, 'permission_staff_note' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/tags',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_tags' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/woocommerce',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_woocommerce_context' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/ai-summary',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_ai_summary' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tickets/(?P<id>[a-zA-Z0-9\-]+)/ai-suggest-reply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'suggest_ai_reply' ),
				'permission_callback' => array( $this, 'permission_staff_reply' ),
			)
		);

		// Staff: Categories & Tags & Agents
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/categories',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_category' ),
					'permission_callback' => array( $this, 'permission_staff_categories' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/categories/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_category' ),
				'permission_callback' => array( $this, 'permission_staff_categories' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tags',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tags' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_tag' ),
					'permission_callback' => array( $this, 'permission_staff_tags' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/tags/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_tag' ),
				'permission_callback' => array( $this, 'permission_staff_tags' ),
			)
		);

		// Staff: Products Catalog
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_product' ),
					'permission_callback' => array( $this, 'permission_staff_categories' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/products/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_product' ),
				'permission_callback' => array( $this, 'permission_staff_categories' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/products/sync-wc',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'sync_wc_products' ),
				'permission_callback' => array( $this, 'permission_staff_categories' ),
			)
		);

		// Staff: Dynamic Taxonomies & Terms
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/taxonomies',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_taxonomies' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_taxonomy' ),
					'permission_callback' => array( $this, 'permission_staff_categories' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/taxonomies/(?P<slug>[a-zA-Z0-9_\-]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_taxonomy' ),
				'permission_callback' => array( $this, 'permission_staff_categories' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/taxonomies/(?P<slug>[a-zA-Z0-9_\-]+)/terms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_taxonomy_terms' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_taxonomy_term' ),
					'permission_callback' => array( $this, 'permission_staff_categories' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/taxonomies/(?P<slug>[a-zA-Z0-9_\-]+)/terms/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_taxonomy_term' ),
				'permission_callback' => array( $this, 'permission_staff_categories' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/agents',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_agents' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_agent' ),
					'permission_callback' => array( $this, 'permission_staff_agents' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/agents/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_agent' ),
				'permission_callback' => array( $this, 'permission_staff_agents' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/wp-users',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_wp_users' ),
				'permission_callback' => array( $this, 'permission_staff_agents' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permission_staff_settings' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/portal-settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_portal_settings' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_portal_settings' ),
					'permission_callback' => array( $this, 'permission_staff_settings' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/permissions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_permissions' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_permissions' ),
					'permission_callback' => array( $this, 'permission_staff_settings' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/me',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_current_user_profile' ),
				'permission_callback' => array( $this, 'permission_staff_view' ),
			)
		);

		// Customer Portal Routes
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/portal/tickets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_portal_tickets' ),
					'permission_callback' => array( $this, 'permission_portal_access' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_portal_ticket' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/portal/tickets/(?P<uuid>[a-zA-Z0-9\-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_portal_ticket' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/portal/tickets/(?P<uuid>[a-zA-Z0-9\-]+)/reply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'portal_reply' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/support/portal/tickets/(?P<uuid>[a-zA-Z0-9\-]+)/close',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'portal_close_ticket' ),
				'permission_callback' => '__return_true',
			)
		);

		// Staff: User Saved Filters
		register_rest_route(
			self::REST_NAMESPACE,
			'/support/user-filters',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_user_filters' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_user_filters' ),
					'permission_callback' => array( $this, 'permission_staff_view' ),
				),
			)
		);
	}

	// -------------------------------------------------------------
	// Permission Callbacks
	// -------------------------------------------------------------

	public function permission_staff_view() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'view_tickets' );
	}

	public function permission_staff_create() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'create_ticket' );
	}

	public function permission_staff_delete() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'delete_ticket' );
	}

	public function permission_staff_status() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'change_status' );
	}

	public function permission_staff_priority() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'change_priority' );
	}

	public function permission_staff_assign() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'assign_ticket' );
	}

	public function permission_staff_take_control() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'take_ai_control' );
	}

	public function permission_staff_release_control() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'release_ai_control' );
	}

	public function permission_staff_reply() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'reply_customer' );
	}

	public function permission_staff_note() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'internal_note' );
	}

	public function permission_staff_categories() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' );
	}

	public function permission_staff_tags() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' );
	}

	public function permission_staff_agents() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'manage_agents' );
	}

	public function permission_staff_settings() {
		return DCTC_Support_Permission_Service::current_user_can_support( 'manage_settings' ) || current_user_can( 'manage_options' );
	}

	/**
	 * AI Chatbot Session Keepalive / Heartbeat handler.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function session_heartbeat( $request ) {
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		if ( empty( $session_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing session_id',
				),
				400
			);
		}

		global $wpdb;
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';
		$wpdb->update(
			$table_sessions,
			array( 'updated_at' => current_time( 'mysql' ) ),
			array( 'session_id' => $session_id )
		);

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function permission_portal_access( $request ) {
		if ( get_current_user_id() ) {
			return true;
		}
		$token = $request->get_header( 'X-Guest-Token' );
		return ! empty( $token );
	}

	// -------------------------------------------------------------
	// Staff Endpoint Handlers
	// -------------------------------------------------------------

	public function get_dashboard() {
		$user_id = get_current_user_id();
		$stats   = DCTC_Support_Ticket_Service::get_dashboard_stats( $user_id );
		return new WP_REST_Response(
			array_merge(
				array(
					'success' => true,
					'stats'   => $stats,
				),
				$stats
			),
			200
		);
	}

	public function update_my_status( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();
		$status  = isset( $params['availability_status'] ) ? sanitize_key( $params['availability_status'] ) : 'available';

		$allowed = array( 'available', 'away', 'offline' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid status.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		global $wpdb;
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		$updated      = $wpdb->update(
			$table_agents,
			array(
				'availability_status' => $status,
				'updated_at'          => current_time( 'mysql' ),
			),
			array( 'wp_user_id' => $user_id )
		);

		return new WP_REST_Response(
			array(
				'success'             => true,
				'availability_status' => $status,
			),
			200
		);
	}

	public function get_tickets( $request ) {
		$params = $request->get_params();
		$result = DCTC_Support_Ticket_Service::get_tickets( $params );
		return new WP_REST_Response( array_merge( array( 'success' => true ), $result ), 200 );
	}

	public function get_ticket( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $ticket,
			),
			200
		);
	}

	public function create_ticket( $request ) {
		$params = $request->get_json_params();
		$ticket = DCTC_Support_Ticket_Service::create_ticket( $params );
		if ( is_wp_error( $ticket ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $ticket->get_error_message(),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $ticket,
			),
			201
		);
	}

	public function update_ticket( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		$user_id = get_current_user_id();
		$result  = DCTC_Support_Ticket_Service::update_ticket_properties( $id, $params, 'agent', $user_id );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $result,
			),
			200
		);
	}

	public function change_status( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();
		$status = isset( $params['status'] ) ? sanitize_key( $params['status'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$user_id   = get_current_user_id();
		$user      = get_userdata( $user_id );
		$user_name = $user ? $user->display_name : 'Staff';

		$updated = DCTC_Support_Ticket_Service::change_status( $ticket['id'], $status, 'agent', $user_id, $user_name );
		return new WP_REST_Response( array( 'success' => $updated ), $updated ? 200 : 400 );
	}

	public function change_priority( $request ) {
		$id       = $request->get_param( 'id' );
		$params   = $request->get_json_params();
		$priority = isset( $params['priority'] ) ? sanitize_key( $params['priority'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$user_id   = get_current_user_id();
		$user      = get_userdata( $user_id );
		$user_name = $user ? $user->display_name : 'Staff';

		$updated = DCTC_Support_Ticket_Service::change_priority( $ticket['id'], $priority, 'agent', $user_id, $user_name );
		return new WP_REST_Response( array( 'success' => $updated ), $updated ? 200 : 400 );
	}

	public function assign_ticket( $request ) {
		$id       = $request->get_param( 'id' );
		$params   = $request->get_json_params();
		$agent_id = isset( $params['agent_id'] ) ? absint( $params['agent_id'] ) : 0;
		$reason   = isset( $params['reason'] ) ? sanitize_text_field( $params['reason'] ) : 'Manual assignment';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$updated = DCTC_Support_Ticket_Service::assign_ticket( $ticket['id'], $agent_id, 0, 'manual', $reason, get_current_user_id() );
		return new WP_REST_Response( array( 'success' => $updated ), $updated ? 200 : 400 );
	}

	public function take_control( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$result = DCTC_Support_AI_Handoff_Service::take_control( $ticket['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success'      => true,
				'control_mode' => 'human',
			),
			200
		);
	}

	public function release_control( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$result = DCTC_Support_AI_Handoff_Service::give_control_to_ai( $ticket['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success'      => true,
				'control_mode' => 'ai',
			),
			200
		);
	}

	public function toggle_control( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();
		$mode   = isset( $params['mode'] ) ? sanitize_key( $params['mode'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		if ( 'human' === $mode ) {
			$result = DCTC_Support_AI_Handoff_Service::take_control( $ticket['id'], get_current_user_id() );
			if ( is_wp_error( $result ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $result->get_error_message(),
					),
					400
				);
			}
			return new WP_REST_Response(
				array(
					'success'      => true,
					'control_mode' => 'human',
				),
				200
			);
		} else {
			$result = DCTC_Support_AI_Handoff_Service::give_control_to_ai( $ticket['id'], get_current_user_id() );
			if ( is_wp_error( $result ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $result->get_error_message(),
					),
					400
				);
			}
			return new WP_REST_Response(
				array(
					'success'      => true,
					'control_mode' => 'ai',
				),
				200
			);
		}
	}

	public function add_reply( $request ) {
		$id      = $request->get_param( 'id' );
		$params  = $request->get_json_params();
		$message = isset( $params['message'] ) ? wp_kses_post( $params['message'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$result = DCTC_Support_Ticket_Service::add_reply( $ticket['id'], $message, 'agent', get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		$updated_ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket['id'] );
		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $updated_ticket,
			),
			200
		);
	}

	public function add_note( $request ) {
		$id        = $request->get_param( 'id' );
		$params    = $request->get_json_params();
		$content   = isset( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';
		$is_pinned = ! empty( $params['is_pinned'] );

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$note_id = DCTC_Support_Note_Service::add_note( $ticket['id'], $content, $is_pinned, get_current_user_id() );
		if ( ! $note_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not add internal note.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}

		$notes = DCTC_Support_Note_Service::get_notes( $ticket['id'] );
		return new WP_REST_Response(
			array(
				'success' => true,
				'notes'   => $notes,
			),
			201
		);
	}

	public function delete_note( $request ) {
		$note_id = $request->get_param( 'note_id' );
		$deleted = DCTC_Support_Note_Service::delete_note( $note_id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function update_tags( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();
		$tags   = isset( $params['tags'] ) && is_array( $params['tags'] ) ? $params['tags'] : array();

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		DCTC_Support_Tag_Service::set_ticket_tags( $ticket['id'], $tags );
		$updated_tags = DCTC_Support_Tag_Service::get_ticket_tags( $ticket['id'] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'tags'    => $updated_tags,
			),
			200
		);
	}

	public function get_woocommerce_context( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$wc_context = DCTC_Support_WooCommerce_Service::get_customer_wc_context( $ticket['id'] );
		return new WP_REST_Response(
			array(
				'success'     => true,
				'woocommerce' => $wc_context,
			),
			200
		);
	}

	public function generate_ai_summary( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$summary = DCTC_Support_AI_Assist_Service::generate_summary( $ticket['id'] );
		if ( is_wp_error( $summary ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $summary->get_error_message(),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'ai_summary' => $summary,
			),
			200
		);
	}

	public function suggest_ai_reply( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$suggested = DCTC_Support_AI_Assist_Service::suggest_reply( $ticket['id'] );
		if ( is_wp_error( $suggested ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $suggested->get_error_message(),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success'         => true,
				'suggested_reply' => $suggested,
			),
			200
		);
	}

	public function update_viewing_presence( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$viewing = isset( $params['viewing'] ) ? ( (bool) $params['viewing'] ? 1 : 0 ) : 1;
		$now     = current_time( 'timestamp' );
		$user_id = get_current_user_id();

		// Fetch existing viewers map
		$stored_viewers = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket['id'], '_agent_viewing_user_ids', true );
		$active_viewers = array();

		if ( is_array( $stored_viewers ) ) {
			foreach ( $stored_viewers as $viewer ) {
				if ( is_array( $viewer ) && ! empty( $viewer['user_id'] ) && ! empty( $viewer['last_seen'] ) ) {
					// Prune if older than 35s
					if ( ( $now - (int) $viewer['last_seen'] ) <= 35 ) {
						$active_viewers[ (int) $viewer['user_id'] ] = $viewer;
					}
				}
			}
		}

		if ( $user_id > 0 ) {
			if ( $viewing ) {
				$user         = get_userdata( $user_id );
				$display_name = $user ? $user->display_name : 'Agent #' . $user_id;
				$avatar       = get_avatar_url( $user_id, array( 'size' => 48 ) );

				$initials = '';
				$parts    = explode( ' ', trim( $display_name ) );
				foreach ( $parts as $p ) {
					if ( ! empty( $p ) ) {
						$initials .= strtoupper( mb_substr( $p, 0, 1 ) );
					}
				}
				$initials = substr( $initials, 0, 2 ) ?: 'AG';

				$active_viewers[ $user_id ] = array(
					'user_id'   => (int) $user_id,
					'name'      => $display_name,
					'initials'  => $initials,
					'avatar'    => $avatar,
					'last_seen' => $now,
				);
			} else {
				unset( $active_viewers[ $user_id ] );
			}
		}

		$is_anyone_viewing = ! empty( $active_viewers ) ? 1 : 0;
		$viewers_list      = array_values( $active_viewers );

		DCTC_Support_Ticket_Service::update_ticket_meta( $ticket['id'], '_agent_viewing', $is_anyone_viewing );
		DCTC_Support_Ticket_Service::update_ticket_meta( $ticket['id'], '_agent_last_viewed_at', $now );
		DCTC_Support_Ticket_Service::update_ticket_meta( $ticket['id'], '_agent_viewing_user_ids', $viewers_list );

		return new WP_REST_Response(
			array(
				'success'       => true,
				'ticket_id'     => (int) $ticket['id'],
				'viewing'       => $is_anyone_viewing,
				'viewing_users' => $viewers_list,
				'updated_at'    => $now,
			),
			200
		);
	}

	public function delete_ticket( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		// Move to trash
		$updated = DCTC_Support_Ticket_Service::change_status( $ticket['id'], 'trash', 'agent', get_current_user_id() );
		return new WP_REST_Response( array( 'success' => $updated ), 200 );
	}

	// Categories & Tags & Agents
	public function get_categories() {
		$categories = DCTC_Support_Category_Service::get_categories();
		return new WP_REST_Response(
			array(
				'success'    => true,
				'categories' => $categories,
			),
			200
		);
	}

	public function save_category( $request ) {
		$data = $request->get_json_params();
		$id   = DCTC_Support_Category_Service::save_category( $data );
		if ( ! $id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not save category.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
			),
			200
		);
	}

	public function delete_category( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = DCTC_Support_Category_Service::delete_category( $id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_tags() {
		$tags = DCTC_Support_Tag_Service::get_tags();
		return new WP_REST_Response(
			array(
				'success' => true,
				'tags'    => $tags,
			),
			200
		);
	}

	public function save_tag( $request ) {
		$data = $request->get_json_params();
		$id   = DCTC_Support_Tag_Service::save_tag( $data );
		if ( ! $id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not save tag.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
			),
			200
		);
	}

	public function delete_tag( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = DCTC_Support_Tag_Service::delete_tag( $id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	// Products Catalog
	public function get_products( $request ) {
		$params   = $request->get_params();
		$products = class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::get_products( $params ) : array();
		return new WP_REST_Response(
			array(
				'success'  => true,
				'products' => $products,
			),
			200
		);
	}

	public function save_product( $request ) {
		$data = $request->get_json_params();
		$id   = class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::save_product( $data ) : false;
		if ( ! $id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not save product.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
			),
			200
		);
	}

	public function delete_product( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::delete_product( $id ) : false;
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function sync_wc_products() {
		$count    = class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::sync_woocommerce_products() : 0;
		$products = class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::get_products() : array();
		return new WP_REST_Response(
			array(
				'success'      => true,
				'synced_count' => $count,
				'products'     => $products,
			),
			200
		);
	}

	// Dynamic Taxonomies & Terms
	public function get_taxonomies() {
		$taxonomies = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::get_taxonomies() : array();
		if ( is_array( $taxonomies ) ) {
			foreach ( $taxonomies as &$tax ) {
				$tax['terms'] = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::get_terms( $tax['slug'] ) : array();
			}
		}
		return new WP_REST_Response(
			array(
				'success'    => true,
				'taxonomies' => $taxonomies,
			),
			200
		);
	}

	public function save_taxonomy( $request ) {
		$data = $request->get_json_params();
		$tax  = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::save_taxonomy( $data ) : false;
		if ( ! $tax ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not save taxonomy.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success'  => true,
				'taxonomy' => $tax,
			),
			200
		);
	}

	public function delete_taxonomy( $request ) {
		$slug    = $request->get_param( 'slug' );
		$deleted = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::delete_taxonomy( $slug ) : false;
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_taxonomy_terms( $request ) {
		$slug  = $request->get_param( 'slug' );
		$terms = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::get_terms( $slug ) : array();
		return new WP_REST_Response(
			array(
				'success' => true,
				'terms'   => $terms,
			),
			200
		);
	}

	public function save_taxonomy_term( $request ) {
		$slug = $request->get_param( 'slug' );
		$data = $request->get_json_params();
		$id   = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::save_term( $slug, $data ) : false;
		if ( ! $id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not save taxonomy term.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
			),
			200
		);
	}

	public function delete_taxonomy_term( $request ) {
		$slug    = $request->get_param( 'slug' );
		$id      = $request->get_param( 'id' );
		$deleted = class_exists( 'DCTC_Support_Taxonomy_Service' ) ? DCTC_Support_Taxonomy_Service::delete_term( $slug, $id ) : false;
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_agents() {
		$agents = DCTC_Support_Agent_Service::get_agents();
		return new WP_REST_Response(
			array(
				'success' => true,
				'agents'  => $agents,
			),
			200
		);
	}

	public function save_agent( $request ) {
		$data = $request->get_json_params();
		$id   = DCTC_Support_Agent_Service::save_agent( $data );
		if ( ! $id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Could not save agent.', 'dragwyb-click-to-chat' ),
				),
				400
			);
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
			),
			200
		);
	}

	public function delete_agent( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = DCTC_Support_Agent_Service::delete_agent( $id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_wp_users() {
		$users = get_users(
			array(
				'number'  => 100,
				'orderby' => 'display_name',
				'order'   => 'ASC',
				'fields'  => array( 'ID', 'display_name', 'user_email', 'user_login' ),
			)
		);

		$formatted = array();
		foreach ( $users as $u ) {
			$user_obj    = get_userdata( $u->ID );
			$formatted[] = array(
				'id'           => (int) $u->ID,
				'display_name' => $u->display_name,
				'user_email'   => $u->user_email,
				'user_login'   => $u->user_login,
				'roles'        => $user_obj ? (array) $user_obj->roles : array(),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'users'   => $formatted,
			),
			200
		);
	}

	public function get_settings() {
		$settings = get_option( 'dctc_support_settings', array() );
		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $settings,
			),
			200
		);
	}

	public function save_settings( $request ) {
		$data = $request->get_json_params();
		update_option( 'dctc_support_settings', $data );

		if ( ! empty( $data['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
			DCTC_Support_DB::create_tables();
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $data,
			),
			200
		);
	}

	public function get_portal_settings() {
		$settings = class_exists( 'DCTC_Support_Portal' )
			? DCTC_Support_Portal::get_settings()
			: get_option( 'dctc_support_portal_settings', array() );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $settings,
			),
			200
		);
	}

	public function save_portal_settings( $request ) {
		$data = $request->get_json_params();
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$sanitized = class_exists( 'DCTC_Support_Portal' )
			? DCTC_Support_Portal::sanitize_settings( $data )
			: $data;

		update_option( 'dctc_support_portal_settings', $sanitized );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $sanitized,
				'message'  => __( 'Support portal styling and configuration saved successfully.', 'dragwyb-click-to-chat' ),
			),
			200
		);
	}

	public function get_permissions() {
		$matrix           = DCTC_Support_Permission_Service::get_role_permissions();
		$user_permissions = DCTC_Support_Permission_Service::get_user_permissions();
		return new WP_REST_Response(
			array(
				'success'            => true,
				'permissions_matrix' => $matrix,
				'user_permissions'   => $user_permissions,
				'roles'              => array(
					'admin'   => __( 'Administrator', 'dragwyb-click-to-chat' ),
					'manager' => __( 'Support Manager', 'dragwyb-click-to-chat' ),
					'senior'  => __( 'Senior Specialist', 'dragwyb-click-to-chat' ),
					'support' => __( 'Support Agent', 'dragwyb-click-to-chat' ),
					'fresher' => __( 'Fresher / Junior', 'dragwyb-click-to-chat' ),
				),
			),
			200
		);
	}

	public function save_permissions( $request ) {
		$matrix  = $request->get_json_params();
		$success = DCTC_Support_Permission_Service::save_role_permissions( $matrix );
		return new WP_REST_Response(
			array(
				'success'            => (bool) $success,
				'permissions_matrix' => DCTC_Support_Permission_Service::get_role_permissions(),
			),
			$success ? 200 : 400
		);
	}

	public function get_current_user_profile() {
		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Not authenticated.', 'dragwyb-click-to-chat' ),
				),
				401
			);
		}

		$perms = DCTC_Support_Permission_Service::get_user_permissions( $user_id );
		return new WP_REST_Response(
			array(
				'success'     => true,
				'user'        => array(
					'id'           => $user_id,
					'display_name' => $user->display_name,
					'email'        => $user->user_email,
					'avatar'       => get_avatar_url( $user_id, array( 'size' => 64 ) ),
				),
				'permissions' => $perms,
			),
			200
		);
	}

	// -------------------------------------------------------------
	// Customer Portal Endpoints
	// -------------------------------------------------------------

	public function get_portal_tickets( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_params();

		if ( $user_id ) {
			$params['customer_wp_user_id'] = $user_id;
		} else {
			$guest_token = $request->get_header( 'X-Guest-Token' );
			if ( ! empty( $guest_token ) ) {
				$params['guest_access_token'] = sanitize_text_field( $guest_token );
			} else {
				// Logged-out visitor with no guest token has no tickets
				return new WP_REST_Response(
					array(
						'success' => true,
						'tickets' => array(),
						'total'   => 0,
						'pages'   => 1,
					),
					200
				);
			}
		}

		$result = DCTC_Support_Ticket_Service::get_tickets( $params );

		// Security: Strip internal notes and sensitive staff-only metadata from all tickets returned to portal
		if ( ! empty( $result['tickets'] ) && is_array( $result['tickets'] ) ) {
			foreach ( $result['tickets'] as &$t ) {
				unset( $t['notes'], $t['internal_notes'], $t['ai_classification_confidence'] );
			}
		}

		return new WP_REST_Response( array_merge( array( 'success' => true ), $result ), 200 );
	}

	public function create_portal_ticket( $request ) {
		$params  = $request->get_json_params();
		$user_id = get_current_user_id();

		// If user is logged out, verify that guest ticket submissions are enabled
		if ( ! $user_id ) {
			require_once DCTC_PLUGIN_DIR . 'includes/ai/support/class-dctc-support-portal.php';
			$portal_settings = DCTC_Support_Portal::get_settings();
			if ( empty( $portal_settings['enable_guest_ticket_form'] ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Guest ticket submissions are disabled. Please log in to your account to submit a ticket.', 'dragwyb-click-to-chat' ),
					),
					403
				);
			}

			// Require visitor name and email for guest ticket
			if ( empty( $params['customer_email'] ) || ! is_email( $params['customer_email'] ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Please provide a valid email address for your support ticket.', 'dragwyb-click-to-chat' ),
					),
					400
				);
			}
		}

		$params['origin_type']      = 'support_portal';
		$params['reply_surface']    = 'support_portal';
		$params['interaction_type'] = 'SUPPORT_TICKET';
		$params['control_mode']     = 'human';

		if ( $user_id ) {
			$params['customer_wp_user_id'] = $user_id;
		}

		$ticket = DCTC_Support_Ticket_Service::create_ticket( $params );
		if ( is_wp_error( $ticket ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $ticket->get_error_message(),
				),
				400
			);
		}

		// Strip internal staff notes from response
		unset( $ticket['notes'], $ticket['ai_classification_confidence'] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $ticket,
			),
			201
		);
	}

	public function get_portal_ticket( $request ) {
		$uuid   = $request->get_param( 'uuid' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $uuid );

		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$user_id     = get_current_user_id();
		$guest_token = $request->get_header( 'X-Guest-Token' );

		// Security: Customer authorization check
		$is_owner = false;
		if ( $user_id && ! empty( $ticket['customer_wp_user_id'] ) && (int) $ticket['customer_wp_user_id'] === $user_id ) {
			$is_owner = true;
		} elseif ( ! empty( $ticket['guest_access_token'] ) && $guest_token === $ticket['guest_access_token'] ) {
			$is_owner = true;
		} elseif ( current_user_can( 'manage_options' ) ) {
			$is_owner = true;
		}

		if ( ! $is_owner ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Unauthorized ticket access.', 'dragwyb-click-to-chat' ),
				),
				403
			);
		}

		// Strip internal notes and sensitive staff-only metadata from customer response
		unset( $ticket['notes'] );
		unset( $ticket['ai_classification_confidence'] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $ticket,
			),
			200
		);
	}

	public function portal_reply( $request ) {
		$uuid    = $request->get_param( 'uuid' );
		$params  = $request->get_json_params();
		$message = isset( $params['message'] ) ? wp_kses_post( $params['message'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $uuid );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$user_id     = get_current_user_id();
		$guest_token = $request->get_header( 'X-Guest-Token' );

		$is_owner = false;
		if ( $user_id && ! empty( $ticket['customer_wp_user_id'] ) && (int) $ticket['customer_wp_user_id'] === $user_id ) {
			$is_owner = true;
		} elseif ( ! empty( $ticket['guest_access_token'] ) && $guest_token === $ticket['guest_access_token'] ) {
			$is_owner = true;
		}

		if ( ! $is_owner ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Unauthorized ticket access.', 'dragwyb-click-to-chat' ),
				),
				403
			);
		}

		$result = DCTC_Support_Ticket_Service::add_reply( $ticket['id'], $message, 'customer', $user_id );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		$updated_ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket['id'] );
		unset( $updated_ticket['notes'] );

		return new WP_REST_Response(
			array(
				'success' => true,
				'ticket'  => $updated_ticket,
			),
			200
		);
	}

	public function portal_close_ticket( $request ) {
		$uuid   = $request->get_param( 'uuid' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $uuid );
		if ( ! $ticket ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ),
				),
				404
			);
		}

		$user_id     = get_current_user_id();
		$guest_token = $request->get_header( 'X-Guest-Token' );

		$is_owner = false;
		if ( $user_id && ! empty( $ticket['customer_wp_user_id'] ) && (int) $ticket['customer_wp_user_id'] === $user_id ) {
			$is_owner = true;
		} elseif ( ! empty( $ticket['guest_access_token'] ) && $guest_token === $ticket['guest_access_token'] ) {
			$is_owner = true;
		}

		if ( ! $is_owner ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Unauthorized ticket access.', 'dragwyb-click-to-chat' ),
				),
				403
			);
		}

		DCTC_Support_Ticket_Service::change_status( $ticket['id'], 'closed', 'customer', $user_id );
		return new WP_REST_Response(
			array(
				'success' => true,
				'status'  => 'closed',
			),
			200
		);
	}

	/**
	 * Get current user's saved filter preferences.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_user_filters( $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_REST_Response( array( 'success' => false, 'filters' => new stdClass() ), 401 );
		}

		$filters = get_option( 'dctc_support_user_filters_' . $user_id, null );
		if ( null === $filters || false === $filters ) {
			$filters = get_user_meta( $user_id, 'dctc_support_saved_filters', true );
		}

		if ( empty( $filters ) || ! is_array( $filters ) ) {
			$filters = new stdClass();
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'filters' => $filters,
			),
			200
		);
	}

	/**
	 * Save or reset current user's filter preferences in option/meta table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_user_filters( $request ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Unauthorized.', 'dragwyb-click-to-chat' ) ), 401 );
		}

		$params  = $request->get_json_params();
		$reset   = ! empty( $params['reset'] );
		$filters = isset( $params['filters'] ) && is_array( $params['filters'] ) ? $params['filters'] : array();

		if ( $reset || empty( $filters ) ) {
			delete_option( 'dctc_support_user_filters_' . $user_id );
			delete_user_meta( $user_id, 'dctc_support_saved_filters' );
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Saved filters reset successfully.', 'dragwyb-click-to-chat' ),
					'filters' => new stdClass(),
				),
				200
			);
		}

		// Sanitize filter map
		$clean = array();
		foreach ( $filters as $k => $v ) {
			$clean_key = sanitize_key( $k );
			if ( is_array( $v ) ) {
				$clean[ $clean_key ] = array_map( 'sanitize_text_field', $v );
			} else {
				$clean[ $clean_key ] = sanitize_text_field( (string) $v );
			}
		}

		update_option( 'dctc_support_user_filters_' . $user_id, $clean );
		update_user_meta( $user_id, 'dctc_support_saved_filters', $clean );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'User filters saved.', 'dragwyb-click-to-chat' ),
				'filters' => $clean,
			),
			200
		);
	}
}
