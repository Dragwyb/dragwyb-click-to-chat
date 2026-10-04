<?php
/**
 * DCTC Support Ticket Service
 *
 * Core service for support tickets: lifecycle management, creation,
 * updates, status changes, assignments, and conversation synchronization.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Ticket_Service
 */
class DCTC_Support_Ticket_Service {

	/**
	 * Create a new support ticket (from chatbot, portal, or admin).
	 *
	 * @param array $data Ticket data.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_ticket( $data ) {
		global $wpdb;
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		$subject     = ! empty( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : 'Support Request';
		$session_id  = ! empty( $data['session_id'] ) ? sanitize_text_field( $data['session_id'] ) : 'sess_' . wp_generate_uuid4();
		$user_id     = isset( $data['customer_wp_user_id'] ) ? absint( $data['customer_wp_user_id'] ) : get_current_user_id();
		$email       = ! empty( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '';
		$name        = ! empty( $data['customer_name'] ) ? sanitize_text_field( $data['customer_name'] ) : '';
		$status      = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'open';
		$priority    = ! empty( $data['priority'] ) ? sanitize_key( $data['priority'] ) : 'normal';
		$control     = ! empty( $data['control_mode'] ) ? sanitize_key( $data['control_mode'] ) : 'ai';
		$origin      = ! empty( $data['origin_type'] ) ? sanitize_key( $data['origin_type'] ) : 'chatbot';
		$surface     = ! empty( $data['reply_surface'] ) ? sanitize_key( $data['reply_surface'] ) : 'chatbot_widget';
		$interaction = ! empty( $data['interaction_type'] ) ? sanitize_key( $data['interaction_type'] ) : 'SUPPORT_TICKET';
		$category_id = ! empty( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
		$agent_id    = ! empty( $data['assigned_agent_id'] ) ? absint( $data['assigned_agent_id'] ) : 0;
		$team_id     = ! empty( $data['assigned_team_id'] ) ? absint( $data['assigned_team_id'] ) : 0;
		$ai_summary  = isset( $data['ai_summary'] ) ? sanitize_textarea_field( $data['ai_summary'] ) : '';
		$confidence  = isset( $data['ai_classification_confidence'] ) ? floatval( $data['ai_classification_confidence'] ) : 0.0;

		// Populate name/email from WP user if logged in and not provided
		if ( $user_id && ( empty( $email ) || empty( $name ) ) ) {
			$wp_user = get_userdata( $user_id );
			if ( $wp_user ) {
				$email = empty( $email ) ? $wp_user->user_email : $email;
				$name  = empty( $name ) ? $wp_user->display_name : $name;
			}
		}

		$uuid          = wp_generate_uuid4();
		$ticket_number = DCTC_Support_DB::get_next_ticket_number();
		$guest_token   = ! $user_id ? wp_generate_password( 32, false ) : '';

		$fields = array(
			'uuid'                         => $uuid,
			'ticket_number'                => $ticket_number,
			'session_id'                   => $session_id,
			'customer_wp_user_id'          => $user_id,
			'customer_email'               => $email,
			'customer_name'                => $name,
			'guest_access_token'           => $guest_token,
			'subject'                      => $subject,
			'status'                       => $status,
			'priority'                     => $priority,
			'control_mode'                 => $control,
			'origin_type'                  => $origin,
			'reply_surface'                => $surface,
			'interaction_type'             => $interaction,
			'category_id'                  => $category_id,
			'assigned_agent_id'            => $agent_id,
			'assigned_team_id'             => $team_id,
			'ai_classification_confidence' => $confidence,
			'ai_summary'                   => $ai_summary,
			'created_at'                   => current_time( 'mysql' ),
			'updated_at'                   => current_time( 'mysql' ),
		);

		$inserted = $wpdb->insert(
			$table_tickets,
			$fields,
			array( '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_insert_error', __( 'Could not create support ticket.', 'dragwyb-click-to-chat' ) );
		}

		$ticket_id = $wpdb->insert_id;
		$fields['id'] = $ticket_id;

		// Ensure corresponding row in wp_dctc_ai_sessions exists and is linked
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing_session = $wpdb->get_row(
			$wpdb->prepare( "SELECT id FROM `$table_sessions` WHERE session_id = %s", $session_id )
		);

		if ( $existing_session ) {
			$wpdb->update(
				$table_sessions,
				array(
					'support_ticket_id' => $ticket_id,
					'control_mode'      => $control,
					'reply_surface'     => $surface,
					'updated_at'        => current_time( 'mysql' ),
				),
				array( 'session_id' => $session_id )
			);
		} else {
			// Create new session entry for support portal origin
			$initial_messages = array();
			if ( ! empty( $data['initial_message'] ) ) {
				$initial_messages[] = array(
					'role'       => 'user',
					'content'    => sanitize_textarea_field( $data['initial_message'] ),
					'created_at' => current_time( 'mysql' ),
				);
			}

			$wpdb->insert(
				$table_sessions,
				array(
					'session_id'        => $session_id,
					'email'             => $email,
					'model'             => 'support-agent',
					'provider'          => 'dragwyb',
					'content'           => wp_json_encode( $initial_messages ),
					'control_mode'      => $control,
					'reply_surface'     => $surface,
					'support_ticket_id' => $ticket_id,
					'created_at'        => current_time( 'mysql' ),
					'updated_at'        => current_time( 'mysql' ),
					'status'            => 'active',
				)
			);
		}

		// Save tags if passed
		if ( ! empty( $data['tags'] ) && is_array( $data['tags'] ) ) {
			DCTC_Support_Tag_Service::set_ticket_tags( $ticket_id, $data['tags'] );
		}

		// Log ticket created event
		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			'ticket_created',
			$user_id ? 'customer' : 'system',
			$user_id,
			$name ? $name : 'Customer',
			null,
			$status,
			array(
				'ticket_number' => $ticket_number,
				'origin_type'   => $origin,
				'reply_surface' => $surface,
			)
		);

		// Auto-assign if not explicitly assigned
		if ( ! $agent_id && class_exists( 'DCTC_Support_Assignment_Engine' ) ) {
			$auto_agent_id = DCTC_Support_Assignment_Engine::assign_ticket_automatically( $ticket_id );
			if ( $auto_agent_id ) {
				$fields['assigned_agent_id'] = $auto_agent_id;
			}
		} elseif ( $agent_id ) {
			DCTC_Support_Agent_Service::update_workload( $agent_id );
		}

		return $fields;
	}

	/**
	 * Get paginated tickets list with advanced filters.
	 *
	 * @param array $args Filter and pagination parameters.
	 * @return array<string, mixed>
	 */
	public static function get_tickets( $args = array() ) {
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		$table_agents  = $wpdb->prefix . 'dctc_support_agents';
		$table_cats    = $wpdb->prefix . 'dctc_support_categories';

		$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$per_page = isset( $args['per_page'] ) ? min( 100, max( 1, absint( $args['per_page'] ) ) ) : 20;
		$offset   = ( $page - 1 ) * $per_page;

		$where = '1=1';

		// Status filter (ignore trash by default unless requested)
		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where .= $wpdb->prepare( ' AND t.status = %s', sanitize_key( $args['status'] ) );
		} else {
			$where .= " AND t.status != 'trash'";
		}

		if ( ! empty( $args['priority'] ) && 'all' !== $args['priority'] ) {
			$where .= $wpdb->prepare( ' AND t.priority = %s', sanitize_key( $args['priority'] ) );
		}

		if ( ! empty( $args['control_mode'] ) && 'all' !== $args['control_mode'] ) {
			$where .= $wpdb->prepare( ' AND t.control_mode = %s', sanitize_key( $args['control_mode'] ) );
		}

		if ( ! empty( $args['origin_type'] ) && 'all' !== $args['origin_type'] ) {
			$where .= $wpdb->prepare( ' AND t.origin_type = %s', sanitize_key( $args['origin_type'] ) );
		}

		if ( ! empty( $args['category_id'] ) ) {
			$where .= $wpdb->prepare( ' AND t.category_id = %d', absint( $args['category_id'] ) );
		}

		if ( isset( $args['assigned_agent_id'] ) && '' !== $args['assigned_agent_id'] ) {
			if ( 'unassigned' === $args['assigned_agent_id'] || 0 === (int) $args['assigned_agent_id'] ) {
				$where .= ' AND t.assigned_agent_id = 0';
			} else {
				$where .= $wpdb->prepare( ' AND t.assigned_agent_id = %d', absint( $args['assigned_agent_id'] ) );
			}
		}

		if ( ! empty( $args['customer_wp_user_id'] ) ) {
			$where .= $wpdb->prepare( ' AND t.customer_wp_user_id = %d', absint( $args['customer_wp_user_id'] ) );
		}

		// Search
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where .= $wpdb->prepare(
				' AND (t.subject LIKE %s OR t.customer_name LIKE %s OR t.customer_email LIKE %s OR t.ticket_number LIKE %s)',
				$search,
				$search,
				$search,
				$search
			);
		}

		// Order
		$orderby_allowed = array( 'created_at', 'updated_at', 'ticket_number', 'priority', 'status' );
		$orderby = ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], $orderby_allowed, true ) ) ? $args['orderby'] : 'created_at';
		$order   = ( ! empty( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';

		// Count total
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` t WHERE $where" );

		// Query rows with join for category and agent names
		$sql = "SELECT t.*, 
				c.name as category_name, 
				a.wp_user_id as agent_wp_user_id,
				a.support_role as agent_role
				FROM `$table_tickets` t 
				LEFT JOIN `$table_cats` c ON t.category_id = c.id
				LEFT JOIN `$table_agents` a ON t.assigned_agent_id = a.id
				WHERE $where 
				ORDER BY t.`$orderby` $order 
				LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ), ARRAY_A );

		if ( is_array( $rows ) ) {
			foreach ( $rows as &$row ) {
				if ( ! empty( $row['agent_wp_user_id'] ) ) {
					$agent_user = get_userdata( $row['agent_wp_user_id'] );
					$row['agent_name']   = $agent_user ? $agent_user->display_name : 'Agent #' . $row['assigned_agent_id'];
					$row['agent_avatar'] = get_avatar_url( $row['agent_wp_user_id'], array( 'size' => 48 ) );
				} else {
					$row['agent_name']   = __( 'Unassigned', 'dragwyb-click-to-chat' );
					$row['agent_avatar'] = '';
				}
				$row['tags'] = DCTC_Support_Tag_Service::get_ticket_tags( $row['id'] );
			}
		} else {
			$rows = array();
		}

		return array(
			'tickets'     => $rows,
			'total'       => $total,
			'total_pages' => ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Get single ticket details by ID or UUID.
	 *
	 * @param int|string $id_or_uuid Ticket primary key or UUID string.
	 * @return array<string, mixed>|null
	 */
	public static function get_ticket( $id_or_uuid ) {
		global $wpdb;
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
		$table_agents   = $wpdb->prefix . 'dctc_support_agents';
		$table_cats     = $wpdb->prefix . 'dctc_support_categories';
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		if ( is_numeric( $id_or_uuid ) ) {
			$where = $wpdb->prepare( 't.id = %d', absint( $id_or_uuid ) );
		} else {
			$where = $wpdb->prepare( 't.uuid = %s', sanitize_text_field( $id_or_uuid ) );
		}

		$sql = "SELECT t.*, 
				c.name as category_name, 
				a.wp_user_id as agent_wp_user_id,
				a.support_role as agent_role
				FROM `$table_tickets` t 
				LEFT JOIN `$table_cats` c ON t.category_id = c.id
				LEFT JOIN `$table_agents` a ON t.assigned_agent_id = a.id
				WHERE $where";

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row( $sql, ARRAY_A );

		if ( ! $ticket ) {
			return null;
		}

		if ( ! empty( $ticket['agent_wp_user_id'] ) ) {
			$agent_user = get_userdata( $ticket['agent_wp_user_id'] );
			$ticket['agent_name']   = $agent_user ? $agent_user->display_name : 'Agent #' . $ticket['assigned_agent_id'];
			$ticket['agent_avatar'] = get_avatar_url( $ticket['agent_wp_user_id'], array( 'size' => 48 ) );
		} else {
			$ticket['agent_name']   = __( 'Unassigned', 'dragwyb-click-to-chat' );
			$ticket['agent_avatar'] = '';
		}

		$ticket['tags']   = DCTC_Support_Tag_Service::get_ticket_tags( $ticket['id'] );
		$ticket['events'] = DCTC_Support_Event_Service::get_events( $ticket['id'], 'ASC', 50 );
		$ticket['notes']  = DCTC_Support_Note_Service::get_notes( $ticket['id'] );

		// Retrieve conversation messages from session
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session_content = $wpdb->get_var(
			$wpdb->prepare( "SELECT content FROM `$table_sessions` WHERE session_id = %s", $ticket['session_id'] )
		);

		$ticket['messages'] = ! empty( $session_content ) ? json_decode( $session_content, true ) : array();
		$ticket['messages'] = is_array( $ticket['messages'] ) ? $ticket['messages'] : array();

		return $ticket;
	}

	/**
	 * Change ticket status (open, pending, waiting_customer, resolved, closed, trash).
	 *
	 * @param int         $ticket_id   Ticket ID.
	 * @param string      $new_status  New status slug.
	 * @param string      $actor_type  'agent', 'customer', 'ai', 'system'.
	 * @param int         $actor_id    Actor WP User ID.
	 * @param string      $actor_name  Actor Name.
	 * @return bool
	 */
	public static function change_status( $ticket_id, $new_status, $actor_type = 'agent', $actor_id = 0, $actor_name = '' ) {
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		$ticket_id     = absint( $ticket_id );
		$new_status    = sanitize_key( $new_status );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_tickets` WHERE id = %d", $ticket_id ), ARRAY_A );
		if ( ! $ticket ) {
			return false;
		}

		$old_status = $ticket['status'];
		if ( $old_status === $new_status ) {
			return true;
		}

		$updates = array(
			'status'     => $new_status,
			'updated_at' => current_time( 'mysql' ),
		);

		if ( 'resolved' === $new_status ) {
			$updates['resolved_at'] = current_time( 'mysql' );
		} elseif ( 'closed' === $new_status ) {
			$updates['closed_at'] = current_time( 'mysql' );
		}

		$wpdb->update( $table_tickets, $updates, array( 'id' => $ticket_id ) );

		// Determine event type
		$event_type = 'status_changed';
		if ( 'resolved' === $new_status ) {
			$event_type = 'resolved';
		} elseif ( 'closed' === $new_status ) {
			$event_type = 'closed';
		} elseif ( in_array( $old_status, array( 'resolved', 'closed' ), true ) && 'open' === $new_status ) {
			$event_type = 'reopened';
		}

		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			$event_type,
			$actor_type,
			$actor_id,
			$actor_name,
			$old_status,
			$new_status
		);

		// Recalculate agent workload
		if ( ! empty( $ticket['assigned_agent_id'] ) ) {
			DCTC_Support_Agent_Service::update_workload( $ticket['assigned_agent_id'] );
		}

		// Trigger resolved email notification
		if ( 'resolved' === $new_status && class_exists( 'DCTC_Support_Notification_Service' ) ) {
			DCTC_Support_Notification_Service::notify_ticket_resolved( $ticket_id );
		}

		return true;
	}

	/**
	 * Change ticket priority.
	 *
	 * @param int         $ticket_id     Ticket ID.
	 * @param string      $new_priority  'low', 'normal', 'high', 'urgent'.
	 * @param string      $actor_type    'agent', 'system'.
	 * @param int         $actor_id      Actor User ID.
	 * @param string      $actor_name    Actor Name.
	 * @return bool
	 */
	public static function change_priority( $ticket_id, $new_priority, $actor_type = 'agent', $actor_id = 0, $actor_name = '' ) {
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		$ticket_id     = absint( $ticket_id );
		$new_priority  = sanitize_key( $new_priority );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old_priority = $wpdb->get_var( $wpdb->prepare( "SELECT priority FROM `$table_tickets` WHERE id = %d", $ticket_id ) );
		if ( ! $old_priority || $old_priority === $new_priority ) {
			return false;
		}

		$wpdb->update(
			$table_tickets,
			array( 'priority' => $new_priority, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $ticket_id )
		);

		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			'priority_changed',
			$actor_type,
			$actor_id,
			$actor_name,
			$old_priority,
			$new_priority
		);

		return true;
	}

	/**
	 * Change control mode (AI / Human / Hybrid) with race condition safety.
	 *
	 * @param int    $ticket_id     Ticket ID.
	 * @param string $control_mode  'ai', 'human', 'hybrid'.
	 * @param string $actor_type    'agent', 'ai', 'system'.
	 * @param int    $actor_id      Actor ID.
	 * @param string $actor_name    Actor Name.
	 * @return bool
	 */
	public static function set_control_mode( $ticket_id, $control_mode, $actor_type = 'agent', $actor_id = 0, $actor_name = '' ) {
		global $wpdb;
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		$ticket_id    = absint( $ticket_id );
		$control_mode = sanitize_key( $control_mode );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_tickets` WHERE id = %d", $ticket_id ), ARRAY_A );
		if ( ! $ticket ) {
			return false;
		}

		$old_mode = $ticket['control_mode'];
		if ( $old_mode === $control_mode ) {
			return true;
		}

		$wpdb->update(
			$table_tickets,
			array( 'control_mode' => $control_mode, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $ticket_id )
		);

		if ( ! empty( $ticket['session_id'] ) ) {
			$wpdb->update(
				$table_sessions,
				array( 'control_mode' => $control_mode, 'updated_at' => current_time( 'mysql' ) ),
				array( 'session_id' => $ticket['session_id'] )
			);
		}

		$event_type = 'human' === $control_mode ? 'agent_control_started' : 'ai_control_resumed';

		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			$event_type,
			$actor_type,
			$actor_id,
			$actor_name,
			$old_mode,
			$control_mode
		);

		return true;
	}

	/**
	 * Assign ticket to an agent.
	 *
	 * @param int    $ticket_id   Ticket ID.
	 * @param int    $agent_id    Support Agent ID.
	 * @param int    $team_id     Team ID.
	 * @param string $method      'manual', 'round_robin', 'least_loaded', 'skill_match', 'escalation', 'fallback'.
	 * @param string $reason      Assignment reason.
	 * @param int    $assigned_by WP User ID of assigner (0 = system).
	 * @return bool
	 */
	public static function assign_ticket( $ticket_id, $agent_id, $team_id = 0, $method = 'manual', $reason = '', $assigned_by = 0 ) {
		global $wpdb;
		$table_tickets     = $wpdb->prefix . 'dctc_support_tickets';
		$table_assignments = $wpdb->prefix . 'dctc_support_assignments';

		$ticket_id = absint( $ticket_id );
		$agent_id  = absint( $agent_id );
		$team_id   = absint( $team_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_tickets` WHERE id = %d", $ticket_id ), ARRAY_A );
		if ( ! $ticket ) {
			return false;
		}

		$old_agent_id = (int) $ticket['assigned_agent_id'];
		if ( $old_agent_id === $agent_id ) {
			return true;
		}

		// Update ticket
		$wpdb->update(
			$table_tickets,
			array(
				'assigned_agent_id' => $agent_id,
				'assigned_team_id'  => $team_id,
				'updated_at'        => current_time( 'mysql' ),
			),
			array( 'id' => $ticket_id )
		);

		// Record assignment history
		$wpdb->insert(
			$table_assignments,
			array(
				'ticket_id'          => $ticket_id,
				'agent_id'           => $agent_id,
				'team_id'            => $team_id,
				'assigned_by'        => $assigned_by ? absint( $assigned_by ) : get_current_user_id(),
				'assignment_reason'  => sanitize_text_field( $reason ),
				'assignment_method'  => sanitize_key( $method ),
				'created_at'         => current_time( 'mysql' ),
			)
		);

		// Log timeline event
		$event_type = $old_agent_id ? 'ticket_reassigned' : 'ticket_assigned';
		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			$event_type,
			$assigned_by ? 'agent' : 'system',
			$assigned_by,
			'',
			$old_agent_id,
			$agent_id,
			array( 'method' => $method, 'reason' => $reason )
		);

		// Update workloads
		if ( $old_agent_id ) {
			DCTC_Support_Agent_Service::update_workload( $old_agent_id );
		}
		if ( $agent_id ) {
			DCTC_Support_Agent_Service::update_workload( $agent_id );
			if ( class_exists( 'DCTC_Support_Notification_Service' ) ) {
				DCTC_Support_Notification_Service::notify_ticket_assigned( $ticket_id, $agent_id );
			}
		}

		return true;
	}

	/**
	 * Verify guest ticket authorization token.
	 *
	 * @param string $ticket_uuid  Ticket UUID.
	 * @param string $access_token Guest secret token.
	 * @return array<string, mixed>|false
	 */
	public static function verify_guest_access( $ticket_uuid, $access_token ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_tickets';

		if ( empty( $ticket_uuid ) || empty( $access_token ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `$table` WHERE uuid = %s AND guest_access_token = %s",
				sanitize_text_field( $ticket_uuid ),
				sanitize_text_field( $access_token )
			),
			ARRAY_A
		);

		return $ticket ? $ticket : false;
	}

	/**
	 * Append a customer or agent reply to the linked session conversation.
	 *
	 * @param int         $ticket_id   Ticket ID.
	 * @param string      $message     Message content.
	 * @param string      $sender_type 'customer' or 'agent'.
	 * @param int|null    $user_id     Actor user ID.
	 * @return bool|WP_Error
	 */
	public static function add_reply( $ticket_id, $message, $sender_type = 'customer', $user_id = null ) {
		global $wpdb;
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		$ticket_id   = absint( $ticket_id );
		$message     = wp_kses_post( $message );
		$sender_type = sanitize_key( $sender_type );
		$user_id     = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( empty( $message ) ) {
			return new WP_Error( 'empty_message', __( 'Reply content cannot be empty.', 'dragwyb-click-to-chat' ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_tickets` WHERE id = %d", $ticket_id ), ARRAY_A );
		if ( ! $ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'dragwyb-click-to-chat' ) );
		}

		$session_id = $ticket['session_id'];

		// Read existing session content
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$raw_content = $wpdb->get_var(
			$wpdb->prepare( "SELECT content FROM `$table_sessions` WHERE session_id = %s", $session_id )
		);

		$messages = ! empty( $raw_content ) ? json_decode( $raw_content, true ) : array();
		$messages = is_array( $messages ) ? $messages : array();

		$user = $user_id ? get_userdata( $user_id ) : null;
		$display_name = $user ? $user->display_name : ( 'agent' === $sender_type ? 'Support Agent' : 'Customer' );

		$new_msg = array(
			'role'         => 'agent' === $sender_type ? 'assistant' : 'user',
			'sender_type'  => $sender_type,
			'sender_name'  => $display_name,
			'content'      => $message,
			'created_at'   => current_time( 'mysql' ),
		);

		$messages[] = $new_msg;
		$messages   = array_slice( $messages, -100 ); // keep last 100 messages

		$wpdb->update(
			$table_sessions,
			array(
				'content'    => wp_json_encode( $messages ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'session_id' => $session_id )
		);

		// If customer replies to a resolved ticket, automatically reopen it
		if ( 'customer' === $sender_type && in_array( $ticket['status'], array( 'resolved', 'closed' ), true ) ) {
			self::change_status( $ticket_id, 'open', 'customer', $user_id, $display_name );
		}

		// Update ticket updated_at and first_response_at
		$ticket_updates = array( 'updated_at' => current_time( 'mysql' ) );
		if ( 'agent' === $sender_type && empty( $ticket['first_response_at'] ) ) {
			$ticket_updates['first_response_at'] = current_time( 'mysql' );
		}
		$wpdb->update( $table_tickets, $ticket_updates, array( 'id' => $ticket_id ) );

		// Log event
		$event_type = 'agent' === $sender_type ? 'agent_replied' : 'customer_replied';
		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			$event_type,
			$sender_type,
			$user_id,
			$display_name
		);

		// Trigger reply email notification
		if ( class_exists( 'DCTC_Support_Notification_Service' ) ) {
			if ( 'agent' === $sender_type ) {
				DCTC_Support_Notification_Service::notify_agent_reply( $ticket_id, $message );
			} elseif ( 'customer' === $sender_type ) {
				DCTC_Support_Notification_Service::notify_customer_reply( $ticket_id, $message );
			}
		}

		return true;
	}
}
