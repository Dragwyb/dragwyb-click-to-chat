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

		// Intelligent Auto-Classification for Category & Product Tags if not explicitly provided
		$query_content = $subject . ' ' . ( ! empty( $data['initial_message'] ) ? $data['initial_message'] : '' );
		if ( ( empty( $category_id ) || empty( $data['tags'] ) ) && ! empty( $query_content ) ) {
			$classification = self::auto_classify_query( $query_content );
			if ( empty( $category_id ) && ! empty( $classification['category_id'] ) ) {
				$category_id = $classification['category_id'];
				$confidence  = $classification['confidence'];
			}
			if ( empty( $data['tags'] ) && ! empty( $classification['tags'] ) ) {
				$data['tags'] = $classification['tags'];
			}
		}

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
				$msg_entry = array(
					'role'       => 'user',
					'content'    => wp_kses_post( $data['initial_message'] ),
					'created_at' => current_time( 'mysql' ),
				);

				if ( ! empty( $data['attachments'] ) && is_array( $data['attachments'] ) ) {
					$sanitized_attachments = array();
					foreach ( $data['attachments'] as $att ) {
						if ( is_array( $att ) && ! empty( $att['url'] ) ) {
							$sanitized_attachments[] = array(
								'name' => ! empty( $att['name'] ) ? sanitize_text_field( $att['name'] ) : 'attachment',
								'url'  => esc_url_raw( $att['url'] ),
								'type' => ! empty( $att['type'] ) ? sanitize_mime_type( $att['type'] ) : '',
								'size' => ! empty( $att['size'] ) ? absint( $att['size'] ) : 0,
							);
						}
					}
					if ( ! empty( $sanitized_attachments ) ) {
						$msg_entry['attachments'] = $sanitized_attachments;
					}
				}

				$initial_messages[] = $msg_entry;
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

		// Save ticket meta if passed
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $mkey => $mval ) {
				self::update_ticket_meta( $ticket_id, $mkey, $mval );
			}
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
		$table_terms   = $wpdb->prefix . 'dctc_support_terms';

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
				c.color as category_color,
				a.wp_user_id as agent_wp_user_id,
				a.support_role as agent_role,
				s.id as session_exists_id,
				s.content as session_content
				FROM `$table_tickets` t 
				LEFT JOIN `$table_terms` c ON (t.category_id = c.id AND c.taxonomy_slug = 'category')
				LEFT JOIN `$table_agents` a ON t.assigned_agent_id = a.id
				LEFT JOIN `" . $wpdb->prefix . "dctc_ai_sessions` s ON t.session_id = s.session_id
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

				// Calculate chat / message count and session existence
				$row['has_session']   = ! empty( $row['session_exists_id'] );
				$session_messages     = ! empty( $row['session_content'] ) ? json_decode( $row['session_content'], true ) : array();
				$row['chat_count']    = is_array( $session_messages ) ? count( $session_messages ) : 0;
				$row['message_count'] = $row['chat_count'];
				unset( $row['session_content'], $row['session_exists_id'] );
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
		$table_terms    = $wpdb->prefix . 'dctc_support_terms';
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		if ( is_numeric( $id_or_uuid ) ) {
			$where = $wpdb->prepare( 't.id = %d', absint( $id_or_uuid ) );
		} else {
			$where = $wpdb->prepare( 't.uuid = %s', sanitize_text_field( $id_or_uuid ) );
		}

		$sql = "SELECT t.*, 
				c.name as category_name, 
				c.color as category_color,
				a.wp_user_id as agent_wp_user_id,
				a.support_role as agent_role
				FROM `$table_tickets` t 
				LEFT JOIN `$table_terms` c ON (t.category_id = c.id AND c.taxonomy_slug = 'category')
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
		$ticket['meta']   = self::get_all_ticket_meta( $ticket['id'] );

		// Retrieve conversation messages and check session existence
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session_row = ! empty( $ticket['session_id'] ) ? $wpdb->get_row(
			$wpdb->prepare( "SELECT id, content, updated_at FROM `$table_sessions` WHERE session_id = %s", $ticket['session_id'] ),
			ARRAY_A
		) : null;

		$ticket['has_session']   = ! empty( $session_row );
		$session_content         = $session_row ? $session_row['content'] : '';
		$ticket['messages']      = ! empty( $session_content ) ? json_decode( $session_content, true ) : array();
		if ( empty( $ticket['messages'] ) ) {
			$meta_msgs = self::get_ticket_meta( $ticket['id'], '_dctc_ticket_messages', true );
			if ( is_array( $meta_msgs ) ) {
				$ticket['messages'] = $meta_msgs;
			}
		}
		$ticket['messages']      = is_array( $ticket['messages'] ) ? $ticket['messages'] : array();
		$ticket['chat_count']    = count( $ticket['messages'] );
		$ticket['message_count'] = $ticket['chat_count'];

		$session_updated = ( $session_row && ! empty( $session_row['updated_at'] ) ) ? strtotime( $session_row['updated_at'] ) : 0;
		$now             = current_time( 'timestamp' );
		$ticket['is_session_active'] = $session_row && ( ( $now - $session_updated ) < 90 );

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
			$raw_content = $wpdb->get_var(
				$wpdb->prepare( "SELECT content FROM `$table_sessions` WHERE session_id = %s", $ticket['session_id'] )
			);
			$messages = ! empty( $raw_content ) ? json_decode( $raw_content, true ) : array();
			$messages = is_array( $messages ) ? $messages : array();

			if ( 'human' === $control_mode && 'human' !== $old_mode ) {
				$messages[] = array(
					'role'        => 'system',
					'sender_type' => 'system',
					'sender_name' => 'System',
					'content'     => sprintf( __( '— Support Agent %s took control of Ticket #%d —', 'dragwyb-click-to-chat' ), $actor_name ?: 'Staff', $ticket_id ),
					'created_at'  => current_time( 'mysql' ),
				);
			}

			$wpdb->update(
				$table_sessions,
				array(
					'control_mode' => $control_mode,
					'content'      => wp_json_encode( $messages ),
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'session_id' => $ticket['session_id'] )
			);

			self::update_ticket_meta( $ticket_id, '_dctc_ticket_messages', $messages );
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

		self::update_ticket_meta( $ticket_id, '_dctc_ticket_messages', $messages );

		// If agent replies, auto-take control (Pause AI) and assign agent if unassigned
		if ( 'agent' === $sender_type ) {
			if ( $ticket['control_mode'] !== 'human' ) {
				self::set_control_mode( $ticket_id, 'human', 'agent', $user_id, $display_name );
			}
			if ( empty( $ticket['assigned_agent_id'] ) && $user_id ) {
				$table_agents = $wpdb->prefix . 'dctc_support_agents';
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$agent_row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM `$table_agents` WHERE wp_user_id = %d", $user_id ), ARRAY_A );
				if ( $agent_row ) {
					$wpdb->update(
						$table_tickets,
						array( 'assigned_agent_id' => absint( $agent_row['id'] ) ),
						array( 'id' => $ticket_id )
					);
				}
			}
		}

		// If customer replies to a resolved ticket, automatically reopen it
		if ( 'customer' === $sender_type && in_array( $ticket['status'], array( 'resolved', 'closed' ), true ) ) {
			self::change_status( $ticket_id, 'open', 'customer', $user_id, $display_name );
		}

		// Update ticket updated_at and first_response_at
		$ticket_updates = array( 'updated_at' => current_time( 'mysql' ) );
		if ( 'agent' === $sender_type ) {
			if ( empty( $ticket['first_response_at'] ) ) {
				$ticket_updates['first_response_at'] = current_time( 'mysql' );
			}
			if ( ! in_array( $ticket['status'], array( 'resolved', 'closed' ), true ) ) {
				$ticket_updates['status'] = 'waiting_customer';
			}
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

	/**
	 * Get live dashboard KPI metrics and agent summary for the logged in user.
	 *
	 * @param int|null $user_id Optional WP User ID.
	 * @return array<string, mixed>
	 */
	public static function get_dashboard_stats( $user_id = null ) {
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		$table_agents  = $wpdb->prefix . 'dctc_support_agents';
		$table_events  = $wpdb->prefix . 'dctc_support_events';

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		$wp_user = get_userdata( $user_id );

		// Find agent record
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$agent = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table_agents` WHERE wp_user_id = %d AND active = 1", $user_id ),
			ARRAY_A
		);

		$agent_id = $agent ? (int) $agent['id'] : 0;
		$is_admin = user_can( $user_id, 'manage_options' );

		$today = current_time( 'Y-m-d' );

		// Metrics
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_tickets = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status != 'trash'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_open = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status = 'open'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status IN ('pending', 'waiting_customer')" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_resolved = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status IN ('resolved', 'closed')" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$today_created = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE DATE(created_at) = %s AND status != 'trash'", $today ) );

		// Assigned to me today
		if ( $agent_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$my_today_assigned = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE assigned_agent_id = %d AND DATE(created_at) = %s AND status != 'trash'", $agent_id, $today ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$my_active = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE assigned_agent_id = %d AND status IN ('open', 'pending', 'waiting_customer')", $agent_id ) );
		} else {
			$my_today_assigned = $today_created;
			$my_active = $total_open + $total_pending;
		}

		// Control mode breakdown
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ai_controlled = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE control_mode = 'ai' AND status IN ('open', 'pending', 'waiting_customer')" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$human_controlled = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE control_mode = 'human' AND status IN ('open', 'pending', 'waiting_customer')" );

		// Agent info
		$agent_profile = array(
			'id'                  => $agent_id,
			'wp_user_id'          => $user_id,
			'display_name'        => $wp_user ? $wp_user->display_name : 'Staff Member',
			'user_email'          => $wp_user ? $wp_user->user_email : '',
			'avatar'              => get_avatar_url( $user_id, array( 'size' => 64 ) ),
			'support_role'        => $agent ? $agent['support_role'] : ( $is_admin ? 'Administrator' : 'Agent' ),
			'availability_status' => $agent ? $agent['availability_status'] : 'available',
			'current_active'      => $agent ? (int) $agent['current_active_tickets'] : $my_active,
			'max_active'          => $agent ? (int) $agent['max_active_tickets'] : 10,
			'is_admin'            => $is_admin,
		);

		// Recent events
		$recent_sql = "SELECT e.*, t.ticket_number, t.subject as ticket_subject 
			FROM `$table_events` e 
			LEFT JOIN `$table_tickets` t ON e.ticket_id = t.id 
			ORDER BY e.created_at DESC LIMIT 6";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$recent_events = $wpdb->get_results( $recent_sql, ARRAY_A );

		return array(
			'total_tickets'            => $total_tickets,
			'total_open'               => $total_open,
			'total_pending'            => $total_pending,
			'total_resolved'           => $total_resolved,
			'today_created'            => $today_created,
			'today_assigned_tickets'   => $my_today_assigned,
			'my_active_tickets'        => $my_active,
			'ai_controlled_tickets'    => $ai_controlled,
			'human_controlled_tickets' => $human_controlled,
			'agent'                    => $agent_profile,
			'recent_activity'          => is_array( $recent_events ) ? $recent_events : array(),
		);
	}

	/**
	 * Automatically classify a user support query by category and tags (including WooCommerce product detection).
	 *
	 * @param string $text Query content (subject and message).
	 * @return array<string, mixed> Array containing category_id, confidence, and tags.
	 */
	public static function auto_classify_query( $text ) {
		if ( empty( $text ) || ! is_string( $text ) ) {
			return array(
				'category_id' => 0,
				'confidence'  => 0.0,
				'tags'        => array(),
			);
		}

		$text_lower = mb_strtolower( $text );
		$tags       = array();
		$matched_id = 0;
		$confidence = 0.5;

		// 1. Detect WooCommerce Products if present
		$matched_product_name = '';
		if ( post_type_exists( 'product' ) ) {
			$products = get_posts( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );

			if ( ! empty( $products ) && is_array( $products ) ) {
				foreach ( $products as $product ) {
					$title_lower = mb_strtolower( $product->post_title );
					if ( strlen( $title_lower ) >= 3 && false !== mb_strpos( $text_lower, $title_lower ) ) {
						$matched_product_name = $product->post_title;
						$tags[] = $product->post_title;
						$confidence = 0.90;
						break;
					}
				}
			}
		}

		// 2. Fetch categories from DB
		$db_categories = class_exists( 'DCTC_Support_Category_Service' )
			? DCTC_Support_Category_Service::get_categories( array( 'status' => 'active' ) )
			: array();

		// Check for specific category keywords
		$category_keywords = array(
			'billing'     => array( 'bill', 'invoice', 'receipt', 'charge', 'payment', 'paid', 'credit card', 'subscription', 'price', 'pricing', 'checkout', 'cost' ),
			'returns'     => array( 'return', 'refund', 'money back', 'cancel order', 'exchange', 'replace', 'damaged', 'broken item', 'wrong item' ),
			'technical'   => array( 'bug', 'error', 'not working', 'crash', 'broken', 'issue', 'fail', 'failed', 'glitch', 'exception', 'login error', 'technical', 'blank page' ),
			'woocommerce' => array( 'order', 'shipping', 'delivery', 'tracking', 'package', 'cart', 'product', 'item', 'stock', 'inventory', 'store' ),
			'account'     => array( 'password', 'login', 'account', 'profile', 'reset password', 'register', 'sign in', 'email change' ),
		);

		foreach ( $category_keywords as $cat_slug => $keywords ) {
			foreach ( $keywords as $kw ) {
				if ( false !== mb_strpos( $text_lower, $kw ) ) {
					// Add intent tags
					if ( in_array( $kw, array( 'refund', 'return', 'cancel order' ), true ) && ! in_array( 'Refund', $tags, true ) ) {
						$tags[] = 'Refund';
					}
					if ( in_array( $kw, array( 'shipping', 'delivery', 'tracking', 'package' ), true ) && ! in_array( 'Shipping', $tags, true ) ) {
						$tags[] = 'Shipping';
					}
					if ( in_array( $kw, array( 'bug', 'error', 'crash', 'glitch' ), true ) && ! in_array( 'Bug', $tags, true ) ) {
						$tags[] = 'Bug';
					}
					if ( in_array( $kw, array( 'billing', 'invoice', 'payment', 'charge' ), true ) && ! in_array( 'Billing', $tags, true ) ) {
						$tags[] = 'Billing';
					}

					// Find matching DB category
					if ( ! $matched_id && ! empty( $db_categories ) ) {
						foreach ( $db_categories as $cat ) {
							$slug = mb_strtolower( $cat['slug'] );
							$name = mb_strtolower( $cat['name'] );
							if ( false !== mb_strpos( $slug, $cat_slug ) || false !== mb_strpos( $name, $cat_slug ) || false !== mb_strpos( $text_lower, $name ) ) {
								$matched_id = (int) $cat['id'];
								$confidence = max( $confidence, 0.85 );
								break;
							}
						}
					}
					break;
				}
			}
		}

		// Fallback category matching against DB category names directly
		if ( ! $matched_id && ! empty( $db_categories ) ) {
			foreach ( $db_categories as $cat ) {
				$name = mb_strtolower( $cat['name'] );
				if ( strlen( $name ) >= 3 && false !== mb_strpos( $text_lower, $name ) ) {
					$matched_id = (int) $cat['id'];
					$confidence = 0.80;
					break;
				}
			}
		}

		// If matched a product but no category found, pick first WooCommerce or Store category if available
		if ( $matched_product_name && ! $matched_id && ! empty( $db_categories ) ) {
			foreach ( $db_categories as $cat ) {
				$slug = mb_strtolower( $cat['slug'] );
				if ( false !== mb_strpos( $slug, 'woo' ) || false !== mb_strpos( $slug, 'order' ) || false !== mb_strpos( $slug, 'product' ) ) {
					$matched_id = (int) $cat['id'];
					break;
				}
			}
		}

		// Ensure tags are unique
		$tags = array_values( array_unique( array_filter( $tags ) ) );

		return array(
			'category_id' => $matched_id,
			'confidence'  => $confidence,
			'tags'        => $tags,
		);
	}

	/**
	 * Get ticket metadata.
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single Return single string/array or all entries.
	 * @return mixed
	 */
	public static function get_ticket_meta( $ticket_id, $meta_key = '', $single = true ) {
		global $wpdb;
		$table     = $wpdb->prefix . 'dctc_support_ticket_meta';
		$ticket_id = absint( $ticket_id );

		if ( empty( $meta_key ) ) {
			return self::get_all_ticket_meta( $ticket_id );
		}

		$meta_key = sanitize_key( $meta_key );
		if ( $single ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$val = $wpdb->get_var(
				$wpdb->prepare( "SELECT meta_value FROM `$table` WHERE ticket_id = %d AND meta_key = %s LIMIT 1", $ticket_id, $meta_key )
			);
			return $val;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return $wpdb->get_col(
				$wpdb->prepare( "SELECT meta_value FROM `$table` WHERE ticket_id = %d AND meta_key = %s", $ticket_id, $meta_key )
			);
		}
	}

	/**
	 * Get all metadata for a ticket as associative key => value map.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return array<string, mixed>
	 */
	public static function get_all_ticket_meta( $ticket_id ) {
		global $wpdb;
		$table     = $wpdb->prefix . 'dctc_support_ticket_meta';
		$ticket_id = absint( $ticket_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT meta_key, meta_value FROM `$table` WHERE ticket_id = %d", $ticket_id ),
			ARRAY_A
		);

		$map = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$decoded = json_decode( $row['meta_value'], true );
				$map[ $row['meta_key'] ] = ( null !== $decoded && is_array( $decoded ) ) ? $decoded : $row['meta_value'];
			}
		}

		return $map;
	}

	/**
	 * Update or insert ticket metadata.
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value (scalar or array).
	 * @return bool
	 */
	public static function update_ticket_meta( $ticket_id, $meta_key, $meta_value ) {
		global $wpdb;
		$table     = $wpdb->prefix . 'dctc_support_ticket_meta';
		$ticket_id = absint( $ticket_id );
		$meta_key  = sanitize_key( $meta_key );

		if ( empty( $ticket_id ) || empty( $meta_key ) ) {
			return false;
		}

		$val_str = is_array( $meta_value ) || is_object( $meta_value )
			? wp_json_encode( $meta_value )
			: (string) $meta_value;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_id FROM `$table` WHERE ticket_id = %d AND meta_key = %s", $ticket_id, $meta_key )
		);

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'meta_value' => $val_str ),
				array( 'meta_id' => (int) $existing ),
				array( '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'ticket_id'  => $ticket_id,
					'meta_key'   => $meta_key,
					'meta_value' => $val_str,
				),
				array( '%d', '%s', '%s' )
			);
		}

		return true;
	}

	/**
	 * Delete ticket metadata.
	 *
	 * @param int    $ticket_id Ticket ID.
	 * @param string $meta_key Optional meta key.
	 * @return bool
	 */
	public static function delete_ticket_meta( $ticket_id, $meta_key = '' ) {
		global $wpdb;
		$table     = $wpdb->prefix . 'dctc_support_ticket_meta';
		$ticket_id = absint( $ticket_id );

		if ( empty( $ticket_id ) ) {
			return false;
		}

		if ( ! empty( $meta_key ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete(
				$table,
				array( 'ticket_id' => $ticket_id, 'meta_key' => sanitize_key( $meta_key ) ),
				array( '%d', '%s' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete(
				$table,
				array( 'ticket_id' => $ticket_id ),
				array( '%d' )
			);
		}

		return true;
	}
}

