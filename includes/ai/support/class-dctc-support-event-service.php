<?php
/**
 * DCTC Support Event Service
 *
 * Records audit logs, status changes, control handoffs, and activity timelines for tickets.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Event_Service
 */
class DCTC_Support_Event_Service {

	/**
	 * Log a support event.
	 *
	 * @param int        $ticket_id   Support ticket ID.
	 * @param string     $event_type  Event type slug.
	 * @param string     $actor_type  'customer', 'agent', 'ai', 'system'.
	 * @param int        $actor_id    Actor WP user ID or 0.
	 * @param string     $actor_name  Actor display name.
	 * @param mixed|null $old_val     Previous value before change.
	 * @param mixed|null $new_val     New value after change.
	 * @param array|null $metadata    Additional event context.
	 * @return int|false Inserted event ID or false on failure.
	 */
	public static function log_event( $ticket_id, $event_type, $actor_type = 'system', $actor_id = 0, $actor_name = '', $old_val = null, $new_val = null, $metadata = array() ) {
		global $wpdb;
		$table_events = $wpdb->prefix . 'dctc_support_events';

		$ticket_id  = absint( $ticket_id );
		$event_type = sanitize_key( $event_type );
		$actor_type = sanitize_key( $actor_type );
		$actor_id   = absint( $actor_id );

		if ( empty( $actor_name ) ) {
			if ( 'ai' === $actor_type ) {
				$actor_name = 'AI Assistant';
			} elseif ( 'system' === $actor_type ) {
				$actor_name = 'System';
			} elseif ( 'agent' === $actor_type && $actor_id ) {
				$user       = get_userdata( $actor_id );
				$actor_name = $user ? $user->display_name : 'Support Agent';
			} elseif ( 'customer' === $actor_type && $actor_id ) {
				$user       = get_userdata( $actor_id );
				$actor_name = $user ? $user->display_name : 'Customer';
			} else {
				$actor_name = 'Customer';
			}
		}

		$old_val_encoded = ( null !== $old_val && ! is_scalar( $old_val ) ) ? wp_json_encode( $old_val ) : (string) $old_val;
		$new_val_encoded = ( null !== $new_val && ! is_scalar( $new_val ) ) ? wp_json_encode( $new_val ) : (string) $new_val;
		$meta_encoded    = ! empty( $metadata ) ? wp_json_encode( $metadata ) : null;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$table_events,
			array(
				'ticket_id'  => $ticket_id,
				'actor_type' => $actor_type,
				'actor_id'   => $actor_id,
				'actor_name' => sanitize_text_field( $actor_name ),
				'event_type' => $event_type,
				'old_value'  => $old_val_encoded,
				'new_value'  => $new_val_encoded,
				'metadata'   => $meta_encoded,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}
}
