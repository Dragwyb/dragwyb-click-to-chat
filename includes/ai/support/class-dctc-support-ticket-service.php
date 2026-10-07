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

		// Insert only clean core columns into dctc_support_tickets table
		$core_fields = array(
			'ticket_number'                 => $ticket_number,
			'uuid'                          => $uuid,
			'customer_wp_user_id'           => $user_id,
			'customer_email'                => $email,
			'customer_name'                 => $name,
			'subject'                       => $subject,
			'status'                        => $status,
			'priority'                      => $priority,
			'customer_last_seen_at'         => current_time( 'mysql' ),
			'customer_last_read_message_id' => '',
			'agent_last_read_message_id'    => '',
			'created_at'                    => current_time( 'mysql' ),
			'updated_at'                    => current_time( 'mysql' ),
		);

		$inserted = $wpdb->insert(
			$table_tickets,
			$core_fields,
			array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_insert_error', __( 'Could not create support ticket.', 'dragwyb-click-to-chat' ) );
		}

		$ticket_id = $wpdb->insert_id;

		// Save all extra metadata in dctc_support_ticket_meta
		if ( ! empty( $session_id ) ) {
			self::update_ticket_meta( $ticket_id, 'session_id', $session_id );
		}
		if ( ! empty( $guest_token ) ) {
			self::update_ticket_meta( $ticket_id, 'guest_access_token', $guest_token );
		}
		self::update_ticket_meta( $ticket_id, 'control_mode', $control );
		self::update_ticket_meta( $ticket_id, 'origin_type', $origin );
		self::update_ticket_meta( $ticket_id, 'reply_surface', $surface );
		self::update_ticket_meta( $ticket_id, 'interaction_type', $interaction );

		if ( $category_id ) {
			self::update_ticket_meta( $ticket_id, 'category_id', $category_id );
		}
		if ( $agent_id ) {
			self::update_ticket_meta( $ticket_id, 'assigned_agent_id', $agent_id );
		}
		if ( $team_id ) {
			self::update_ticket_meta( $ticket_id, 'assigned_team_id', $team_id );
		}
		if ( $confidence > 0 ) {
			self::update_ticket_meta( $ticket_id, 'ai_classification_confidence', $confidence );
		}
		if ( ! empty( $ai_summary ) ) {
			self::update_ticket_meta( $ticket_id, 'ai_summary', $ai_summary );
		}

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

		// Save custom ticket meta if passed
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
				$agent_id = $auto_agent_id;
				self::update_ticket_meta( $ticket_id, 'assigned_agent_id', $auto_agent_id );
			}
		} elseif ( $agent_id ) {
			DCTC_Support_Agent_Service::update_workload( $agent_id );
		}

		// Return fully populated ticket object with meta
		$result = array_merge(
			$core_fields,
			array(
				'id'                           => $ticket_id,
				'session_id'                   => $session_id,
				'guest_access_token'           => $guest_token,
				'control_mode'                 => $control,
				'origin_type'                  => $origin,
				'reply_surface'                => $surface,
				'interaction_type'             => $interaction,
				'category_id'                  => $category_id,
				'assigned_agent_id'            => $agent_id,
				'assigned_team_id'             => $team_id,
				'ai_classification_confidence' => $confidence,
				'ai_summary'                   => $ai_summary,
			)
		);

		return $result;
	}

	/**
	 * Get paginated tickets list with advanced filters.
	 *
	 * @param array $args Filter and pagination parameters.
	 * @return array<string, mixed>
	 */
	public static function get_tickets( $args = array() ) {
		global $wpdb;
		$table_tickets     = $wpdb->prefix . 'dctc_support_tickets';
		$table_ticket_meta = $wpdb->prefix . 'dctc_support_ticket_meta';
		$table_agents      = $wpdb->prefix . 'dctc_support_agents';
		$table_terms       = $wpdb->prefix . 'dctc_support_terms';
		$table_sessions    = $wpdb->prefix . 'dctc_ai_sessions';

		$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$per_page = isset( $args['per_page'] ) ? min( 100, max( 1, absint( $args['per_page'] ) ) ) : 20;
		$offset   = ( $page - 1 ) * $per_page;

		$where = '1=1';

		// Status filter (ignore trash by default unless requested)
		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$status_val = sanitize_key( $args['status'] );
			if ( 'pending' === $status_val ) {
				$where .= " AND t.status IN ('pending', 'waiting_customer', 'waiting_agent', 'hold')";
			} elseif ( 'open' === $status_val ) {
				// Show all active tickets that are not closed / resolved
				$where .= " AND t.status NOT IN ('resolved', 'closed', 'trash')";
			} elseif ( 'resolved' === $status_val ) {
				$where .= " AND t.status IN ('resolved', 'closed')";
			} elseif ( in_array( $status_val, array( 'ai_bot', 'ai', 'bot', 'chatbot' ), true ) ) {
				$where .= " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE (meta_key = 'origin_type' AND meta_value IN ('chatbot', 'ai', 'ai_assistant')) OR (meta_key = 'control_mode' AND meta_value = 'ai') OR (meta_key = 'session_id' AND meta_value != '' AND meta_value IS NOT NULL))) AND t.status != 'trash'";
			} else {
				$where .= $wpdb->prepare( ' AND t.status = %s', $status_val );
			}
		} else {
			$where .= " AND t.status != 'trash'";
		}

		if ( ! empty( $args['priority'] ) && 'all' !== $args['priority'] ) {
			$where .= $wpdb->prepare( ' AND t.priority = %s', sanitize_key( $args['priority'] ) );
		}

		$table_rel = $wpdb->prefix . 'dctc_support_term_relationships';

		// Ticket Type filter: human vs ai
		$mode_val = ! empty( $args['ticket_type'] ) ? $args['ticket_type'] : ( ! empty( $args['control_mode'] ) ? $args['control_mode'] : '' );
		if ( ! empty( $mode_val ) && 'all' !== $mode_val ) {
			if ( 'human' === $mode_val ) {
				$where .= $wpdb->prepare( " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'control_mode' AND meta_value = %s) OR (t.id NOT IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'control_mode') AND t.id NOT IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'origin_type' AND meta_value IN ('chatbot', 'ai', 'ai_assistant'))))", 'human' );
			} elseif ( in_array( $mode_val, array( 'ai', 'ai_bot', 'bot', 'chatbot' ), true ) ) {
				$where .= " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE (meta_key = 'origin_type' AND meta_value IN ('chatbot', 'ai', 'ai_assistant')) OR (meta_key = 'control_mode' AND meta_value = 'ai') OR (meta_key = 'session_id' AND meta_value != '' AND meta_value IS NOT NULL)))";
			}
		}

		if ( ! empty( $args['origin_type'] ) && 'all' !== $args['origin_type'] ) {
			$where .= $wpdb->prepare( " AND t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'origin_type' AND meta_value = %s)", sanitize_key( $args['origin_type'] ) );
		}

		// Category filter
		$cat_val = ! empty( $args['category_id'] ) ? $args['category_id'] : ( ! empty( $args['category'] ) ? $args['category'] : '' );
		if ( ! empty( $cat_val ) && 'all' !== $cat_val ) {
			if ( is_numeric( $cat_val ) ) {
				$cat_id = absint( $cat_val );
				$where .= $wpdb->prepare( " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'category_id' AND meta_value = %d) OR t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = 'category' AND term_id = %d))", $cat_id, $cat_id );
			} else {
				$where .= $wpdb->prepare( " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'category_name' AND meta_value = %s) OR t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = 'category' AND term_id IN (SELECT id FROM `$table_terms` WHERE taxonomy_slug = 'category' AND (slug = %s OR name = %s))))", sanitize_text_field( $cat_val ), sanitize_title( $cat_val ), sanitize_text_field( $cat_val ) );
			}
		}

		// Product filter
		$prod_val = ! empty( $args['product_id'] ) ? $args['product_id'] : ( ! empty( $args['product'] ) ? $args['product'] : '' );
		if ( ! empty( $prod_val ) && 'all' !== $prod_val ) {
			if ( is_numeric( $prod_val ) ) {
				$prod_id = absint( $prod_val );
				$where .= $wpdb->prepare( " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE (meta_key = 'product_id' AND meta_value = %s) OR (meta_key = 'product' AND meta_value = %s)) OR t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = 'product' AND term_id = %d))", (string) $prod_id, (string) $prod_id, $prod_id );
			} else {
				$where .= $wpdb->prepare( " AND (t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key IN ('product', 'product_name') AND meta_value = %s) OR t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = 'product' AND term_id IN (SELECT id FROM `$table_terms` WHERE taxonomy_slug = 'product' AND (slug = %s OR name = %s))))", sanitize_text_field( $prod_val ), sanitize_title( $prod_val ), sanitize_text_field( $prod_val ) );
			}
		}

		// Tag filter
		$tag_val = ! empty( $args['tag_id'] ) ? $args['tag_id'] : ( ! empty( $args['tag'] ) ? $args['tag'] : '' );
		if ( ! empty( $tag_val ) && 'all' !== $tag_val ) {
			if ( is_numeric( $tag_val ) ) {
				$tag_id = absint( $tag_val );
				$where .= $wpdb->prepare( " AND t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = 'tag' AND term_id = %d)", $tag_id );
			} else {
				$where .= $wpdb->prepare( " AND t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = 'tag' AND term_id IN (SELECT id FROM `$table_terms` WHERE taxonomy_slug = 'tag' AND (slug = %s OR name = %s)))", sanitize_title( $tag_val ), sanitize_text_field( $tag_val ) );
			}
		}

		// Customer Type filter
		if ( ! empty( $args['customer_type'] ) && 'all' !== $args['customer_type'] ) {
			$cust_type = sanitize_key( $args['customer_type'] );
			if ( 'registered' === $cust_type ) {
				$where .= ' AND (t.customer_wp_user_id IS NOT NULL AND t.customer_wp_user_id > 0)';
			} elseif ( 'guest' === $cust_type ) {
				$where .= ' AND (t.customer_wp_user_id IS NULL OR t.customer_wp_user_id = 0)';
			}
		}

		// Date Range filter
		if ( ! empty( $args['date_range'] ) && 'all' !== $args['date_range'] ) {
			$range = sanitize_key( $args['date_range'] );
			if ( 'today' === $range ) {
				$where .= ' AND DATE(t.created_at) = CURDATE()';
			} elseif ( 'yesterday' === $range ) {
				$where .= ' AND DATE(t.created_at) = SUBDATE(CURDATE(), 1)';
			} elseif ( in_array( $range, array( '7days', '7d', 'week' ), true ) ) {
				$where .= ' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
			} elseif ( in_array( $range, array( '30days', '30d', 'month' ), true ) ) {
				$where .= ' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
			} elseif ( 'this_month' === $range ) {
				$where .= ' AND YEAR(t.created_at) = YEAR(CURDATE()) AND MONTH(t.created_at) = MONTH(CURDATE())';
			}
		}

		// Support custom taxonomies filter array
		if ( ! empty( $args['tax_terms'] ) ) {
			$tax_terms = is_string( $args['tax_terms'] ) ? json_decode( stripslashes( $args['tax_terms'] ), true ) : $args['tax_terms'];
			if ( is_array( $tax_terms ) ) {
				foreach ( $tax_terms as $tax_slug => $term_val ) {
					if ( ! empty( $term_val ) && 'all' !== $term_val ) {
						if ( is_numeric( $term_val ) ) {
							$where .= $wpdb->prepare( " AND t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = %s AND term_id = %d)", sanitize_title( $tax_slug ), absint( $term_val ) );
						} else {
							$where .= $wpdb->prepare( " AND t.id IN (SELECT object_id FROM `$table_rel` WHERE taxonomy_slug = %s AND term_id IN (SELECT id FROM `$table_terms` WHERE taxonomy_slug = %s AND (slug = %s OR name = %s)))", sanitize_title( $tax_slug ), sanitize_title( $tax_slug ), sanitize_title( $term_val ), sanitize_text_field( $term_val ) );
						}
					}
				}
			}
		}

		// Assigned Agent filter
		$agent_arg = isset( $args['assigned_agent_id'] ) ? $args['assigned_agent_id'] : ( isset( $args['assigned_to'] ) ? $args['assigned_to'] : '' );
		if ( '' !== $agent_arg && 'all' !== $agent_arg ) {
			if ( 'unassigned' === $agent_arg || 0 === (int) $agent_arg || '0' === (string) $agent_arg ) {
				$where .= " AND t.id NOT IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'assigned_agent_id' AND meta_value != '0' AND meta_value != '')";
			} else {
				$where .= $wpdb->prepare( " AND t.id IN (SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'assigned_agent_id' AND meta_value = %s)", (string) absint( $agent_arg ) );
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

		// Query page rows from clean tickets table
		$sql = "SELECT t.* FROM `$table_tickets` t WHERE $where ORDER BY t.`$orderby` $order LIMIT %d OFFSET %d";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $per_page, $offset ), ARRAY_A );

		if ( is_array( $rows ) && ! empty( $rows ) ) {
			$ticket_ids = wp_list_pluck( $rows, 'id' );
			$ids_in     = implode( ',', array_map( 'absint', $ticket_ids ) );

			// Batch load all metadata for these tickets
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$meta_rows = $wpdb->get_results( "SELECT ticket_id, meta_key, meta_value FROM `$table_ticket_meta` WHERE ticket_id IN ($ids_in)", ARRAY_A );
			$meta_map  = array();
			if ( is_array( $meta_rows ) ) {
				foreach ( $meta_rows as $mr ) {
					$meta_map[ (int) $mr['ticket_id'] ][ $mr['meta_key'] ] = $mr['meta_value'];
				}
			}

			// Collect category IDs and agent IDs for batch lookup
			$cat_ids   = array();
			$agent_ids = array();

			foreach ( $rows as $row ) {
				$t_id   = (int) $row['id'];
				$t_meta = isset( $meta_map[ $t_id ] ) ? $meta_map[ $t_id ] : array();
				if ( ! empty( $t_meta['category_id'] ) ) {
					$cat_ids[] = absint( $t_meta['category_id'] );
				}
				if ( ! empty( $t_meta['assigned_agent_id'] ) ) {
					$agent_ids[] = absint( $t_meta['assigned_agent_id'] );
				}
			}

			// Batch load categories
			$cats_map = array();
			if ( ! empty( $cat_ids ) ) {
				$c_ids_in = implode( ',', array_unique( $cat_ids ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$c_rows = $wpdb->get_results( "SELECT id, name, color FROM `$table_terms` WHERE id IN ($c_ids_in)", ARRAY_A );
				if ( is_array( $c_rows ) ) {
					foreach ( $c_rows as $cr ) {
						$cats_map[ (int) $cr['id'] ] = $cr;
					}
				}
			}

			// Batch load agents
			$agents_map = array();
			if ( ! empty( $agent_ids ) ) {
				$a_ids_in = implode( ',', array_unique( $agent_ids ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$a_rows = $wpdb->get_results( "SELECT id, wp_user_id, support_role FROM `$table_agents` WHERE id IN ($a_ids_in)", ARRAY_A );
				if ( is_array( $a_rows ) ) {
					foreach ( $a_rows as $ar ) {
						$agents_map[ (int) $ar['id'] ] = $ar;
					}
				}
			}

			// Batch load linked chat sessions
			$sessions_map = array();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$s_rows = $wpdb->get_results( "SELECT id, session_id, support_ticket_id, content FROM `$table_sessions` WHERE support_ticket_id IN ($ids_in)", ARRAY_A );
			if ( is_array( $s_rows ) ) {
				foreach ( $s_rows as $sr ) {
					$sessions_map[ (int) $sr['support_ticket_id'] ] = $sr;
				}
			}

			foreach ( $rows as &$row ) {
				$t_id   = (int) $row['id'];
				$t_meta = isset( $meta_map[ $t_id ] ) ? $meta_map[ $t_id ] : array();

				$row['category_id']                  = ! empty( $t_meta['category_id'] ) ? absint( $t_meta['category_id'] ) : 0;
				$row['assigned_agent_id']            = ! empty( $t_meta['assigned_agent_id'] ) ? absint( $t_meta['assigned_agent_id'] ) : 0;
				$row['assigned_team_id']             = ! empty( $t_meta['assigned_team_id'] ) ? absint( $t_meta['assigned_team_id'] ) : 0;
				$row['control_mode']                 = ! empty( $t_meta['control_mode'] ) ? (string) $t_meta['control_mode'] : 'ai';
				$row['origin_type']                  = ! empty( $t_meta['origin_type'] ) ? (string) $t_meta['origin_type'] : 'chatbot';
				$row['reply_surface']                = ! empty( $t_meta['reply_surface'] ) ? (string) $t_meta['reply_surface'] : 'chatbot_widget';
				$row['interaction_type']             = ! empty( $t_meta['interaction_type'] ) ? (string) $t_meta['interaction_type'] : 'SUPPORT_TICKET';
				$row['session_id']                   = ! empty( $t_meta['session_id'] ) ? (string) $t_meta['session_id'] : '';
				$row['guest_access_token']           = ! empty( $t_meta['guest_access_token'] ) ? (string) $t_meta['guest_access_token'] : '';
				$row['ai_classification_confidence'] = isset( $t_meta['ai_classification_confidence'] ) ? floatval( $t_meta['ai_classification_confidence'] ) : 0.0;
				$row['ai_summary']                   = ! empty( $t_meta['ai_summary'] ) ? (string) $t_meta['ai_summary'] : '';

				// Category resolution
				if ( ! empty( $row['category_id'] ) && isset( $cats_map[ $row['category_id'] ] ) ) {
					$row['category_name']  = $cats_map[ $row['category_id'] ]['name'];
					$row['category_color'] = $cats_map[ $row['category_id'] ]['color'];
				} else {
					$row['category_name']  = '';
					$row['category_color'] = '#4F46E5';
				}

				// Agent resolution
				if ( ! empty( $row['assigned_agent_id'] ) && isset( $agents_map[ $row['assigned_agent_id'] ] ) ) {
					$agent_rec               = $agents_map[ $row['assigned_agent_id'] ];
					$row['agent_wp_user_id'] = (int) $agent_rec['wp_user_id'];
					$row['agent_role']       = $agent_rec['support_role'];
					$agent_user              = get_userdata( $agent_rec['wp_user_id'] );
					$row['agent_name']       = $agent_user ? $agent_user->display_name : 'Agent #' . $row['assigned_agent_id'];
					$row['agent_avatar']     = get_avatar_url( $agent_rec['wp_user_id'], array( 'size' => 48 ) );
				} else {
					$row['agent_wp_user_id'] = 0;
					$row['agent_role']       = '';
					$row['agent_name']       = __( 'Unassigned', 'dragwyb-click-to-chat' );
					$row['agent_avatar']     = '';
				}

				$row['tags']    = DCTC_Support_Tag_Service::get_ticket_tags( $row['id'] );
				$row['product'] = ! empty( $t_meta['product'] ) ? (string) $t_meta['product'] : ( ! empty( $t_meta['product_name'] ) ? (string) $t_meta['product_name'] : '' );

				// Chat and session count
				$session_rec          = isset( $sessions_map[ $t_id ] ) ? $sessions_map[ $t_id ] : null;
				$row['has_session']   = ! empty( $session_rec );
				$session_messages     = ( $session_rec && ! empty( $session_rec['content'] ) ) ? json_decode( $session_rec['content'], true ) : array();
				$row['chat_count']    = is_array( $session_messages ) ? count( $session_messages ) : 0;
				$row['message_count'] = $row['chat_count'];

				// Message excerpt extraction
				$first_content = '';
				$last_content  = '';
				if ( ! empty( $session_messages ) && is_array( $session_messages ) ) {
					$first_item    = reset( $session_messages );
					$last_item     = end( $session_messages );
					$first_content = ! empty( $first_item['content'] ) ? wp_strip_all_tags( $first_item['content'] ) : '';
					$last_content  = ! empty( $last_item['content'] ) ? wp_strip_all_tags( $last_item['content'] ) : '';
				}
				$row['excerpt']      = $first_content ? $first_content : ( $last_content ? $last_content : $row['subject'] );
				$row['last_message'] = $last_content ? $last_content : $row['subject'];
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ticket = $wpdb->get_row( "SELECT t.* FROM `$table_tickets` t WHERE $where", ARRAY_A );

		if ( ! $ticket ) {
			return null;
		}

		$ticket_id = (int) $ticket['id'];

		// Load all ticket metadata
		$ticket_meta    = self::get_all_ticket_meta( $ticket_id );
		$ticket['meta'] = $ticket_meta;

		// Merge metadata fields into root ticket array for seamless API & frontend consumption
		$ticket['session_id']                   = ! empty( $ticket_meta['session_id'] ) ? (string) $ticket_meta['session_id'] : '';
		$ticket['guest_access_token']           = ! empty( $ticket_meta['guest_access_token'] ) ? (string) $ticket_meta['guest_access_token'] : '';
		$ticket['control_mode']                 = ! empty( $ticket_meta['control_mode'] ) ? (string) $ticket_meta['control_mode'] : 'ai';
		$ticket['origin_type']                  = ! empty( $ticket_meta['origin_type'] ) ? (string) $ticket_meta['origin_type'] : 'chatbot';
		$ticket['reply_surface']                = ! empty( $ticket_meta['reply_surface'] ) ? (string) $ticket_meta['reply_surface'] : 'chatbot_widget';
		$ticket['interaction_type']             = ! empty( $ticket_meta['interaction_type'] ) ? (string) $ticket_meta['interaction_type'] : 'SUPPORT_TICKET';
		$ticket['category_id']                  = ! empty( $ticket_meta['category_id'] ) ? absint( $ticket_meta['category_id'] ) : 0;
		$ticket['product']                      = ! empty( $ticket_meta['product'] ) ? (string) $ticket_meta['product'] : ( ! empty( $ticket_meta['product_name'] ) ? (string) $ticket_meta['product_name'] : '' );
		$ticket['assigned_agent_id']            = ! empty( $ticket_meta['assigned_agent_id'] ) ? absint( $ticket_meta['assigned_agent_id'] ) : 0;
		$ticket['assigned_team_id']             = ! empty( $ticket_meta['assigned_team_id'] ) ? absint( $ticket_meta['assigned_team_id'] ) : 0;
		$ticket['ai_classification_confidence'] = isset( $ticket_meta['ai_classification_confidence'] ) ? floatval( $ticket_meta['ai_classification_confidence'] ) : 0.0;
		$ticket['ai_summary']                   = ! empty( $ticket_meta['ai_summary'] ) ? (string) $ticket_meta['ai_summary'] : '';

		// Category info
		$ticket['category_name']  = '';
		$ticket['category_color'] = '#4F46E5';
		if ( ! empty( $ticket['category_id'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$cat_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT name, color FROM `$table_terms` WHERE id = %d AND taxonomy_slug = 'category'", $ticket['category_id'] ),
				ARRAY_A
			);
			if ( $cat_row ) {
				$ticket['category_name']  = $cat_row['name'];
				$ticket['category_color'] = $cat_row['color'];
			}
		}

		// Agent info
		$ticket['agent_wp_user_id'] = 0;
		$ticket['agent_role']       = '';
		$ticket['agent_name']       = __( 'Unassigned', 'dragwyb-click-to-chat' );
		$ticket['agent_avatar']     = '';

		if ( ! empty( $ticket['assigned_agent_id'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$agent_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT wp_user_id, support_role FROM `$table_agents` WHERE id = %d", $ticket['assigned_agent_id'] ),
				ARRAY_A
			);
			if ( $agent_row && ! empty( $agent_row['wp_user_id'] ) ) {
				$ticket['agent_wp_user_id'] = (int) $agent_row['wp_user_id'];
				$ticket['agent_role']       = $agent_row['support_role'];
				$agent_user                 = get_userdata( $agent_row['wp_user_id'] );
				$ticket['agent_name']       = $agent_user ? $agent_user->display_name : 'Agent #' . $ticket['assigned_agent_id'];
				$ticket['agent_avatar']     = get_avatar_url( $agent_row['wp_user_id'], array( 'size' => 48 ) );
			}
		}

		$ticket['tags']   = DCTC_Support_Tag_Service::get_ticket_tags( $ticket_id );
		$ticket['events'] = DCTC_Support_Event_Service::get_events( $ticket_id, 'ASC', 50 );
		$ticket['notes']  = DCTC_Support_Note_Service::get_notes( $ticket_id );

		// Retrieve conversation messages from sessions table (by session_id or support_ticket_id)
		$session_row = null;
		if ( ! empty( $ticket['session_id'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$session_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, content, updated_at FROM `$table_sessions` WHERE session_id = %s LIMIT 1", $ticket['session_id'] ),
				ARRAY_A
			);
		}
		if ( ! $session_row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$session_row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, content, updated_at FROM `$table_sessions` WHERE support_ticket_id = %d LIMIT 1", $ticket_id ),
				ARRAY_A
			);
		}

		$ticket['has_session']   = ! empty( $session_row );
		$session_content         = $session_row ? $session_row['content'] : '';
		$ticket['messages']      = ! empty( $session_content ) ? json_decode( $session_content, true ) : array();
		if ( empty( $ticket['messages'] ) ) {
			$meta_msgs = ! empty( $ticket_meta['_dctc_ticket_messages'] ) ? $ticket_meta['_dctc_ticket_messages'] : array();
			if ( is_array( $meta_msgs ) ) {
				$ticket['messages'] = $meta_msgs;
			}
		}
		$ticket['messages']      = is_array( $ticket['messages'] ) ? $ticket['messages'] : array();
		$ticket['chat_count']    = count( $ticket['messages'] );
		$ticket['message_count'] = $ticket['chat_count'];

		// Phone resolution
		$ticket['customer_phone'] = '';
		if ( ! empty( $ticket_meta['customer_phone'] ) ) {
			$ticket['customer_phone'] = (string) $ticket_meta['customer_phone'];
		} elseif ( ! empty( $ticket_meta['phone'] ) ) {
			$ticket['customer_phone'] = (string) $ticket_meta['phone'];
		}

		$session_updated = ( $session_row && ! empty( $session_row['updated_at'] ) ) ? strtotime( $session_row['updated_at'] ) : 0;
		$now             = current_time( 'timestamp' );
		$ticket['is_session_active'] = $session_row && ( ( $now - $session_updated ) < 90 );

		// Active agent viewers list
		$viewing_users = ! empty( $ticket_meta['_agent_viewing_user_ids'] ) && is_array( $ticket_meta['_agent_viewing_user_ids'] ) ? $ticket_meta['_agent_viewing_user_ids'] : array();
		$active_viewers = array_values( array_filter( $viewing_users, function( $v ) use ( $now ) {
			return ! empty( $v['last_seen'] ) && ( $now - (int) $v['last_seen'] ) <= 35;
		} ) );
		$ticket['viewing_users']    = $active_viewers;
		$ticket['is_agent_viewing'] = ! empty( $active_viewers ) ? 1 : 0;

		// Instantiate OOP model to load AI useful content & WC info
		if ( class_exists( 'DCTC_Support_Ticket' ) ) {
			$ticket_obj = new DCTC_Support_Ticket( $ticket );
			$ticket['ai_useful_content'] = $ticket_obj->get_ai_useful_content();
			if ( empty( $ticket['customer_phone'] ) && ! empty( $ticket['ai_useful_content']['customer_phone'] ) ) {
				$ticket['customer_phone'] = $ticket['ai_useful_content']['customer_phone'];
			}
		}

		return $ticket;
	}

	/**
	 * Get OOP Ticket object for standard object-oriented operations.
	 *
	 * @param int|string|array $id_or_uuid_or_session Ticket ID, UUID, session ID, or data array.
	 * @return DCTC_Support_Ticket|null
	 */
	public static function get_ticket_object( $id_or_uuid_or_session ) {
		if ( class_exists( 'DCTC_Support_Ticket' ) ) {
			return DCTC_Support_Ticket::get( $id_or_uuid_or_session );
		}
		return null;
	}

	/**
	 * Get ticket by connected session ID.
	 *
	 * @param string $session_id Conversation session identifier.
	 * @return array<string, mixed>|null
	 */
	public static function get_ticket_by_session_id( $session_id ) {
		global $wpdb;
		$table_sessions    = $wpdb->prefix . 'dctc_ai_sessions';
		$table_ticket_meta = $wpdb->prefix . 'dctc_support_ticket_meta';

		$session_id = sanitize_text_field( $session_id );
		if ( empty( $session_id ) ) {
			return null;
		}

		// 1. Check sessions table support_ticket_id
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ticket_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT support_ticket_id FROM `$table_sessions` WHERE session_id = %s AND support_ticket_id > 0 LIMIT 1", $session_id )
		);

		// 2. Check ticket meta session_id
		if ( ! $ticket_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ticket_id = $wpdb->get_var(
				$wpdb->prepare( "SELECT ticket_id FROM `$table_ticket_meta` WHERE meta_key = 'session_id' AND meta_value = %s ORDER BY meta_id DESC LIMIT 1", $session_id )
			);
		}

		if ( $ticket_id ) {
			return self::get_ticket( (int) $ticket_id );
		}

		return null;
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

		$ticket = self::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		$old_mode = ! empty( $ticket['control_mode'] ) ? $ticket['control_mode'] : 'ai';
		if ( $old_mode === $control_mode ) {
			return true;
		}

		// Update ticket meta
		self::update_ticket_meta( $ticket_id, 'control_mode', $control_mode );

		// Touch ticket updated_at
		$wpdb->update(
			$table_tickets,
			array( 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $ticket_id )
		);

		$session_id = ! empty( $ticket['session_id'] ) ? $ticket['session_id'] : '';

		if ( ! empty( $session_id ) ) {
			$wpdb->update(
				$table_sessions,
				array(
					'control_mode' => $control_mode,
					'updated_at'   => current_time( 'mysql' ),
				),
				array( 'session_id' => $session_id )
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

		$ticket = self::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		$old_agent_id = (int) ( ! empty( $ticket['assigned_agent_id'] ) ? $ticket['assigned_agent_id'] : 0 );
		if ( $old_agent_id === $agent_id ) {
			return true;
		}

		// Update ticket meta
		self::update_ticket_meta( $ticket_id, 'assigned_agent_id', $agent_id );
		if ( $team_id ) {
			self::update_ticket_meta( $ticket_id, 'assigned_team_id', $team_id );
		}

		// Touch ticket updated_at
		$wpdb->update(
			$table_tickets,
			array( 'updated_at' => current_time( 'mysql' ) ),
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
		if ( empty( $ticket_uuid ) || empty( $access_token ) ) {
			return false;
		}

		$ticket = self::get_ticket( sanitize_text_field( $ticket_uuid ) );
		if ( ! $ticket ) {
			return false;
		}

		$stored_token = ! empty( $ticket['guest_access_token'] ) ? $ticket['guest_access_token'] : self::get_ticket_meta( $ticket['id'], 'guest_access_token', true );

		if ( ! empty( $stored_token ) && hash_equals( (string) $stored_token, (string) $access_token ) ) {
			return $ticket;
		}

		return false;
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

		$ticket = self::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'dragwyb-click-to-chat' ) );
		}

		$session_id = ! empty( $ticket['session_id'] ) ? $ticket['session_id'] : '';

		// Read existing session content
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$raw_content = ! empty( $session_id ) ? $wpdb->get_var(
			$wpdb->prepare( "SELECT content FROM `$table_sessions` WHERE session_id = %s", $session_id )
		) : null;

		$messages = ! empty( $raw_content ) ? json_decode( $raw_content, true ) : array();
		if ( empty( $messages ) && ! empty( $ticket['messages'] ) ) {
			$messages = $ticket['messages'];
		}
		$messages = is_array( $messages ) ? $messages : array();

		$user         = $user_id ? get_userdata( $user_id ) : null;
		$display_name = $user ? $user->display_name : ( 'agent' === $sender_type ? 'Support Agent' : 'Customer' );

		$new_msg = array(
			'role'        => 'agent' === $sender_type ? 'assistant' : 'user',
			'sender_type' => $sender_type,
			'sender_name' => $display_name,
			'content'     => $message,
			'created_at'  => current_time( 'mysql' ),
		);

		$messages[] = $new_msg;
		$messages   = array_slice( $messages, -100 ); // keep last 100 messages

		if ( ! empty( $session_id ) ) {
			$wpdb->update(
				$table_sessions,
				array(
					'content'    => wp_json_encode( $messages ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'session_id' => $session_id )
			);
		}

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
					self::update_ticket_meta( $ticket_id, 'assigned_agent_id', absint( $agent_row['id'] ) );
					DCTC_Support_Agent_Service::update_workload( absint( $agent_row['id'] ) );
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
		$table_tickets     = $wpdb->prefix . 'dctc_support_tickets';
		$table_ticket_meta = $wpdb->prefix . 'dctc_support_ticket_meta';
		$table_agents      = $wpdb->prefix . 'dctc_support_agents';
		$table_events      = $wpdb->prefix . 'dctc_support_events';

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
		$total_open = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status NOT IN ('resolved', 'closed', 'trash')" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status IN ('pending', 'waiting_customer', 'waiting_agent', 'hold')" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_resolved = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status IN ('resolved', 'closed')" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$today_created = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE DATE(created_at) = %s AND status != 'trash'", $today ) );

		// AI Bot Tickets Count
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_ai_bot = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT t.id) FROM `$table_tickets` t 
			 INNER JOIN `$table_ticket_meta` tm ON t.id = tm.ticket_id 
			 WHERE ((tm.meta_key = 'origin_type' AND tm.meta_value IN ('chatbot', 'ai', 'ai_assistant'))
			    OR (tm.meta_key = 'session_id' AND tm.meta_value != '' AND tm.meta_value IS NOT NULL)
			    OR (tm.meta_key = 'control_mode' AND tm.meta_value = 'ai')) 
			   AND t.status != 'trash'"
		);

		// Assigned to me metrics
		$agent_ids_check = array_filter( array_unique( array( $agent_id, $user_id ) ) );
		$agent_placeholders = ! empty( $agent_ids_check ) ? implode( ',', array_map( 'absint', $agent_ids_check ) ) : '0';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$my_active = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT t.id) FROM `$table_tickets` t INNER JOIN `$table_ticket_meta` tm ON t.id = tm.ticket_id WHERE tm.meta_key = 'assigned_agent_id' AND tm.meta_value IN ($agent_placeholders) AND t.status IN ('open', 'pending', 'waiting_customer')"
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$my_today_assigned = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT t.id) FROM `$table_tickets` t INNER JOIN `$table_ticket_meta` tm ON t.id = tm.ticket_id WHERE tm.meta_key = 'assigned_agent_id' AND tm.meta_value IN ($agent_placeholders) AND DATE(t.created_at) = %s AND t.status != 'trash'",
				$today
			)
		);

		// If user is admin and has no individual assigned tickets, show site active open/pending
		if ( $is_admin && 0 === $my_active && $total_open > 0 ) {
			$my_active = $total_open;
			$my_today_assigned = $today_created;
		} elseif ( 0 === $my_today_assigned && $my_active > 0 ) {
			// If active tickets exist for agent, display active count so hero/card is meaningful
			$my_today_assigned = $my_active;
		}

		// Control mode breakdown
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$human_controlled = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT t.id) FROM `$table_tickets` t INNER JOIN `$table_ticket_meta` tm ON t.id = tm.ticket_id WHERE tm.meta_key = 'control_mode' AND tm.meta_value = 'human' AND t.status NOT IN ('resolved', 'closed', 'trash')"
		);
		$ai_controlled = max( 0, $total_open - $human_controlled );

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

		// Detailed status breakdown
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count_new = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status = 'new'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count_closed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status = 'closed'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count_resolved_only = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table_tickets` WHERE status = 'resolved'" );

		$status_breakdown = array(
			'new'      => $count_new,
			'open'     => $total_open,
			'pending'  => $total_pending,
			'resolved' => $count_resolved_only,
			'closed'   => $count_closed,
		);

		// 7-day daily trend calculations
		$daily_trends = array();
		for ( $i = 6; $i >= 0; $i-- ) {
			$day_ts   = strtotime( "-{$i} days", current_time( 'timestamp' ) );
			$day_date = date( 'Y-m-d', $day_ts );
			$day_lbl  = date( 'M d', $day_ts );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$day_new = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE DATE(created_at) = %s AND status != 'trash'", $day_date )
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$day_open = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE DATE(created_at) = %s AND status NOT IN ('resolved', 'closed', 'trash')", $day_date )
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$day_pending = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE DATE(created_at) = %s AND status IN ('pending', 'waiting_customer')", $day_date )
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$day_resolved = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `$table_tickets` WHERE DATE(updated_at) = %s AND status IN ('resolved', 'closed')", $day_date )
			);

			$daily_trends[] = array(
				'date'      => $day_lbl,
				'full_date' => $day_date,
				'new'       => $day_new,
				'open'      => $day_open,
				'pending'   => $day_pending,
				'resolved'  => $day_resolved,
			);
		}

		return array(
			'total_tickets'            => $total_tickets,
			'total_open'               => $total_open,
			'total_pending'            => $total_pending,
			'total_resolved'           => $total_resolved,
			'total_ai_bot'             => $total_ai_bot,
			'ai_bot_tickets'           => $total_ai_bot,
			'today_created'            => $today_created,
			'today_assigned_tickets'   => $my_today_assigned,
			'my_active_tickets'        => $my_active,
			'ai_controlled_tickets'    => $ai_controlled,
			'human_controlled_tickets' => $human_controlled,
			'agent'                    => $agent_profile,
			'recent_activity'          => is_array( $recent_events ) ? $recent_events : array(),
			'status_breakdown'         => $status_breakdown,
			'daily_trends'             => $daily_trends,
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

	/**
	 * Update general ticket properties (status, priority, subject, category_id, product, tags, assigned_agent_id).
	 *
	 * @param int|string $ticket_id_or_uuid Ticket ID or UUID.
	 * @param array      $data Key-value pairs to update.
	 * @param string     $actor_type Actor type ('agent', 'customer', 'system').
	 * @param int        $actor_id Actor WordPress user ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update_ticket_properties( $ticket_id_or_uuid, $data = array(), $actor_type = 'agent', $actor_id = 0 ) {
		global $wpdb;
		$ticket = self::get_ticket( $ticket_id_or_uuid );
		if ( ! $ticket ) {
			return new WP_Error( 'not_found', __( 'Ticket not found.', 'dragwyb-click-to-chat' ) );
		}

		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		$core_updates  = array();
		$actor_name    = 'Staff';

		if ( $actor_id ) {
			$u = get_userdata( $actor_id );
			if ( $u ) {
				$actor_name = $u->display_name;
			}
		}

		if ( isset( $data['status'] ) && ! empty( $data['status'] ) ) {
			$new_status = sanitize_key( $data['status'] );
			if ( $new_status !== $ticket['status'] ) {
				$core_updates['status'] = $new_status;
				if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
					DCTC_Support_Event_Service::log_event( $ticket['id'], 'status_changed', $ticket['status'], $new_status, $actor_type, $actor_id, $actor_name );
				}
			}
		}

		if ( isset( $data['priority'] ) && ! empty( $data['priority'] ) ) {
			$new_priority = sanitize_key( $data['priority'] );
			if ( $new_priority !== $ticket['priority'] ) {
				$core_updates['priority'] = $new_priority;
				if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
					DCTC_Support_Event_Service::log_event( $ticket['id'], 'priority_changed', $ticket['priority'], $new_priority, $actor_type, $actor_id, $actor_name );
				}
			}
		}

		if ( isset( $data['subject'] ) && ! empty( $data['subject'] ) ) {
			$core_updates['subject'] = sanitize_text_field( $data['subject'] );
		}

		if ( ! empty( $core_updates ) ) {
			$core_updates['updated_at'] = current_time( 'mysql' );
			$wpdb->update( $table_tickets, $core_updates, array( 'id' => $ticket['id'] ) );
		}

		// Update metadata properties
		if ( isset( $data['category_id'] ) ) {
			$cat_id = absint( $data['category_id'] );
			self::update_ticket_meta( $ticket['id'], 'category_id', $cat_id );
			if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
				DCTC_Support_Event_Service::log_event( $ticket['id'], 'category_changed', (string) $ticket['category_id'], (string) $cat_id, $actor_type, $actor_id, $actor_name );
			}
		}

		if ( isset( $data['product'] ) ) {
			$product_val = sanitize_text_field( $data['product'] );
			self::update_ticket_meta( $ticket['id'], 'product', $product_val );
			self::update_ticket_meta( $ticket['id'], 'product_name', $product_val );
			if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
				$prev_product = isset( $ticket['product'] ) ? $ticket['product'] : '';
				DCTC_Support_Event_Service::log_event( $ticket['id'], 'product_changed', $prev_product, $product_val, $actor_type, $actor_id, $actor_name );
			}
		}

		if ( isset( $data['assigned_agent_id'] ) ) {
			$agent_id = absint( $data['assigned_agent_id'] );
			self::update_ticket_meta( $ticket['id'], 'assigned_agent_id', $agent_id );
			if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
				DCTC_Support_Event_Service::log_event( $ticket['id'], 'agent_assigned', (string) $ticket['assigned_agent_id'], (string) $agent_id, $actor_type, $actor_id, $actor_name );
			}
		}

		if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
			if ( class_exists( 'DCTC_Support_Tag_Service' ) ) {
				DCTC_Support_Tag_Service::set_ticket_tags( $ticket['id'], $data['tags'] );
			}
			self::update_ticket_meta( $ticket['id'], 'tags', $data['tags'] );
		}

		return self::get_ticket( $ticket['id'] );
	}
}

