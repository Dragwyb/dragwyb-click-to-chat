<?php
/**
 * DCTC Support Note Service
 *
 * Manages private staff internal notes attached to tickets stored cleanly
 * inside wp_dctc_support_ticket_meta. Strictly isolated from customer APIs.
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
	 * Meta key identifier for internal notes in wp_dctc_support_ticket_meta.
	 */
	const META_KEY = '_dctc_support_note';

	/**
	 * Add an internal note to a ticket.
	 *
	 * @param int      $ticket_id Ticket ID.
	 * @param string   $content   Note content.
	 * @param bool     $is_pinned Whether note is pinned.
	 * @param int|null $user_id   Optional agent user ID.
	 * @return int|false  Inserted note ID (meta_id) or false on failure.
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

		$user       = get_userdata( $user_id );
		$agent_name = $user ? $user->display_name : 'Staff Agent';

		global $wpdb;
		$table_meta = $wpdb->prefix . 'dctc_support_ticket_meta';

		$uuid         = wp_generate_uuid4();
		$note_payload = array(
			'uuid'             => $uuid,
			'ticket_id'        => $ticket_id,
			'agent_wp_user_id' => $user_id,
			'agent_name'       => sanitize_text_field( $agent_name ),
			'author_name'      => sanitize_text_field( $agent_name ),
			'content'          => $content,
			'note'             => $content,
			'is_pinned'        => $is_pinned ? 1 : 0,
			'created_at'       => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$table_meta,
			array(
				'ticket_id'  => $ticket_id,
				'meta_key'   => self::META_KEY,
				'meta_value' => wp_json_encode( $note_payload ),
			),
			array( '%d', '%s', '%s' )
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
		$table_meta = $wpdb->prefix . 'dctc_support_ticket_meta';
		$ticket_id  = absint( $ticket_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM `$table_meta` WHERE ticket_id = %d AND meta_key = %s ORDER BY meta_id ASC",
				$ticket_id,
				self::META_KEY
			),
			ARRAY_A
		);

		$notes = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$note = json_decode( $row['meta_value'], true );
				if ( is_array( $note ) ) {
					$note['id']          = (int) $row['meta_id'];
					$note['author_name'] = ! empty( $note['author_name'] ) ? $note['author_name'] : ( ! empty( $note['agent_name'] ) ? $note['agent_name'] : 'Staff Agent' );
					$note['agent_name']  = ! empty( $note['agent_name'] ) ? $note['agent_name'] : $note['author_name'];
					$note['content']     = isset( $note['content'] ) ? $note['content'] : ( isset( $note['note'] ) ? $note['note'] : '' );
					$note['note']        = isset( $note['note'] ) ? $note['note'] : ( isset( $note['content'] ) ? $note['content'] : '' );
					$note['is_pinned']   = ! empty( $note['is_pinned'] ) ? 1 : 0;
					$notes[]             = $note;
				}
			}
		}

		// Sort notes: pinned first, then chronological
		usort(
			$notes,
			function ( $a, $b ) {
				if ( (int) $a['is_pinned'] !== (int) $b['is_pinned'] ) {
					return (int) $b['is_pinned'] - (int) $a['is_pinned'];
				}
				return (int) $a['id'] - (int) $b['id'];
			}
		);

		return $notes;
	}

	/**
	 * Delete an internal note.
	 *
	 * @param int $note_id Note ID (meta_id).
	 * @return bool
	 */
	public static function delete_note( $note_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'internal_note' ) ) {
			return false;
		}

		global $wpdb;
		$table_meta = $wpdb->prefix . 'dctc_support_ticket_meta';
		$note_id    = absint( $note_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_meta,
			array(
				'meta_id'  => $note_id,
				'meta_key' => self::META_KEY,
			),
			array( '%d', '%s' )
		);

		return (bool) $deleted;
	}
}
