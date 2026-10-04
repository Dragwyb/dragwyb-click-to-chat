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
		return current_user_can( 'manage_options' );
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
		return new WP_REST_Response( array_merge( array( 'success' => true ), $stats ), 200 );
	}

	public function update_my_status( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();
		$status  = isset( $params['availability_status'] ) ? sanitize_key( $params['availability_status'] ) : 'available';

		$allowed = array( 'available', 'away', 'offline' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Invalid status.', 'dragwyb-click-to-chat' ) ), 400 );
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

		return new WP_REST_Response( array( 'success' => true, 'availability_status' => $status ), 200 );
	}

	public function get_tickets( $request ) {
		$params = $request->get_params();
		$result = DCTC_Support_Ticket_Service::get_tickets( $params );
		return new WP_REST_Response( array_merge( array( 'success' => true ), $result ), 200 );
	}

	public function get_ticket( $request ) {
		$id = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}
		return new WP_REST_Response( array( 'success' => true, 'ticket' => $ticket ), 200 );
	}

	public function create_ticket( $request ) {
		$params = $request->get_json_params();
		$ticket = DCTC_Support_Ticket_Service::create_ticket( $params );
		if ( is_wp_error( $ticket ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $ticket->get_error_message() ), 400 );
		}
		return new WP_REST_Response( array( 'success' => true, 'ticket' => $ticket ), 201 );
	}

	public function change_status( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();
		$status = isset( $params['status'] ) ? sanitize_key( $params['status'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
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
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
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
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$updated = DCTC_Support_Ticket_Service::assign_ticket( $ticket['id'], $agent_id, 0, 'manual', $reason, get_current_user_id() );
		return new WP_REST_Response( array( 'success' => $updated ), $updated ? 200 : 400 );
	}

	public function take_control( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$result = DCTC_Support_AI_Handoff_Service::take_control( $ticket['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 400 );
		}
		return new WP_REST_Response( array( 'success' => true, 'control_mode' => 'human' ), 200 );
	}

	public function release_control( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$result = DCTC_Support_AI_Handoff_Service::give_control_to_ai( $ticket['id'], get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 400 );
		}
		return new WP_REST_Response( array( 'success' => true, 'control_mode' => 'ai' ), 200 );
	}

	public function add_reply( $request ) {
		$id      = $request->get_param( 'id' );
		$params  = $request->get_json_params();
		$message = isset( $params['message'] ) ? wp_kses_post( $params['message'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$result = DCTC_Support_Ticket_Service::add_reply( $ticket['id'], $message, 'agent', get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 400 );
		}

		$updated_ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket['id'] );
		return new WP_REST_Response( array( 'success' => true, 'ticket' => $updated_ticket ), 200 );
	}

	public function add_note( $request ) {
		$id        = $request->get_param( 'id' );
		$params    = $request->get_json_params();
		$content   = isset( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';
		$is_pinned = ! empty( $params['is_pinned'] );

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$note_id = DCTC_Support_Note_Service::add_note( $ticket['id'], $content, $is_pinned, get_current_user_id() );
		if ( ! $note_id ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Could not add internal note.', 'dragwyb-click-to-chat' ) ), 400 );
		}

		$notes = DCTC_Support_Note_Service::get_notes( $ticket['id'] );
		return new WP_REST_Response( array( 'success' => true, 'notes' => $notes ), 201 );
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
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		DCTC_Support_Tag_Service::set_ticket_tags( $ticket['id'], $tags );
		$updated_tags = DCTC_Support_Tag_Service::get_ticket_tags( $ticket['id'] );

		return new WP_REST_Response( array( 'success' => true, 'tags' => $updated_tags ), 200 );
	}

	public function get_woocommerce_context( $request ) {
		$id = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$wc_context = DCTC_Support_WooCommerce_Service::get_customer_wc_context( $ticket['id'] );
		return new WP_REST_Response( array( 'success' => true, 'woocommerce' => $wc_context ), 200 );
	}

	public function generate_ai_summary( $request ) {
		$id = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$summary = DCTC_Support_AI_Assist_Service::generate_summary( $ticket['id'] );
		if ( is_wp_error( $summary ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $summary->get_error_message() ), 400 );
		}

		return new WP_REST_Response( array( 'success' => true, 'ai_summary' => $summary ), 200 );
	}

	public function suggest_ai_reply( $request ) {
		$id = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$suggested = DCTC_Support_AI_Assist_Service::suggest_reply( $ticket['id'] );
		if ( is_wp_error( $suggested ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $suggested->get_error_message() ), 400 );
		}

		return new WP_REST_Response( array( 'success' => true, 'suggested_reply' => $suggested ), 200 );
	}

	public function delete_ticket( $request ) {
		$id     = $request->get_param( 'id' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $id );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		// Move to trash
		$updated = DCTC_Support_Ticket_Service::change_status( $ticket['id'], 'trash', 'agent', get_current_user_id() );
		return new WP_REST_Response( array( 'success' => $updated ), 200 );
	}

	// Categories & Tags & Agents
	public function get_categories() {
		$categories = DCTC_Support_Category_Service::get_categories();
		return new WP_REST_Response( array( 'success' => true, 'categories' => $categories ), 200 );
	}

	public function save_category( $request ) {
		$data = $request->get_json_params();
		$id   = DCTC_Support_Category_Service::save_category( $data );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Could not save category.', 'dragwyb-click-to-chat' ) ), 400 );
		}
		return new WP_REST_Response( array( 'success' => true, 'id' => $id ), 200 );
	}

	public function delete_category( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = DCTC_Support_Category_Service::delete_category( $id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_tags() {
		$tags = DCTC_Support_Tag_Service::get_tags();
		return new WP_REST_Response( array( 'success' => true, 'tags' => $tags ), 200 );
	}

	public function save_tag( $request ) {
		$data = $request->get_json_params();
		$id   = DCTC_Support_Tag_Service::save_tag( $data );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Could not save tag.', 'dragwyb-click-to-chat' ) ), 400 );
		}
		return new WP_REST_Response( array( 'success' => true, 'id' => $id ), 200 );
	}

	public function delete_tag( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = DCTC_Support_Tag_Service::delete_tag( $id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_agents() {
		$agents = DCTC_Support_Agent_Service::get_agents();
		return new WP_REST_Response( array( 'success' => true, 'agents' => $agents ), 200 );
	}

	public function save_agent( $request ) {
		$data = $request->get_json_params();
		$id   = DCTC_Support_Agent_Service::save_agent( $data );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Could not save agent.', 'dragwyb-click-to-chat' ) ), 400 );
		}
		return new WP_REST_Response( array( 'success' => true, 'id' => $id ), 200 );
	}

	public function delete_agent( $request ) {
		$id      = $request->get_param( 'id' );
		$deleted = DCTC_Support_Agent_Service::delete_agent( $id );
		return new WP_REST_Response( array( 'success' => $deleted ), $deleted ? 200 : 400 );
	}

	public function get_settings() {
		$settings = get_option( 'dctc_support_settings', array() );
		return new WP_REST_Response( array( 'success' => true, 'settings' => $settings ), 200 );
	}

	public function save_settings( $request ) {
		$data = $request->get_json_params();
		update_option( 'dctc_support_settings', $data );
		return new WP_REST_Response( array( 'success' => true, 'settings' => $data ), 200 );
	}

	// -------------------------------------------------------------
	// Customer Portal Endpoints
	// -------------------------------------------------------------

	public function get_portal_tickets( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_params();

		if ( $user_id ) {
			$params['customer_wp_user_id'] = $user_id;
		}

		$result = DCTC_Support_Ticket_Service::get_tickets( $params );
		return new WP_REST_Response( array_merge( array( 'success' => true ), $result ), 200 );
	}

	public function create_portal_ticket( $request ) {
		$params = $request->get_json_params();
		$user_id = get_current_user_id();

		$params['origin_type']      = 'support_portal';
		$params['reply_surface']    = 'support_portal';
		$params['interaction_type'] = 'SUPPORT_TICKET';
		$params['control_mode']     = 'human';

		if ( $user_id ) {
			$params['customer_wp_user_id'] = $user_id;
		}

		$ticket = DCTC_Support_Ticket_Service::create_ticket( $params );
		if ( is_wp_error( $ticket ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $ticket->get_error_message() ), 400 );
		}

		return new WP_REST_Response( array( 'success' => true, 'ticket' => $ticket ), 201 );
	}

	public function get_portal_ticket( $request ) {
		$uuid = $request->get_param( 'uuid' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $uuid );

		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$user_id = get_current_user_id();
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
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Unauthorized ticket access.', 'dragwyb-click-to-chat' ) ), 403 );
		}

		// Strip internal notes and sensitive staff-only metadata from customer response
		unset( $ticket['notes'] );
		unset( $ticket['ai_classification_confidence'] );

		return new WP_REST_Response( array( 'success' => true, 'ticket' => $ticket ), 200 );
	}

	public function portal_reply( $request ) {
		$uuid    = $request->get_param( 'uuid' );
		$params  = $request->get_json_params();
		$message = isset( $params['message'] ) ? wp_kses_post( $params['message'] ) : '';

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $uuid );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$user_id = get_current_user_id();
		$guest_token = $request->get_header( 'X-Guest-Token' );

		$is_owner = false;
		if ( $user_id && ! empty( $ticket['customer_wp_user_id'] ) && (int) $ticket['customer_wp_user_id'] === $user_id ) {
			$is_owner = true;
		} elseif ( ! empty( $ticket['guest_access_token'] ) && $guest_token === $ticket['guest_access_token'] ) {
			$is_owner = true;
		}

		if ( ! $is_owner ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Unauthorized ticket access.', 'dragwyb-click-to-chat' ) ), 403 );
		}

		$result = DCTC_Support_Ticket_Service::add_reply( $ticket['id'], $message, 'customer', $user_id );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 400 );
		}

		$updated_ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket['id'] );
		unset( $updated_ticket['notes'] );

		return new WP_REST_Response( array( 'success' => true, 'ticket' => $updated_ticket ), 200 );
	}

	public function portal_close_ticket( $request ) {
		$uuid = $request->get_param( 'uuid' );
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $uuid );
		if ( ! $ticket ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Ticket not found.', 'dragwyb-click-to-chat' ) ), 404 );
		}

		$user_id = get_current_user_id();
		$guest_token = $request->get_header( 'X-Guest-Token' );

		$is_owner = false;
		if ( $user_id && ! empty( $ticket['customer_wp_user_id'] ) && (int) $ticket['customer_wp_user_id'] === $user_id ) {
			$is_owner = true;
		} elseif ( ! empty( $ticket['guest_access_token'] ) && $guest_token === $ticket['guest_access_token'] ) {
			$is_owner = true;
		}

		if ( ! $is_owner ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => __( 'Unauthorized ticket access.', 'dragwyb-click-to-chat' ) ), 403 );
		}

		DCTC_Support_Ticket_Service::change_status( $ticket['id'], 'closed', 'customer', $user_id );
		return new WP_REST_Response( array( 'success' => true, 'status' => 'closed' ), 200 );
	}
}
