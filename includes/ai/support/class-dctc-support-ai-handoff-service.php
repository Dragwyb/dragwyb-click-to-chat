<?php
/**
 * DCTC Support AI Handoff Service
 *
 * Coordinates AI-to-human escalation, human-to-AI handback,
 * race-condition prevention during generation, and control mode state checks.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_AI_Handoff_Service
 */
class DCTC_Support_AI_Handoff_Service {

	/**
	 * Check if AI automatic response should be blocked for a given session.
	 *
	 * @param string $session_id Conversation session identifier.
	 * @return bool True if AI should NOT auto-reply.
	 */
	public static function should_block_ai_response( $session_id ) {
		global $wpdb;
		$session_id = sanitize_text_field( $session_id );
		if ( empty( $session_id ) ) {
			return false;
		}

		$ticket = class_exists( 'DCTC_Support_Ticket_Service' )
			? DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id )
			: null;

		if ( ! $ticket ) {
			return false;
		}

		$ticket_id    = (int) $ticket['id'];
		$control_mode = ! empty( $ticket['control_mode'] ) ? $ticket['control_mode'] : 'ai';

		if ( in_array( $ticket['status'], array( 'resolved', 'closed' ), true ) ) {
			return true;
		}

		// If in human mode, check agent viewing state and response wait time
		if ( 'human' === $control_mode ) {
			// 1. Check if agent is currently viewing this ticket
			$is_agent_viewing = false;
			if ( $ticket_id > 0 && class_exists( 'DCTC_Support_Ticket_Service' ) ) {
				$viewing_meta  = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, '_agent_viewing', true );
				$viewing_users = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, '_agent_viewing_user_ids', true );
				$now           = current_time( 'timestamp' );

				if ( is_array( $viewing_users ) && ! empty( $viewing_users ) ) {
					$active = array_filter(
						$viewing_users,
						function ( $v ) use ( $now ) {
							return ! empty( $v['last_seen'] ) && ( $now - (int) $v['last_seen'] ) <= 35;
						}
					);
					if ( ! empty( $active ) ) {
						$is_agent_viewing = true;
					}
				} elseif ( '1' === (string) $viewing_meta || 1 === (int) $viewing_meta ) {
					$is_agent_viewing = true;
				}
			}

			// If agent is actively viewing the ticket conversation, do not fallback to AI
			if ( $is_agent_viewing ) {
				return true;
			}

			// 2. Fetch configurable max wait time for human agent response (default 60s / 1 min)
			$settings         = get_option( 'dctc_ai_settings', array() );
			$chatbot_settings = ! empty( $settings['chatbot'] ) && is_array( $settings['chatbot'] ) ? $settings['chatbot'] : array();
			$max_wait_time    = isset( $chatbot_settings['human_agent_max_wait_time'] ) ? max( 10, intval( $chatbot_settings['human_agent_max_wait_time'] ) ) : 60;
			$last_viewed_at   = (int) DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, '_agent_last_viewed_at', true );
			$now              = current_time( 'timestamp' );
			$elapsed          = $now - $last_viewed_at;

			// If within grace period, keep blocking AI response to give human agent time
			if ( $elapsed >= 0 && $elapsed < $max_wait_time ) {
				return true;
			}

			// If agent did not reply within max wait time and is not viewing, auto fallback to AI!
			if ( $ticket_id > 0 && class_exists( 'DCTC_Support_Ticket_Service' ) ) {
				DCTC_Support_Ticket_Service::set_control_mode( $ticket_id, 'ai', 'system', 0, 'Auto-Fallback: Agent wait timeout' );
			}

			return false;
		}

		return false;
	}

	/**
	 * Agent takes control of a ticket/conversation from AI.
	 *
	 * @param int $ticket_id         Ticket ID.
	 * @param int $agent_wp_user_id  Agent WP User ID.
	 * @return bool|WP_Error
	 */
	public static function take_control( $ticket_id, $agent_wp_user_id = 0 ) {
		$agent_wp_user_id = $agent_wp_user_id ? absint( $agent_wp_user_id ) : get_current_user_id();

		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'take_ai_control', $agent_wp_user_id ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to take AI control.', 'dragwyb-click-to-chat' ) );
		}

		$user       = get_userdata( $agent_wp_user_id );
		$agent_name = $user ? $user->display_name : 'Support Agent';

		$updated = DCTC_Support_Ticket_Service::set_control_mode(
			$ticket_id,
			'human',
			'agent',
			$agent_wp_user_id,
			$agent_name
		);

		return $updated ? true : new WP_Error( 'update_failed', __( 'Could not take control.', 'dragwyb-click-to-chat' ) );
	}

	/**
	 * Agent gives control back to AI.
	 *
	 * @param int $ticket_id         Ticket ID.
	 * @param int $agent_wp_user_id  Agent WP User ID.
	 * @return bool|WP_Error
	 */
	public static function give_control_to_ai( $ticket_id, $agent_wp_user_id = 0 ) {
		$agent_wp_user_id = $agent_wp_user_id ? absint( $agent_wp_user_id ) : get_current_user_id();

		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'release_ai_control', $agent_wp_user_id ) ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to release AI control.', 'dragwyb-click-to-chat' ) );
		}

		$user       = get_userdata( $agent_wp_user_id );
		$agent_name = $user ? $user->display_name : 'Support Agent';

		$updated = DCTC_Support_Ticket_Service::set_control_mode(
			$ticket_id,
			'ai',
			'agent',
			$agent_wp_user_id,
			$agent_name
		);

		return $updated ? true : new WP_Error( 'update_failed', __( 'Could not release control.', 'dragwyb-click-to-chat' ) );
	}

	/**
	 * Handle customer message when HUMAN_CONTROL is active (saves message & notifies agent).
	 *
	 * @param string $session_id Session identifier.
	 * @param string $prompt     Customer message.
	 * @param string $email      Customer email.
	 * @return array<string, mixed>
	 */
	public static function handle_customer_message_in_human_mode( $session_id, $prompt, $email = '' ) {
		global $wpdb;
		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';
		$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';

		$session_id = sanitize_text_field( $session_id );
		$prompt     = sanitize_textarea_field( $prompt );

		$ticket = null;
		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$ticket = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$session = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table_sessions` WHERE session_id = %s", $session_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$user_id      = get_current_user_id();
		$current_user = $user_id ? get_userdata( $user_id ) : null;
		$sender_name  = $current_user ? $current_user->display_name : ( ! empty( $email ) ? $email : 'Customer' );
		$msg_uuid     = wp_generate_uuid4();

		$new_user_msg = array(
			'id'          => $msg_uuid,
			'uuid'        => $msg_uuid,
			'role'        => 'user',
			'sender_type' => 'customer',
			'sender_name' => $sender_name,
			'content'     => $prompt,
			'created_at'  => current_time( 'mysql', true ),
		);

		$ticket_id  = 0;
		$agent_name = __( 'a support specialist', 'dragwyb-click-to-chat' );

		if ( $ticket && ! empty( $ticket['id'] ) ) {
			$ticket_id = (int) $ticket['id'];
			if ( ! empty( $ticket['agent_name'] ) && __( 'Unassigned', 'dragwyb-click-to-chat' ) !== $ticket['agent_name'] ) {
				$agent_name = $ticket['agent_name'];
			}

			// Append to ticket messages (Primary source of truth)
			DCTC_Support_Ticket_Service::append_ticket_messages( $ticket_id, array( $new_user_msg ) );
			DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'chatbot_last_message_time', current_time( 'timestamp' ) );
		}

		// Persist to session table ONLY if save_chat is enabled
		$ai_settings  = class_exists( 'DCTC_AI_Settings_Handler' ) ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : get_option( 'dctc_ai_settings', array() );
		$save_enabled = ! empty( $ai_settings['chatbot']['save_chat'] );

		if ( $save_enabled && $session ) {
			$session_messages   = ( ! empty( $session['content'] ) ) ? json_decode( $session['content'], true ) : array();
			$session_messages   = is_array( $session_messages ) ? $session_messages : array();
			$session_messages[] = $new_user_msg;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_sessions,
				array(
					'content'    => wp_json_encode( $session_messages ),
					'updated_at' => current_time( 'mysql', true ),
				),
				array( 'session_id' => $session_id )
			);
		}

		if ( $ticket && $ticket_id ) {

				// If ticket was resolved/closed, reopen it
			if ( in_array( $ticket['status'], array( 'resolved', 'closed' ), true ) ) {
				DCTC_Support_Ticket_Service::change_status( $ticket_id, 'open', 'customer', $user_id, $sender_name );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_tickets,
					array(
						'status'                => 'waiting_agent',
						'customer_last_seen_at' => current_time( 'mysql', true ),
						'updated_at'            => current_time( 'mysql', true ),
					),
					array( 'id' => $ticket_id )
				);
			}

				DCTC_Support_Event_Service::log_event(
					$ticket_id,
					'customer_replied',
					'customer',
					$user_id,
					$sender_name,
					null,
					$prompt
				);

				// Trigger delayed notification if agent doesn't view ticket within 1 minute
			if ( class_exists( 'DCTC_Support_Notification_Service' ) ) {
				DCTC_Support_Notification_Service::notify_customer_reply( $ticket_id, $prompt );
			}
		}

		$ticket_info = array(
			'id'           => (int) $ticket_id,
			'ticket_id'    => (int) $ticket_id,
			'status'       => $ticket ? $ticket['status'] : 'waiting_agent',
			'control_mode' => 'human',
			'agent_name'   => $agent_name,
		);

		return array(
			'success'          => true,
			'message'          => '', // No static text bubble; frontend renders typing dots and waiting animation
			'control_mode'     => 'human',
			'handler'          => 'human_support',
			'session_id'       => $session_id,
			'messages'         => $messages,
			'is_human_handled' => true,
			'waiting_agent'    => true,
			'has_ticket'       => (bool) $ticket_id,
			'ticket'           => $ticket_info,
		);
	}
}
