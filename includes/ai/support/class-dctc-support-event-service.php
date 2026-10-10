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

	/**
	 * Get activity events for a ticket.
	 *
	 * @param int    $ticket_id Support ticket ID.
	 * @param string $order     'ASC' or 'DESC'.
	 * @param int    $limit     Limit of events.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_events( $ticket_id, $order = 'ASC', $limit = 100 ) {
		global $wpdb;
		$table_events = $wpdb->prefix . 'dctc_support_events';

		$ticket_id   = absint( $ticket_id );
		$order_clean = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';
		$limit_val   = max( 1, min( 500, absint( $limit ) ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `$table_events` WHERE ticket_id = %d ORDER BY created_at {$order_clean} LIMIT %d",
				$ticket_id,
				$limit_val
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			if ( ! empty( $row['metadata'] ) ) {
				$decoded         = json_decode( $row['metadata'], true );
				$row['metadata'] = is_array( $decoded ) ? $decoded : $row['metadata'];
			}
		}

		return $rows;
	}
}
