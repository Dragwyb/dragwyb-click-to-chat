<?php
/**
 * DCTC Support Notification Service
 *
 * Handles agent and customer email notifications, active session presence suppression,
 * 1-minute delayed notification dispatch with view-checking, HTML email templating, and delivery audit logging.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Notification_Service
 */
class DCTC_Support_Notification_Service {

	/**
	 * Hook delayed cron actions, async notifications, and transient fallbacks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'dctc_support_delayed_customer_reply_notif', array( __CLASS__, 'process_delayed_customer_reply' ), 10, 3 );
		add_action( 'dctc_support_delayed_agent_reply_notif', array( __CLASS__, 'process_delayed_agent_reply' ), 10, 3 );

		// Asynchronous notification cron handler
		add_action( 'dctc_support_async_dispatch_notification', array( __CLASS__, 'cron_dispatch_notification' ), 10, 2 );

		// Fallback check on request lifecycle (init and shutdown) for environments where WP-Cron is blocked
		add_action( 'init', array( __CLASS__, 'check_pending_transient_notifications' ) );
		add_action( 'shutdown', array( __CLASS__, 'check_pending_transient_notifications' ) );
	}

	/**
	 * Schedule an email notification asynchronously using WP-Cron with a transient callback fallback.
	 *
	 * Prevents HTTP request blocking during chat and ticket workflows.
	 * Sets a transient callback; if WP-Cron executes, it clears the transient first before sending.
	 * If WP-Cron is blocked on the server, the transient fallback executes after a 30-second delay gap.
	 *
	 * @param string $event_type Notification event type slug.
	 * @param array  $payload    Arguments required for sending.
	 * @return bool
	 */
	public static function schedule_async_notification( $event_type, array $payload ) {
		$notif_id      = 'dctc_notif_' . sanitize_key( $event_type ) . '_' . wp_generate_password( 8, false, false );
		$transient_key = 'dctc_ntf_' . md5( $notif_id );
		$now           = time();

		$data = array(
			'notif_id'      => $notif_id,
			'transient_key' => $transient_key,
			'event_type'    => $event_type,
			'payload'       => $payload,
			'created_at'    => $now,
		);

		// Store notification payload in transient with 1-hour expiration
		set_transient( $transient_key, $data, HOUR_IN_SECONDS );

		// Register in persistent queue registry
		$queue              = get_option( 'dctc_pending_notification_queue', array() );
		$queue[ $notif_id ] = array(
			'transient_key' => $transient_key,
			'created_at'    => $now,
		);
		update_option( 'dctc_pending_notification_queue', $queue, false );

		// Schedule single event immediately via WP-Cron
		wp_schedule_single_event(
			$now,
			'dctc_support_async_dispatch_notification',
			array( $notif_id, $transient_key )
		);

		return true;
	}

	/**
	 * Process async notification triggered via WP-Cron.
	 *
	 * Clears transient first before sending to prevent duplicate dispatch.
	 *
	 * @param string $notif_id      Unique notification ID.
	 * @param string $transient_key Transient key.
	 * @return bool
	 */
	public static function cron_dispatch_notification( $notif_id, $transient_key ) {
		$data = get_transient( $transient_key );
		if ( empty( $data ) || ! is_array( $data ) ) {
			// Transient was already cleared/processed by fallback or cancelled
			self::remove_from_pending_queue( $notif_id );
			return false;
		}

		// Prevent race condition across concurrent processes
		$lock_key = 'dctc_lck_' . md5( $notif_id );
		if ( get_transient( $lock_key ) ) {
			return false;
		}
		set_transient( $lock_key, 1, 60 );

		// Clear transient FIRST before sending mail
		delete_transient( $transient_key );
		self::remove_from_pending_queue( $notif_id );

		// Now send mail
		return self::dispatch_notification_payload( $data['event_type'], $data['payload'] );
	}

	/**
	 * Transient fallback processor.
	 *
	 * Checks for any pending notifications where WP-Cron may have failed or been blocked.
	 * Enforces at least a 30-second delay gap so WP-Cron has time to trigger first.
	 * If the 30-second gap has elapsed and transient still exists, clears transient first then dispatches mail.
	 *
	 * @return void
	 */
	public static function check_pending_transient_notifications() {
		static $already_checked = false;
		if ( $already_checked ) {
			return;
		}
		$already_checked = true;

		$queue = get_option( 'dctc_pending_notification_queue', array() );
		if ( empty( $queue ) || ! is_array( $queue ) ) {
			return;
		}

		$now         = time();
		$has_changes = false;

		foreach ( $queue as $notif_id => $item ) {
			$created_at = isset( $item['created_at'] ) ? (int) $item['created_at'] : 0;

			// Enforce 30-second delay gap: WP-Cron is allowed 30 seconds to trigger first
			if ( ( $now - $created_at ) < 30 ) {
				continue;
			}

			// Drop stale items older than 24 hours to prevent memory leaks or ancient email spam
			if ( ( $now - $created_at ) > ( DAY_IN_SECONDS ) ) {
				if ( ! empty( $item['transient_key'] ) ) {
					delete_transient( $item['transient_key'] );
				}
				unset( $queue[ $notif_id ] );
				$has_changes = true;
				continue;
			}

			$transient_key = isset( $item['transient_key'] ) ? $item['transient_key'] : '';
			if ( ! $transient_key ) {
				unset( $queue[ $notif_id ] );
				$has_changes = true;
				continue;
			}

			// Atomic lock check to prevent race conditions
			$lock_key = 'dctc_lck_' . md5( $notif_id );
			if ( get_transient( $lock_key ) ) {
				continue;
			}

			$data = get_transient( $transient_key );
			if ( empty( $data ) || ! is_array( $data ) ) {
				// Transient already cleared by cron
				unset( $queue[ $notif_id ] );
				$has_changes = true;
				continue;
			}

			// Acquire processing lock
			set_transient( $lock_key, 1, 60 );

			// Clear transient FIRST before sending mail
			delete_transient( $transient_key );
			unset( $queue[ $notif_id ] );
			$has_changes = true;

			// Send mail from fallback transient
			if ( ! empty( $data['event_type'] ) && isset( $data['payload'] ) ) {
				self::dispatch_notification_payload( $data['event_type'], $data['payload'] );
			}
		}

		if ( $has_changes ) {
			update_option( 'dctc_pending_notification_queue', $queue, false );
		}
	}

	/**
	 * Dispatch email based on event type.
	 *
	 * @param string $event_type Event slug.
	 * @param array  $payload    Event arguments.
	 * @return bool
	 */
	public static function dispatch_notification_payload( $event_type, array $payload ) {
		switch ( $event_type ) {
			case 'ticket_assigned':
				$ticket_id = isset( $payload['ticket_id'] ) ? (int) $payload['ticket_id'] : 0;
				$agent_id  = isset( $payload['agent_id'] ) ? (int) $payload['agent_id'] : 0;
				return self::process_ticket_assigned( $ticket_id, $agent_id );

			case 'guest_ticket_created':
				$ticket_id = isset( $payload['ticket_id'] ) ? (int) $payload['ticket_id'] : 0;
				return self::process_guest_ticket_created( $ticket_id );

			case 'lead_email':
				$lead = isset( $payload['lead'] ) && is_array( $payload['lead'] ) ? $payload['lead'] : array();
				return self::process_lead_email( $lead );

			default:
				return false;
		}
	}

	/**
	 * Remove notification item from pending queue option.
	 *
	 * @param string $notif_id Notification ID.
	 * @return void
	 */
	private static function remove_from_pending_queue( $notif_id ) {
		$queue = get_option( 'dctc_pending_notification_queue', array() );
		if ( is_array( $queue ) && isset( $queue[ $notif_id ] ) ) {
			unset( $queue[ $notif_id ] );
			update_option( 'dctc_pending_notification_queue', $queue, false );
		}
	}

	/**
	 * Send notification when a ticket is assigned to an agent.
	 *
	 * Scheduled asynchronously via WP-Cron with a transient callback fallback to prevent blocking chat/API response.
	 *
	 * @param int  $ticket_id Ticket ID.
	 * @param int  $agent_id  Support Agent ID.
	 * @param bool $immediate Whether to send immediately without scheduling.
	 * @return bool
	 */
	public static function notify_ticket_assigned( $ticket_id, $agent_id, $immediate = false ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		$agent  = DCTC_Support_Agent_Service::get_agent_by_id( $agent_id );

		if ( ! $ticket || ! $agent || empty( $agent['user_email'] ) ) {
			return false;
		}

		if ( ! self::should_notify( 'agent', 'agent_assignment', $ticket, array( 'agent' => $agent ) ) ) {
			return false;
		}

		if ( $immediate ) {
			return self::process_ticket_assigned( $ticket_id, $agent_id );
		}

		return self::schedule_async_notification(
			'ticket_assigned',
			array(
				'ticket_id' => (int) $ticket_id,
				'agent_id'  => (int) $agent_id,
			)
		);
	}

	/**
	 * Process and dispatch agent ticket assignment email.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $agent_id  Support Agent ID.
	 * @return bool
	 */
	public static function process_ticket_assigned( $ticket_id, $agent_id ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		$agent  = DCTC_Support_Agent_Service::get_agent_by_id( $agent_id );

		if ( ! $ticket || ! $agent || empty( $agent['user_email'] ) ) {
			return false;
		}

		if ( ! self::should_notify( 'agent', 'agent_assignment', $ticket, array( 'agent' => $agent ) ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$admin_url = admin_url( 'admin.php?page=dragwyb-support-tickets' );
		$ticket_no = ! empty( $ticket['id'] ) ? $ticket['id'] : 0;
		$subject   = sprintf( '[%s] New Support Ticket Assigned: #%d - %s', $site_name, $ticket_no, $ticket['subject'] );

		$content  = '<h2>' . esc_html__( 'A new support ticket has been assigned to you', 'dragwyb-click-to-chat' ) . '</h2>';
		$content .= '<p><strong>' . esc_html__( 'Ticket:', 'dragwyb-click-to-chat' ) . '</strong> #' . esc_html( $ticket_no ) . ' - ' . esc_html( $ticket['subject'] ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Customer:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : $ticket['customer_email'] ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Priority:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( ucfirst( $ticket['priority'] ) ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Origin:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket['origin_type'] ) . '</p>';
		$content .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:#4F46E5;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;">' . esc_html__( 'View Ticket in Support Center', 'dragwyb-click-to-chat' ) . '</a></p>';

		return self::send_email( $agent['user_email'], $subject, $content, $ticket['id'], 'agent', $agent['id'], 'agent_assignment' );
	}

	/**
	 * Send notification when a customer replies (Delayed 1 minute to check if agent views ticket).
	 *
	 * @param int    $ticket_id   Ticket ID.
	 * @param string $reply_text Customer message.
	 * @param bool   $immediate   Whether to bypass 1-minute delay (for tests/forced dispatch).
	 * @return bool
	 */
	public static function notify_customer_reply( $ticket_id, $reply_text, $immediate = false ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		$sent_time = time();

		if ( $immediate ) {
			return self::process_delayed_customer_reply( $ticket_id, $reply_text, $sent_time );
		}

		// Schedule single event 1 minute in the future
		wp_schedule_single_event(
			$sent_time + 60,
			'dctc_support_delayed_customer_reply_notif',
			array( (int) $ticket_id, (string) $reply_text, $sent_time )
		);

		return true;
	}

	/**
	 * Process delayed customer reply notification after 1 minute.
	 *
	 * @param int    $ticket_id  Ticket ID.
	 * @param string $reply_text Customer message content.
	 * @param int    $sent_time  Timestamp when customer message was sent.
	 * @return bool
	 */
	public static function process_delayed_customer_reply( $ticket_id, $reply_text, $sent_time = 0 ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		// If ticket is resolved/closed, no need to notify
		if ( in_array( $ticket['status'], array( 'resolved', 'closed' ), true ) ) {
			return false;
		}

		$sent_time = $sent_time ? (int) $sent_time : ( time() - 60 );

		// 1. Check if an agent viewed or is actively viewing the ticket within/after that 1 minute
		$agent_viewed = false;

		$agent_last_viewed = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, '_agent_last_viewed_at', true );
		if ( ! empty( $agent_last_viewed ) && (int) $agent_last_viewed >= $sent_time ) {
			$agent_viewed = true;
		}

		if ( ! $agent_viewed ) {
			$stored_viewers = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, '_agent_viewing_user_ids', true );
			if ( is_array( $stored_viewers ) ) {
				foreach ( $stored_viewers as $viewer ) {
					if ( is_array( $viewer ) && ! empty( $viewer['last_seen'] ) && (int) $viewer['last_seen'] >= $sent_time ) {
						$agent_viewed = true;
						break;
					}
				}
			}
		}

		// 2. Check if an agent already replied after the customer's message
		if ( ! $agent_viewed && ! empty( $ticket['messages'] ) && is_array( $ticket['messages'] ) ) {
			foreach ( array_reverse( $ticket['messages'] ) as $msg ) {
				if ( is_array( $msg ) && ( ( isset( $msg['sender_type'] ) && in_array( $msg['sender_type'], array( 'agent', 'human_agent' ), true ) ) ) ) {
					$msg_time = ! empty( $msg['created_at'] ) ? strtotime( $msg['created_at'] ) : 0;
					if ( $msg_time >= $sent_time ) {
						$agent_viewed = true;
						break;
					}
				}
			}
		}

		// If agent has viewed or replied within 1 minute, suppress notification
		if ( $agent_viewed ) {
			self::log_suppressed(
				$ticket['id'],
				'customer_reply',
				'agent',
				! empty( $ticket['assigned_agent_id'] ) ? $ticket['assigned_agent_id'] : 0,
				'',
				'agent_viewed_within_1_minute'
			);
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$admin_url = admin_url( 'admin.php?page=dragwyb-support-tickets' );

		// 3. If ticket is assigned to an agent, send email to assigned agent
		if ( ! empty( $ticket['assigned_agent_id'] ) ) {
			$agent = DCTC_Support_Agent_Service::get_agent_by_id( $ticket['assigned_agent_id'] );
			if ( $agent && ! empty( $agent['user_email'] ) ) {
				if ( ! self::should_notify( 'agent', 'customer_reply', $ticket, array( 'agent' => $agent ) ) ) {
					return false;
				}

				$ticket_no = ! empty( $ticket['id'] ) ? $ticket['id'] : 0;
				$subject   = sprintf( '[%s] New Customer Reply on Ticket #%d: %s', $site_name, $ticket_no, $ticket['subject'] );
				$content   = '<h2>' . esc_html__( 'Customer has sent a new reply', 'dragwyb-click-to-chat' ) . '</h2>';
				$content  .= '<p><strong>' . esc_html__( 'Ticket:', 'dragwyb-click-to-chat' ) . '</strong> #' . esc_html( $ticket_no ) . ' - ' . esc_html( $ticket['subject'] ) . '</p>';
				$content  .= '<p><strong>' . esc_html__( 'Customer:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : $ticket['customer_email'] ) . '</p>';
				$content  .= '<div style="background:#F3F4F6;border-left:4px solid #4F46E5;padding:12px;margin:15px 0;font-style:italic;">' . nl2br( esc_html( $reply_text ) ) . '</div>';
				$content  .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:#4F46E5;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;">' . esc_html__( 'Open Ticket & Reply', 'dragwyb-click-to-chat' ) . '</a></p>';

				return self::send_email( $agent['user_email'], $subject, $content, $ticket['id'], 'agent', $agent['id'], 'customer_reply' );
			}
		}

		// 4. If no agent is assigned, send notification to admin
		$admin_email = self::get_admin_notification_email();
		if ( empty( $admin_email ) ) {
			return false;
		}

		if ( ! self::should_notify( 'admin', 'customer_reply', $ticket ) ) {
			return false;
		}

		$ticket_no = ! empty( $ticket['id'] ) ? $ticket['id'] : 0;
		$subject   = sprintf( '[%s] [Unassigned] New Customer Message on Ticket #%d: %s', $site_name, $ticket_no, $ticket['subject'] );
		$content   = '<h2>' . esc_html__( 'New Customer Message on Unassigned Ticket', 'dragwyb-click-to-chat' ) . '</h2>';
		$content  .= '<p><strong>' . esc_html__( 'Ticket:', 'dragwyb-click-to-chat' ) . '</strong> #' . esc_html( $ticket_no ) . ' - ' . esc_html( $ticket['subject'] ) . '</p>';
		$content  .= '<p><strong>' . esc_html__( 'Customer:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : $ticket['customer_email'] ) . '</p>';
		$content  .= '<p><strong>' . esc_html__( 'Status:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( ucfirst( str_replace( '_', ' ', $ticket['status'] ) ) ) . ' (' . esc_html__( 'Unassigned', 'dragwyb-click-to-chat' ) . ')</p>';
		$content  .= '<div style="background:#F3F4F6;border-left:4px solid #4F46E5;padding:12px;margin:15px 0;font-style:italic;">' . nl2br( esc_html( $reply_text ) ) . '</div>';
		$content  .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:#4F46E5;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;">' . esc_html__( 'Assign & Reply in Support Center', 'dragwyb-click-to-chat' ) . '</a></p>';

		return self::send_email( $admin_email, $subject, $content, $ticket['id'], 'admin', 0, 'customer_reply_unassigned' );
	}

	/**
	 * Send notification to customer when an agent replies (Delayed 1 minute to check if user views ticket/chat).
	 *
	 * @param int    $ticket_id   Ticket ID.
	 * @param string $reply_text Agent message.
	 * @param bool   $immediate   Whether to bypass 1-minute delay (for tests/forced dispatch).
	 * @return bool
	 */
	public static function notify_agent_reply( $ticket_id, $reply_text, $immediate = false ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket || empty( $ticket['customer_email'] ) ) {
			return false;
		}

		$sent_time = time();

		if ( $immediate ) {
			return self::process_delayed_agent_reply( $ticket_id, $reply_text, $sent_time );
		}

		// Schedule single event 1 minute in the future
		wp_schedule_single_event(
			$sent_time + 60,
			'dctc_support_delayed_agent_reply_notif',
			array( (int) $ticket_id, (string) $reply_text, $sent_time )
		);

		return true;
	}

	/**
	 * Process delayed agent reply notification after 1 minute.
	 *
	 * @param int    $ticket_id  Ticket ID.
	 * @param string $reply_text Agent message content.
	 * @param int    $sent_time  Timestamp when agent message was sent.
	 * @return bool
	 */
	public static function process_delayed_agent_reply( $ticket_id, $reply_text, $sent_time = 0 ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket || empty( $ticket['customer_email'] ) ) {
			return false;
		}

		$sent_time = $sent_time ? (int) $sent_time : ( time() - 60 );

		// Check if customer viewed the ticket or was active in session within/after that 1 minute
		$customer_viewed = false;
		if ( ! empty( $ticket['customer_last_seen_at'] ) ) {
			$last_seen = strtotime( $ticket['customer_last_seen_at'] );
			if ( $last_seen >= $sent_time ) {
				$customer_viewed = true;
			}
		}

		// If customer has viewed or was active in chat within 1 minute, suppress email notification
		if ( $customer_viewed ) {
			self::log_suppressed(
				$ticket['id'],
				'agent_reply',
				'customer',
				$ticket['customer_wp_user_id'],
				$ticket['customer_email'],
				'customer_viewed_within_1_minute'
			);
			return false;
		}

		if ( ! self::should_notify( 'customer', 'agent_reply', $ticket, array( 'reply_text' => $reply_text ) ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$ticket_no = ! empty( $ticket['id'] ) ? $ticket['id'] : 0;
		$subject   = sprintf( '[%s] Support Agent Replied to Ticket #%d: %s', $site_name, $ticket_no, $ticket['subject'] );

		$content = '<h2>' . esc_html__( 'We have an update on your support request', 'dragwyb-click-to-chat' ) . '</h2>';
		/* translators: %s: Customer name */
		$content .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'dragwyb-click-to-chat' ), esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : 'there' ) ) . '</p>';
		/* translators: 1: Ticket number, 2: Ticket subject */
		$content .= '<p>' . sprintf( esc_html__( 'Our support agent has responded to your ticket #%1$d (%2$s):', 'dragwyb-click-to-chat' ), esc_html( $ticket_no ), esc_html( $ticket['subject'] ) ) . '</p>';
		$content .= '<div style="background:#EEF2FF;border-left:4px solid #4F46E5;padding:12px;margin:15px 0;">' . nl2br( esc_html( $reply_text ) ) . '</div>';
		$content .= '<p>' . esc_html__( 'You can reply to this message directly in our support portal or website chatbot widget.', 'dragwyb-click-to-chat' ) . '</p>';

		return self::send_email( $ticket['customer_email'], $subject, $content, $ticket['id'], 'customer', $ticket['customer_wp_user_id'], 'agent_reply' );
	}

	/**
	 * Send notification when a ticket is resolved.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function notify_ticket_resolved( $ticket_id ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket || empty( $ticket['customer_email'] ) ) {
			return false;
		}

		if ( ! self::should_notify( 'customer', 'ticket_resolved', $ticket ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$ticket_no = ! empty( $ticket['id'] ) ? $ticket['id'] : 0;
		$subject   = sprintf( '[%s] Your Support Ticket #%d Has Been Resolved', $site_name, $ticket_no );

		$content = '<h2>' . esc_html__( 'Your support request is marked as Resolved', 'dragwyb-click-to-chat' ) . '</h2>';
		/* translators: %s: Customer name */
		$content .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'dragwyb-click-to-chat' ), esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : 'there' ) ) . '</p>';
		/* translators: 1: Ticket number, 2: Ticket subject */
		$content .= '<p>' . sprintf( esc_html__( 'Your ticket #%1$d (%2$s) has been marked as resolved by our team.', 'dragwyb-click-to-chat' ), esc_html( $ticket_no ), esc_html( $ticket['subject'] ) ) . '</p>';
		$content .= '<p>' . esc_html__( 'If you still need help or have further questions, simply send another reply and the ticket will automatically reopen.', 'dragwyb-click-to-chat' ) . '</p>';

		return self::send_email( $ticket['customer_email'], $subject, $content, $ticket['id'], 'customer', $ticket['customer_wp_user_id'], 'ticket_resolved' );
	}

	/**
	 * Send confirmation notification when a support ticket is created in guest mode.
	 *
	 * Scheduled asynchronously via WP-Cron with a transient callback fallback to prevent blocking chat/API response.
	 *
	 * @param int  $ticket_id Ticket ID.
	 * @param bool $immediate Whether to send immediately without scheduling.
	 * @return bool
	 */
	public static function notify_guest_ticket_created( $ticket_id, $immediate = false ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket || empty( $ticket['customer_email'] ) ) {
			return false;
		}

		// Only applicable for guest visitors (no authenticated WP user)
		if ( ! empty( $ticket['customer_wp_user_id'] ) ) {
			return false;
		}

		// Check global Customer Email Notifications policy
		$support_settings = get_option( 'dctc_support_settings', array() );
		$notifs           = ! empty( $support_settings['notifications'] ) ? $support_settings['notifications'] : array();
		if ( isset( $notifs['customer_email_enabled'] ) && ! $notifs['customer_email_enabled'] ) {
			return false;
		}

		// Check portal specific guest confirmation setting
		$portal_settings = class_exists( 'DCTC_Support_Portal' ) ? DCTC_Support_Portal::get_settings() : array();
		if ( isset( $portal_settings['guest_ticket_received_email_enabled'] ) && ! $portal_settings['guest_ticket_received_email_enabled'] ) {
			return false;
		}

		if ( $immediate ) {
			return self::process_guest_ticket_created( $ticket_id );
		}

		return self::schedule_async_notification(
			'guest_ticket_created',
			array(
				'ticket_id' => (int) $ticket_id,
			)
		);
	}

	/**
	 * Process and dispatch guest ticket created confirmation email.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return bool
	 */
	public static function process_guest_ticket_created( $ticket_id ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket || empty( $ticket['customer_email'] ) ) {
			return false;
		}

		// Only applicable for guest visitors (no authenticated WP user)
		if ( ! empty( $ticket['customer_wp_user_id'] ) ) {
			return false;
		}

		// Check global Customer Email Notifications policy
		$support_settings = get_option( 'dctc_support_settings', array() );
		$notifs           = ! empty( $support_settings['notifications'] ) ? $support_settings['notifications'] : array();
		if ( isset( $notifs['customer_email_enabled'] ) && ! $notifs['customer_email_enabled'] ) {
			return false;
		}

		// Check portal specific guest confirmation setting
		$portal_settings = class_exists( 'DCTC_Support_Portal' ) ? DCTC_Support_Portal::get_settings() : array();
		if ( isset( $portal_settings['guest_ticket_received_email_enabled'] ) && ! $portal_settings['guest_ticket_received_email_enabled'] ) {
			return false;
		}

		$site_name   = get_bloginfo( 'name' );
		$ticket_no   = ! empty( $ticket['id'] ) ? (int) $ticket['id'] : 0;
		$cust_name   = ! empty( $ticket['customer_name'] ) ? $ticket['customer_name'] : 'there';
		$cust_email  = $ticket['customer_email'];
		$ticket_subj = ! empty( $ticket['subject'] ) ? $ticket['subject'] : 'Support Request';

		$portal_page_id = get_option( 'dctc_support_portal_page_id', 0 );
		$portal_url     = $portal_page_id ? get_permalink( $portal_page_id ) : home_url( '/support-portal/' );

		$raw_subject = ! empty( $portal_settings['guest_ticket_received_email_subject'] )
			? $portal_settings['guest_ticket_received_email_subject']
			: sprintf( '[%s] We have received your support request #%d', $site_name, $ticket_no );

		$raw_message = ! empty( $portal_settings['guest_ticket_received_email_message'] )
			? $portal_settings['guest_ticket_received_email_message']
			: __( 'We have received your ticket in guest mode. You can manage this ticket from our support portal with your current email account when guest mode expires or is closed.', 'dragwyb-click-to-chat' );

		// Interpolate tags
		$replacements = array(
			'{site_name}'      => $site_name,
			'{ticket_id}'      => $ticket_no,
			'{ticket_subject}' => $ticket_subj,
			'{customer_name}'  => $cust_name,
			'{customer_email}' => $cust_email,
			'{portal_url}'     => $portal_url,
		);

		$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $raw_subject );
		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $raw_message );

		if ( strpos( $subject, $site_name ) === false && strpos( $raw_subject, '{site_name}' ) === false ) {
			$subject = sprintf( '[%s] %s', $site_name, $subject );
		}

		$content = '<h2>' . esc_html__( 'We have received your support request', 'dragwyb-click-to-chat' ) . '</h2>';
		/* translators: %s: Customer name */
		$content .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'dragwyb-click-to-chat' ), esc_html( $cust_name ) ) . '</p>';
		/* translators: 1: Ticket number, 2: Ticket subject */
		$content .= '<p>' . sprintf( esc_html__( 'Thank you for reaching out to us. Your ticket #%1$d (%2$s) has been successfully created in guest mode.', 'dragwyb-click-to-chat' ), esc_html( $ticket_no ), esc_html( $ticket_subj ) ) . '</p>';

		// Configured guidance card
		$content .= '<div style="background:#EEF2FF;border-left:4px solid #4F46E5;border-radius:4px;padding:14px 16px;margin:18px 0;color:#1E1B4B;font-size:13.5px;line-height:1.6;">';
		$content .= nl2br( esc_html( $message ) );
		$content .= '</div>';

		// Helpful details box
		$content .= '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:14px 16px;margin:18px 0;font-size:13px;color:#334155;">';
		$content .= '<p style="margin:0 0 6px 0;font-weight:700;color:#0F172A;">' . esc_html__( 'Ticket Details:', 'dragwyb-click-to-chat' ) . '</p>';
		$content .= '<ul style="margin:0;padding-left:18px;line-height:1.6;">';
		$content .= '<li><strong>' . esc_html__( 'Registered Email:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $cust_email ) . '</li>';
		$content .= '<li><strong>' . esc_html__( 'Subject:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket_subj ) . '</li>';
		$content .= '<li>' . esc_html__( 'Whenever your guest browser session expires or closes, you can access and track all tickets associated with this email directly from our support portal.', 'dragwyb-click-to-chat' ) . '</li>';
		$content .= '</ul>';
		$content .= '</div>';

		if ( ! empty( $portal_url ) ) {
			$content .= '<p style="margin-top:24px;text-align:center;"><a href="' . esc_url( $portal_url ) . '" style="background:#4F46E5;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;">' . esc_html__( 'View in Support Portal', 'dragwyb-click-to-chat' ) . '</a></p>';
		}

		return self::send_email( $cust_email, $subject, $content, $ticket['id'], 'customer', 0, 'guest_ticket_created' );
	}

	/**
	 * Send email notification to admin when a new lead is captured by AI Chatbot.
	 *
	 * Scheduled asynchronously via WP-Cron with a transient callback fallback to prevent blocking chat/API response.
	 *
	 * @param array $lead      Lead data.
	 * @param bool  $immediate Whether to send immediately without scheduling.
	 * @return bool
	 */
	public static function maybe_send_lead_email( array $lead, $immediate = false ) {
		$settings = class_exists( 'DCTC_AI_Settings_Handler' ) ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : array();
		$bot      = isset( $settings['chatbot'] ) ? $settings['chatbot'] : array();

		$send_email = ! isset( $bot['enable_lead_email_alerts'] ) || (bool) $bot['enable_lead_email_alerts'];
		if ( ! $send_email ) {
			return false;
		}

		$to = ! empty( $bot['lead_notification_email'] ) ? sanitize_email( $bot['lead_notification_email'] ) : get_option( 'admin_email' );
		$to = self::get_admin_notification_email( $to );

		if ( empty( $to ) || ! is_email( $to ) ) {
			return false;
		}

		if ( $immediate ) {
			return self::process_lead_email( $lead );
		}

		return self::schedule_async_notification(
			'lead_email',
			array(
				'lead' => $lead,
			)
		);
	}

	/**
	 * Alias for maybe_send_lead_email.
	 *
	 * @param array $lead      Lead data.
	 * @param bool  $immediate Whether to send immediately without scheduling.
	 * @return bool
	 */
	public static function notify_lead_captured( array $lead, $immediate = false ) {
		return self::maybe_send_lead_email( $lead, $immediate );
	}

	/**
	 * Process and dispatch lead email notification.
	 *
	 * @param array $lead Lead data.
	 * @return bool
	 */
	public static function process_lead_email( array $lead ) {
		$settings = class_exists( 'DCTC_AI_Settings_Handler' ) ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : array();
		$bot      = isset( $settings['chatbot'] ) ? $settings['chatbot'] : array();

		$send_email = ! isset( $bot['enable_lead_email_alerts'] ) || (bool) $bot['enable_lead_email_alerts'];
		if ( ! $send_email ) {
			return false;
		}

		$to = ! empty( $bot['lead_notification_email'] ) ? sanitize_email( $bot['lead_notification_email'] ) : get_option( 'admin_email' );
		$to = self::get_admin_notification_email( $to );

		if ( empty( $to ) || ! is_email( $to ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );
		$lead_name = ! empty( $lead['name'] ) ? $lead['name'] : ( ! empty( $lead['email'] ) ? $lead['email'] : __( 'Visitor', 'dragwyb-click-to-chat' ) );
		$subject   = sprintf(
			/* translators: 1: Site name, 2: Lead name/email */
			__( '[%1$s] New AI Chat Lead: %2$s', 'dragwyb-click-to-chat' ),
			$site_name,
			$lead_name
		);

		$ticket_id = ! empty( $lead['ticket_id'] ) ? (int) $lead['ticket_id'] : ( ! empty( $lead['id'] ) ? (int) $lead['id'] : 0 );

		$content  = '<h2>' . esc_html__( 'New Lead Captured by AI Chatbot', 'dragwyb-click-to-chat' ) . '</h2>';
		$content .= '<p>' . esc_html__( 'A new prospective lead has been captured by your AI Chatbot:', 'dragwyb-click-to-chat' ) . '</p>';

		$content .= '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:16px;margin:18px 0;font-size:13.5px;color:#334155;">';
		$content .= '<table style="width:100%;border-collapse:collapse;line-height:1.8;">';
		$content .= '<tr><td style="font-weight:700;width:35%;color:#0F172A;padding:4px 0;">' . esc_html__( 'Name:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . esc_html( ! empty( $lead['name'] ) ? $lead['name'] : '-' ) . '</td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Email:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . esc_html( ! empty( $lead['email'] ) ? $lead['email'] : '-' ) . '</td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Phone:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . esc_html( ! empty( $lead['phone'] ) ? $lead['phone'] : '-' ) . '</td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Company:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . esc_html( ! empty( $lead['company'] ) ? $lead['company'] : '-' ) . '</td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Lead Score:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;"><strong>' . intval( $lead['score'] ?? 0 ) . ' / 100</strong></td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Requirement:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . nl2br( esc_html( ! empty( $lead['requirement'] ) ? $lead['requirement'] : '-' ) ) . '</td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Source URL:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . esc_html( ! empty( $lead['source_url'] ) ? $lead['source_url'] : '-' ) . '</td></tr>';
		$content .= '<tr><td style="font-weight:700;color:#0F172A;padding:4px 0;">' . esc_html__( 'Captured At:', 'dragwyb-click-to-chat' ) . '</td><td style="padding:4px 0;">' . esc_html( current_time( 'mysql' ) ) . '</td></tr>';
		$content .= '</table>';
		$content .= '</div>';

		$admin_url = admin_url( 'admin.php?page=dragwyb-click-to-chat' );
		$content  .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:#4F46E5;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;">' . esc_html__( 'View Leads in Dashboard', 'dragwyb-click-to-chat' ) . '</a></p>';

		return self::send_email( $to, $subject, $content, $ticket_id, 'admin', 0, 'ai_lead_captured' );
	}

	/**
	 * Centralized Decision Engine for notifications.
	 *
	 * @param string $recipient_type 'agent', 'admin', or 'customer'.
	 * @param string $event_type     Event slug.
	 * @param array  $ticket         Ticket array.
	 * @param array  $context        Additional metadata.
	 * @return bool
	 */
	public static function should_notify( $recipient_type, $event_type, $ticket, $context = array() ) {
		$settings = get_option( 'dctc_support_settings', array() );
		$notifs   = ! empty( $settings['notifications'] ) ? $settings['notifications'] : array();

		// Check global notification enabled flags
		if ( isset( $notifs[ $event_type ] ) && ! $notifs[ $event_type ] ) {
			return false;
		}

		if ( 'customer' === $recipient_type ) {
			if ( isset( $notifs['customer_email_enabled'] ) && ! $notifs['customer_email_enabled'] ) {
				return false;
			}

			// Active Presence Suppression: Don't send redundant email if customer is actively in chat
			if ( ! empty( $notifs['suppress_active_session'] ) && ! empty( $ticket['customer_last_seen_at'] ) ) {
				$last_seen = strtotime( $ticket['customer_last_seen_at'] );
				if ( ( time() - $last_seen ) < 60 ) {
					self::log_suppressed( $ticket['id'], $event_type, 'customer', $ticket['customer_wp_user_id'], $ticket['customer_email'], 'customer_active_in_session' );
					return false;
				}
			}
		} elseif ( 'agent' === $recipient_type ) {
			$agent = ! empty( $context['agent'] ) ? $context['agent'] : null;
			if ( $agent && empty( $agent['notification_email_enabled'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the designated administrator notification email.
	 *
	 * @return string
	 */
	public static function get_admin_notification_email( $default_email = '' ) {
		$support_settings = get_option( 'dctc_support_settings', array() );
		if ( ! empty( $support_settings['ticket_notification_email'] ) ) {
			return sanitize_email( $support_settings['ticket_notification_email'] );
		}

		if ( ! empty( $default_email ) && isset( $default_email ) ) {
			return sanitize_email( $default_email );
		}

		return sanitize_email( get_option( 'admin_email' ) );
	}

	/**
	 * Render HTML email template and send via wp_mail.
	 *
	 * @param string $to             Recipient email.
	 * @param string $subject        Subject line.
	 * @param string $body_content   HTML body inner content.
	 * @param int    $ticket_id      Ticket ID.
	 * @param string $recipient_type 'agent', 'admin', or 'customer'.
	 * @param int    $recipient_id   User ID.
	 * @param string $event_type     Event slug.
	 * @return bool
	 */
	private static function send_email( $to, $subject, $body_content, $ticket_id, $recipient_type, $recipient_id, $event_type ) {
		$site_name  = get_bloginfo( 'name' );
		$from_email = self::get_admin_notification_email();
		$headers    = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $site_name . ' <' . $from_email . '>',
			'Reply-To: ' . $site_name . ' <' . $from_email . '>',
		);

		$template  = '<!DOCTYPE html><html><head><meta charset="utf-8">';
		$template .= '<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;line-height:1.6;color:#333;background:#F9FAFB;margin:0;padding:20px;}';
		$template .= '.email-card{background:#fff;max-width:580px;margin:0 auto;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden;}';
		$template .= '.email-header{background:#4F46E5;color:#fff;padding:20px 24px;font-size:18px;font-weight:bold;}';
		$template .= '.email-body{padding:24px;font-size:14px;}';
		$template .= '.email-footer{background:#F9FAFB;border-top:1px solid #E5E7EB;padding:16px 24px;font-size:12px;color:#6B7280;text-align:center;}';
		$template .= '</style></head><body>';
		$template .= '<div class="email-card">';
		$template .= '<div class="email-header">' . esc_html( $site_name ) . ' ' . esc_html__( 'Support', 'dragwyb-click-to-chat' ) . '</div>';
		$template .= '<div class="email-body">' . $body_content . '</div>';
		/* translators: %s: Site name */
		$template .= '<div class="email-footer">' . sprintf( esc_html__( 'This email was sent automatically by %s Support Center.', 'dragwyb-click-to-chat' ), esc_html( $site_name ) ) . '</div>';
		$template .= '</div></body></html>';

		$sent = wp_mail( $to, $subject, $template, $headers );

		// Log into notification log table
		global $wpdb;
		$table_log = $wpdb->prefix . 'dctc_support_notification_log';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_log,
			array(
				'event_type'      => sanitize_key( $event_type ),
				'recipient_type'  => sanitize_key( $recipient_type ),
				'recipient_id'    => absint( $recipient_id ),
				'email'           => sanitize_email( $to ),
				'ticket_id'       => absint( $ticket_id ),
				'status'          => $sent ? 'sent' : 'failed',
				'suppress_reason' => '',
				'error_message'   => $sent ? '' : 'wp_mail returned false',
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		return $sent;
	}

	/**
	 * Log suppressed notification in database.
	 *
	 * @param int    $ticket_id       Ticket ID.
	 * @param string $event_type      Event slug.
	 * @param string $recipient_type  Recipient type.
	 * @param int    $recipient_id    Recipient ID.
	 * @param string $email           Email address.
	 * @param string $suppress_reason Reason why sending was skipped.
	 * @return void
	 */
	private static function log_suppressed( $ticket_id, $event_type, $recipient_type, $recipient_id, $email, $suppress_reason ) {
		global $wpdb;
		$table_log = $wpdb->prefix . 'dctc_support_notification_log';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_log,
			array(
				'event_type'      => sanitize_key( $event_type ),
				'recipient_type'  => sanitize_key( $recipient_type ),
				'recipient_id'    => absint( $recipient_id ),
				'email'           => sanitize_email( $email ),
				'ticket_id'       => absint( $ticket_id ),
				'status'          => 'suppressed',
				'suppress_reason' => sanitize_text_field( $suppress_reason ),
				'error_message'   => '',
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}
}

// Hook notification service actions
DCTC_Support_Notification_Service::init();
