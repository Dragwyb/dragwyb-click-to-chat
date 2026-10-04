<?php
/**
 * DCTC Support Category Service
 *
 * Manages support categories, skill requirements, and routing metadata.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Category_Service
 */
class DCTC_Support_Category_Service {

	/**
	 * Ensure sub_taxonomies column exists in the categories table.
	 */
	public static function ensure_schema() {
		static $verified = false;
		if ( $verified ) {
			return;
		}
		$verified = true;

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_categories';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$col = $wpdb->get_results( "SHOW COLUMNS FROM `$table` LIKE 'sub_taxonomies'" );
		if ( empty( $col ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "ALTER TABLE `$table` ADD COLUMN `sub_taxonomies` text DEFAULT NULL AFTER `show_tags`" );
		}
	}

	/**
	 * Get all support categories.
	 *
	 * @param array $args Query arguments (e.g. status, parent_id).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_categories( $args = array() ) {
		self::ensure_schema();

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_categories';

		$status = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';
		$where  = '1=1';

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT * FROM `$table` WHERE $where ORDER BY display_order ASC, name ASC",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['required_skills'] = ! empty( $row['required_skills'] ) ? json_decode( $row['required_skills'], true ) : array();
			$row['required_skills'] = is_array( $row['required_skills'] ) ? $row['required_skills'] : array();
			$row['sub_taxonomies']  = ! empty( $row['sub_taxonomies'] ) ? json_decode( $row['sub_taxonomies'], true ) : array();
			$row['sub_taxonomies']  = is_array( $row['sub_taxonomies'] ) ? $row['sub_taxonomies'] : ( array_filter( array( ! empty( $row['show_product'] ) ? 'product' : '', ! empty( $row['show_tags'] ) ? 'tag' : '' ) ) );
			$row['show_product']    = isset( $row['show_product'] ) ? (int) $row['show_product'] : 1;
			$row['show_tags']       = isset( $row['show_tags'] ) ? (int) $row['show_tags'] : 1;
			$row['color']           = ! empty( $row['color'] ) ? $row['color'] : '#4F46E5';
		}

		return $rows;
	}

	/**
	 * Get category by ID.
	 *
	 * @param int $category_id Category ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_category( $category_id ) {
		self::ensure_schema();

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_categories';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE id = %d", absint( $category_id ) ),
			ARRAY_A
		);

		if ( $row ) {
			$row['required_skills'] = ! empty( $row['required_skills'] ) ? json_decode( $row['required_skills'], true ) : array();
			$row['required_skills'] = is_array( $row['required_skills'] ) ? $row['required_skills'] : array();
			$row['sub_taxonomies']  = ! empty( $row['sub_taxonomies'] ) ? json_decode( $row['sub_taxonomies'], true ) : array();
			$row['sub_taxonomies']  = is_array( $row['sub_taxonomies'] ) ? $row['sub_taxonomies'] : ( array_filter( array( ! empty( $row['show_product'] ) ? 'product' : '', ! empty( $row['show_tags'] ) ? 'tag' : '' ) ) );
			$row['show_product']    = isset( $row['show_product'] ) ? (int) $row['show_product'] : 1;
			$row['show_tags']       = isset( $row['show_tags'] ) ? (int) $row['show_tags'] : 1;
			$row['color']           = ! empty( $row['color'] ) ? $row['color'] : '#4F46E5';
		}

		return $row;
	}

	/**
	 * Create or update a category.
	 *
	 * @param array $data Category data.
	 * @return int|false Category ID or false on failure.
	 */
	public static function save_category( $data ) {
		self::ensure_schema();

		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_categories';

		$id               = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$name             = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug             = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$parent_id        = ! empty( $data['parent_id'] ) ? absint( $data['parent_id'] ) : 0;
		$description      = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$default_priority = ! empty( $data['default_priority'] ) ? sanitize_key( $data['default_priority'] ) : 'normal';
		$default_team_id  = ! empty( $data['default_team_id'] ) ? absint( $data['default_team_id'] ) : 0;
		$color            = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#4F46E5';
		$requires_human   = ! empty( $data['requires_human'] ) ? 1 : 0;
		$ai_allowed       = isset( $data['ai_allowed'] ) ? ( $data['ai_allowed'] ? 1 : 0 ) : 1;
		$auto_assign      = isset( $data['auto_assign'] ) ? ( $data['auto_assign'] ? 1 : 0 ) : 1;
		$status           = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';
		$display_order    = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;

		$sub_tax_list = isset( $data['sub_taxonomies'] ) && is_array( $data['sub_taxonomies'] )
			? array_values( array_map( 'sanitize_title', $data['sub_taxonomies'] ) )
			: array();

		$show_product = in_array( 'product', $sub_tax_list, true ) ? 1 : ( isset( $data['show_product'] ) ? ( $data['show_product'] ? 1 : 0 ) : 0 );
		$show_tags    = in_array( 'tag', $sub_tax_list, true ) ? 1 : ( isset( $data['show_tags'] ) ? ( $data['show_tags'] ? 1 : 0 ) : 0 );

		$skills = isset( $data['required_skills'] ) && is_array( $data['required_skills'] )
			? wp_json_encode( array_map( 'sanitize_key', $data['required_skills'] ) )
			: wp_json_encode( array() );

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$fields = array(
			'parent_id'        => $parent_id,
			'name'             => $name,
			'slug'             => $slug,
			'color'            => $color ? $color : '#4F46E5',
			'description'      => $description,
			'default_priority' => $default_priority,
			'default_team_id'  => $default_team_id,
			'required_skills'  => $skills,
			'requires_human'   => $requires_human,
			'ai_allowed'       => $ai_allowed,
			'auto_assign'      => $auto_assign,
			'show_product'     => $show_product,
			'show_tags'        => $show_tags,
			'sub_taxonomies'   => wp_json_encode( $sub_tax_list ),
			'status'           => $status,
			'display_order'    => $display_order,
		);

		if ( $id ) {
			$fields['updated_at'] = current_time( 'mysql' );
			$updated = $wpdb->update( $table, $fields, array( 'id' => $id ) );
			return false !== $updated ? $id : false;
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			$inserted = $wpdb->insert( $table, $fields );
			return $inserted ? $wpdb->insert_id : false;
		}
	}

	/**
	 * Delete a category by ID.
	 *
	 * @param int $category_id Category ID.
	 * @return bool
	 */
	public static function delete_category( $category_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_categories';

		$deleted = $wpdb->delete(
			$table,
			array( 'id' => absint( $category_id ) ),
			array( '%d' )
		);

		return (bool) $deleted;
	}
}
