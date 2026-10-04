<?php
/**
 * DCTC Support Note Service
 *
 * Manages private staff internal notes attached to tickets.
 * Strictly isolated from customer APIs.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Note_Service
 */
class DCTC_Support_Note_Service {

	/**
	 * Add an internal note to a ticket.
	 *
	 * @param int         $ticket_id Ticket ID.
	 * @param string      $content   Note content.
	 * @param bool        $is_pinned Whether note is pinned.
	 * @param int|null    $user_id   Optional agent user ID.
	 * @return int|false  Inserted note ID or false on failure.
	 */
	public static function add_note( $ticket_id, $content, $is_pinned = false, $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id || ! DCTC_Support_Permission_Service::current_user_can_support( 'internal_note', $user_id ) ) {
			return false;
		}

		$ticket_id = absint( $ticket_id );
		$content   = wp_kses_post( $content );
		if ( empty( $content ) ) {
			return false;
		}

		$user = get_userdata( $user_id );
		$agent_name = $user ? $user->display_name : 'Staff Agent';

		global $wpdb;
		$table_notes = $wpdb->prefix . 'dctc_support_notes';

		$uuid = wp_generate_uuid4();

		$inserted = $wpdb->insert(
			$table_notes,
			array(
				'uuid'              => $uuid,
				'ticket_id'         => $ticket_id,
				'agent_wp_user_id'  => $user_id,
				'agent_name'        => sanitize_text_field( $agent_name ),
				'content'           => $content,
				'is_pinned'         => $is_pinned ? 1 : 0,
				'created_at'        => current_time( 'mysql' ),
				'updated_at'        => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( $inserted ) {
			$note_id = $wpdb->insert_id;
			// Log event
			DCTC_Support_Event_Service::log_event(
				$ticket_id,
				'internal_note_added',
				'agent',
				$user_id,
				$agent_name,
				null,
				null,
				array( 'note_id' => $note_id )
			);
			return $note_id;
		}

		return false;
	}

	/**
	 * Get internal notes for a ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_notes( $ticket_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'internal_note' ) ) {
			return array();
		}

		global $wpdb;
		$table_notes = $wpdb->prefix . 'dctc_support_notes';
		$ticket_id   = absint( $ticket_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$notes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `$table_notes` WHERE ticket_id = %d ORDER BY is_pinned DESC, created_at ASC",
				$ticket_id
			),
			ARRAY_A
		);

		return is_array( $notes ) ? $notes : array();
	}

	/**
	 * Delete an internal note.
	 *
	 * @param int $note_id Note ID.
	 * @return bool
	 */
	public static function delete_note( $note_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'internal_note' ) ) {
			return false;
		}

		global $wpdb;
		$table_notes = $wpdb->prefix . 'dctc_support_notes';
		$note_id     = absint( $note_id );

		$deleted = $wpdb->delete(
			$table_notes,
			array( 'id' => $note_id ),
			array( '%d' )
		);

		return (bool) $deleted;
	}
}
