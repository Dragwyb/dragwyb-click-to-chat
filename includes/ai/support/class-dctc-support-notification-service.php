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
	 * Hook delayed cron actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'dctc_support_delayed_customer_reply_notif', array( __CLASS__, 'process_delayed_customer_reply' ), 10, 3 );
		add_action( 'dctc_support_delayed_agent_reply_notif', array( __CLASS__, 'process_delayed_agent_reply' ), 10, 3 );
	}

	/**
	 * Send notification when a ticket is assigned to an agent.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $agent_id  Support Agent ID.
	 * @return bool
	 */
	public static function notify_ticket_assigned( $ticket_id, $agent_id ) {
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
		$subject   = sprintf( '[%s] New Support Ticket Assigned: #%d - %s', $site_name, $ticket['ticket_number'], $ticket['subject'] );

		$content  = '<h2>' . esc_html__( 'A new support ticket has been assigned to you', 'dragwyb-click-to-chat' ) . '</h2>';
		$content .= '<p><strong>' . esc_html__( 'Ticket:', 'dragwyb-click-to-chat' ) . '</strong> #' . esc_html( $ticket['ticket_number'] ) . ' - ' . esc_html( $ticket['subject'] ) . '</p>';
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

				$subject  = sprintf( '[%s] New Customer Reply on Ticket #%d: %s', $site_name, $ticket['ticket_number'], $ticket['subject'] );
				$content  = '<h2>' . esc_html__( 'Customer has sent a new reply', 'dragwyb-click-to-chat' ) . '</h2>';
				$content .= '<p><strong>' . esc_html__( 'Ticket:', 'dragwyb-click-to-chat' ) . '</strong> #' . esc_html( $ticket['ticket_number'] ) . ' - ' . esc_html( $ticket['subject'] ) . '</p>';
				$content .= '<p><strong>' . esc_html__( 'Customer:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : $ticket['customer_email'] ) . '</p>';
				$content .= '<div style="background:#F3F4F6;border-left:4px solid #4F46E5;padding:12px;margin:15px 0;font-style:italic;">' . nl2br( esc_html( $reply_text ) ) . '</div>';
				$content .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:#4F46E5;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;">' . esc_html__( 'Open Ticket & Reply', 'dragwyb-click-to-chat' ) . '</a></p>';

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

		$subject  = sprintf( '[%s] [Unassigned] New Customer Message on Ticket #%d: %s', $site_name, $ticket['ticket_number'], $ticket['subject'] );
		$content  = '<h2>' . esc_html__( 'New Customer Message on Unassigned Ticket', 'dragwyb-click-to-chat' ) . '</h2>';
		$content .= '<p><strong>' . esc_html__( 'Ticket:', 'dragwyb-click-to-chat' ) . '</strong> #' . esc_html( $ticket['ticket_number'] ) . ' - ' . esc_html( $ticket['subject'] ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Customer:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : $ticket['customer_email'] ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Status:', 'dragwyb-click-to-chat' ) . '</strong> ' . esc_html( ucfirst( str_replace( '_', ' ', $ticket['status'] ) ) ) . ' (' . esc_html__( 'Unassigned', 'dragwyb-click-to-chat' ) . ')</p>';
		$content .= '<div style="background:#F3F4F6;border-left:4px solid #4F46E5;padding:12px;margin:15px 0;font-style:italic;">' . nl2br( esc_html( $reply_text ) ) . '</div>';
		$content .= '<p style="margin-top:20px;"><a href="' . esc_url( $admin_url ) . '" style="background:#4F46E5;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;font-weight:bold;">' . esc_html__( 'Assign & Reply in Support Center', 'dragwyb-click-to-chat' ) . '</a></p>';

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
		$subject   = sprintf( '[%s] Support Agent Replied to Ticket #%d: %s', $site_name, $ticket['ticket_number'], $ticket['subject'] );

		$content  = '<h2>' . esc_html__( 'We have an update on your support request', 'dragwyb-click-to-chat' ) . '</h2>';
		$content .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'dragwyb-click-to-chat' ), esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : 'there' ) ) . '</p>';
		$content .= '<p>' . sprintf( esc_html__( 'Our support agent has responded to your ticket #%1$d (%2$s):', 'dragwyb-click-to-chat' ), esc_html( $ticket['ticket_number'] ), esc_html( $ticket['subject'] ) ) . '</p>';
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
		$subject   = sprintf( '[%s] Your Support Ticket #%d Has Been Resolved', $site_name, $ticket['ticket_number'] );

		$content  = '<h2>' . esc_html__( 'Your support request is marked as Resolved', 'dragwyb-click-to-chat' ) . '</h2>';
		$content .= '<p>' . sprintf( esc_html__( 'Hi %s,', 'dragwyb-click-to-chat' ), esc_html( $ticket['customer_name'] ? $ticket['customer_name'] : 'there' ) ) . '</p>';
		$content .= '<p>' . sprintf( esc_html__( 'Your ticket #%1$d (%2$s) has been marked as resolved by our team.', 'dragwyb-click-to-chat' ), esc_html( $ticket['ticket_number'] ), esc_html( $ticket['subject'] ) ) . '</p>';
		$content .= '<p>' . esc_html__( 'If you still need help or have further questions, simply send another reply and the ticket will automatically reopen.', 'dragwyb-click-to-chat' ) . '</p>';

		return self::send_email( $ticket['customer_email'], $subject, $content, $ticket['id'], 'customer', $ticket['customer_wp_user_id'], 'ticket_resolved' );
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
	public static function get_admin_notification_email() {
		$chatbot_settings = get_option( 'dctc_ai_chatbot', array() );
		if ( ! empty( $chatbot_settings['ticket_notification_email'] ) ) {
			return sanitize_email( $chatbot_settings['ticket_notification_email'] );
		}

		$support_settings = get_option( 'dctc_support_settings', array() );
		if ( ! empty( $support_settings['ticket_notification_email'] ) ) {
			return sanitize_email( $support_settings['ticket_notification_email'] );
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
		$site_name = get_bloginfo( 'name' );
		$headers   = array( 'Content-Type: text/html; charset=UTF-8' );

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
		$template .= '<div class="email-footer">' . sprintf( esc_html__( 'This email was sent automatically by %s Support Center.', 'dragwyb-click-to-chat' ), esc_html( $site_name ) ) . '</div>';
		$template .= '</div></body></html>';

		$sent = wp_mail( $to, $subject, $template, $headers );

		// Log into notification log table
		global $wpdb;
		$table_log = $wpdb->prefix . 'dctc_support_notification_log';
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
