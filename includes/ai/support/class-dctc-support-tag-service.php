<?php
/**
 * DCTC Support Tag Service
 *
 * Tag taxonomy layer built on the unified Taxonomy & Term architecture.
 * Manages support tags and ticket-tag polymorphic relationships.
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
		$table = $wpdb->prefix . 'dctc_support_terms';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
		if ( ! $table_exists ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::create_tables();
			} else {
				return array();
			}
		}

		$tags = $wpdb->get_results( "SELECT * FROM `$table` WHERE taxonomy_slug = 'tag' ORDER BY name ASC", ARRAY_A );

		if ( empty( $tags ) ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::seed_default_data();
				$tags = $wpdb->get_results( "SELECT * FROM `$table` WHERE taxonomy_slug = 'tag' ORDER BY name ASC", ARRAY_A );
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return is_array( $tags ) ? $tags : array();
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
		$table = $wpdb->prefix . 'dctc_support_terms';

		$id     = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$name   = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug   = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$color  = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#4F46E5';
		$status = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$fields = array(
			'taxonomy_slug' => 'tag',
			'parent_id'     => 0,
			'name'          => $name,
			'slug'          => $slug,
			'color'         => $color ? $color : '#4F46E5',
			'status'        => $status,
			'updated_at'    => current_time( 'mysql' ),
		);

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update( $table, $fields, array( 'id' => $id ) );
			return false !== $updated ? $id : false;
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

		$tag_id = absint( $tag_id );
		if ( ! $tag_id ) {
			return false;
		}

		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			return DCTC_Support_Taxonomy_Service::delete_term( 'tag', $tag_id );
		}

		return false;
	}

	/**
	 * Get tags assigned to a ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_ticket_tags( $ticket_id ) {
		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			return DCTC_Support_Taxonomy_Service::get_object_terms( $ticket_id, 'tag', 'ticket' );
		}
		return array();
	}

	/**
	 * Assign a list of tag IDs or names to a ticket.
	 *
	 * @param int   $ticket_id Ticket ID.
	 * @param array $tags_input Array of tag IDs or tag name strings.
	 * @return bool
	 */
	public static function set_ticket_tags( $ticket_id, $tags_input = array() ) {
		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			return DCTC_Support_Taxonomy_Service::set_object_terms( $ticket_id, $tags_input, 'tag', 'ticket' );
		}
		return false;
	}
}
