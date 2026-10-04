<?php
/**
 * DCTC Support Tag Service
 *
 * Manages support tags and ticket-tag pivot relationships.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Tag_Service
 */
class DCTC_Support_Tag_Service {

	/**
	 * Get all tags.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_tags() {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_tags';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tags = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY name ASC", ARRAY_A );

		return is_array( $tags) ? $tags : array();
	}

	/**
	 * Save a tag.
	 *
	 * @param array $data Tag data.
	 * @return int|false
	 */
	public static function save_tag( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_tags';

		$id     = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$name   = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug   = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$color  = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#4F46E5';
		$status = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$fields = array(
			'name'   => $name,
			'slug'   => $slug,
			'color'  => $color ? $color : '#4F46E5',
			'status' => $status,
		);

		if ( $id ) {
			$updated = $wpdb->update( $table, $fields, array( 'id' => $id ) );
			return false !== $updated ? $id : false;
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			$inserted = $wpdb->insert( $table, $fields );
			return $inserted ? $wpdb->insert_id : false;
		}
	}

	/**
	 * Delete a tag.
	 *
	 * @param int $tag_id Tag ID.
	 * @return bool
	 */
	public static function delete_tag( $tag_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		global $wpdb;
		$table_tags = $wpdb->prefix . 'dctc_support_tags';
		$table_pivot = $wpdb->prefix . 'dctc_support_ticket_tags';
		$tag_id      = absint( $tag_id );

		$wpdb->delete( $table_pivot, array( 'tag_id' => $tag_id ), array( '%d' ) );
		$deleted = $wpdb->delete( $table_tags, array( 'id' => $tag_id ), array( '%d' ) );

		return (bool) $deleted;
	}

	/**
	 * Get tags assigned to a ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_ticket_tags( $ticket_id ) {
		global $wpdb;
		$table_tags  = $wpdb->prefix . 'dctc_support_tags';
		$table_pivot = $wpdb->prefix . 'dctc_support_ticket_tags';
		$ticket_id   = absint( $ticket_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tags = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM `$table_tags` t INNER JOIN `$table_pivot` p ON t.id = p.tag_id WHERE p.ticket_id = %d ORDER BY t.name ASC",
				$ticket_id
			),
			ARRAY_A
		);

		return is_array( $tags ) ? $tags : array();
	}

	/**
	 * Assign a list of tag IDs or names to a ticket.
	 *
	 * @param int   $ticket_id Ticket ID.
	 * @param array $tags_input Array of tag IDs or tag name strings.
	 * @return bool
	 */
	public static function set_ticket_tags( $ticket_id, $tags_input = array() ) {
		global $wpdb;
		$table_tags  = $wpdb->prefix . 'dctc_support_tags';
		$table_pivot = $wpdb->prefix . 'dctc_support_ticket_tags';
		$ticket_id   = absint( $ticket_id );
		$tags_input  = is_array( $tags_input ) ? $tags_input : array();

		// Delete existing associations
		$wpdb->delete( $table_pivot, array( 'ticket_id' => $ticket_id ), array( '%d' ) );

		if ( empty( $tags_input ) ) {
			return true;
		}

		$tag_ids = array();
		foreach ( $tags_input as $item ) {
			if ( is_numeric( $item ) && (int) $item > 0 ) {
				$tag_ids[] = absint( $item );
			} elseif ( is_string( $item ) && '' !== trim( $item ) ) {
				$tag_name = sanitize_text_field( trim( $item ) );
				$tag_slug = sanitize_title( $tag_name );
				if ( empty( $tag_slug ) ) {
					continue;
				}

				// Find or create
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$existing_id = $wpdb->get_var(
					$wpdb->prepare( "SELECT id FROM `$table_tags` WHERE slug = %s OR name = %s", $tag_slug, $tag_name )
				);

				if ( $existing_id ) {
					$tag_ids[] = (int) $existing_id;
				} else {
					$wpdb->insert(
						$table_tags,
						array(
							'name'       => $tag_name,
							'slug'       => $tag_slug,
							'color'      => '#4F46E5',
							'status'     => 'active',
							'created_at' => current_time( 'mysql' ),
						)
					);
					if ( $wpdb->insert_id ) {
						$tag_ids[] = (int) $wpdb->insert_id;
					}
				}
			}
		}

		$tag_ids = array_unique( array_filter( $tag_ids ) );
		foreach ( $tag_ids as $tag_id ) {
			$wpdb->insert(
				$table_pivot,
				array(
					'ticket_id'  => $ticket_id,
					'tag_id'     => $tag_id,
					'created_at' => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%s' )
			);
		}

		return true;
	}
}

