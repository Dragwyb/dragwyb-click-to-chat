<?php
/**
 * DCTC Support Category Service
 *
 * Category taxonomy layer built on the unified Taxonomy & Term architecture.
 * Manages support categories, routing rules, team/skill requirements, and sub-taxonomies.
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
	 * Get all support categories.
	 *
	 * @param array $args Query arguments (e.g. status, parent_id).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_categories( $args = array() ) {
		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$table_meta  = $wpdb->prefix . 'dctc_support_term_meta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_terms'" );
		if ( ! $table_exists ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::create_tables();
			} else {
				return array();
			}
		}

		$status = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';
		$where  = "taxonomy_slug = 'category'";

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}

		if ( isset( $args['parent_id'] ) ) {
			$where .= $wpdb->prepare( ' AND parent_id = %d', absint( $args['parent_id'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			"SELECT * FROM `$table_terms` WHERE $where ORDER BY display_order ASC, name ASC",
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::seed_default_data();
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$rows = $wpdb->get_results(
					"SELECT * FROM `$table_terms` WHERE $where ORDER BY display_order ASC, name ASC",
					ARRAY_A
				);
			}
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$term_ids = wp_list_pluck( $rows, 'id' );
		$meta_map = array();

		if ( ! empty( $term_ids ) ) {
			$ids_in = implode( ',', array_map( 'absint', $term_ids ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$meta_rows = $wpdb->get_results(
				"SELECT term_id, meta_key, meta_value FROM `$table_meta` WHERE term_id IN ($ids_in)",
				ARRAY_A
			);
			if ( is_array( $meta_rows ) ) {
				foreach ( $meta_rows as $mr ) {
					$meta_map[ (int) $mr['term_id'] ][ $mr['meta_key'] ] = $mr['meta_value'];
				}
			}
		}

		foreach ( $rows as &$row ) {
			$tid  = (int) $row['id'];
			$meta = isset( $meta_map[ $tid ] ) ? $meta_map[ $tid ] : array();

			$row['default_priority'] = ! empty( $meta['default_priority'] ) ? $meta['default_priority'] : 'normal';
			$row['default_team_id']  = ! empty( $meta['default_team_id'] ) ? absint( $meta['default_team_id'] ) : 0;

			$req_skills             = ! empty( $meta['required_skills'] ) ? json_decode( $meta['required_skills'], true ) : array();
			$row['required_skills'] = is_array( $req_skills ) ? $req_skills : array();

			$sub_tax               = ! empty( $meta['sub_taxonomies'] ) ? json_decode( $meta['sub_taxonomies'], true ) : array();
			$row['sub_taxonomies'] = is_array( $sub_tax ) ? $sub_tax : ( array_filter( array( ! empty( $meta['show_product'] ) ? 'product' : '', ! empty( $meta['show_tags'] ) ? 'tag' : '' ) ) );

			$row['requires_human'] = isset( $meta['requires_human'] ) ? (int) $meta['requires_human'] : 0;
			$row['ai_allowed']     = isset( $meta['ai_allowed'] ) ? (int) $meta['ai_allowed'] : 1;
			$row['auto_assign']    = isset( $meta['auto_assign'] ) ? (int) $meta['auto_assign'] : 1;
			$row['show_product']   = isset( $meta['show_product'] ) ? (int) $meta['show_product'] : 1;
			$row['show_tags']      = isset( $meta['show_tags'] ) ? (int) $meta['show_tags'] : 1;
			$row['color']          = ! empty( $row['color'] ) ? $row['color'] : '#4F46E5';
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
		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$category_id = absint( $category_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table_terms` WHERE id = %d AND taxonomy_slug = 'category'", $category_id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$meta = class_exists( 'DCTC_Support_Taxonomy_Service' )
			? DCTC_Support_Taxonomy_Service::get_all_term_meta( $category_id )
			: array();

		$row['default_priority'] = ! empty( $meta['default_priority'] ) ? $meta['default_priority'] : 'normal';
		$row['default_team_id']  = ! empty( $meta['default_team_id'] ) ? absint( $meta['default_team_id'] ) : 0;

		$req_skills             = ! empty( $meta['required_skills'] ) ? json_decode( $meta['required_skills'], true ) : array();
		$row['required_skills'] = is_array( $req_skills ) ? $req_skills : array();

		$sub_tax               = ! empty( $meta['sub_taxonomies'] ) ? json_decode( $meta['sub_taxonomies'], true ) : array();
		$row['sub_taxonomies'] = is_array( $sub_tax ) ? $sub_tax : ( array_filter( array( ! empty( $meta['show_product'] ) ? 'product' : '', ! empty( $meta['show_tags'] ) ? 'tag' : '' ) ) );

		$row['requires_human'] = isset( $meta['requires_human'] ) ? (int) $meta['requires_human'] : 0;
		$row['ai_allowed']     = isset( $meta['ai_allowed'] ) ? (int) $meta['ai_allowed'] : 1;
		$row['auto_assign']    = isset( $meta['auto_assign'] ) ? (int) $meta['auto_assign'] : 1;
		$row['show_product']   = isset( $meta['show_product'] ) ? (int) $meta['show_product'] : 1;
		$row['show_tags']      = isset( $meta['show_tags'] ) ? (int) $meta['show_tags'] : 1;
		$row['color']          = ! empty( $row['color'] ) ? $row['color'] : '#4F46E5';

		return $row;
	}

	/**
	 * Create or update a category term and its metadata.
	 *
	 * @param array $data Category data.
	 * @return int|false Category ID or false on failure.
	 */
	public static function save_category( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) ) {
			return false;
		}

		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';

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
			? wp_json_encode( array_values( array_map( 'sanitize_key', $data['required_skills'] ) ) )
			: ( is_string( $data['required_skills'] ) && ! empty( $data['required_skills'] ) ? $data['required_skills'] : '[]' );

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$term_fields = array(
			'taxonomy_slug' => 'category',
			'parent_id'     => $parent_id,
			'name'          => $name,
			'slug'          => $slug,
			'color'         => $color ? $color : '#4F46E5',
			'description'   => $description,
			'status'        => $status,
			'display_order' => $display_order,
			'updated_at'    => current_time( 'mysql' ),
		);

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_terms, $term_fields, array( 'id' => $id ) );
			$term_id = $id;
		} else {
			$term_fields['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$inserted = $wpdb->insert( $table_terms, $term_fields );
			$term_id  = $inserted ? (int) $wpdb->insert_id : 0;
		}

		if ( ! $term_id ) {
			return false;
		}

		// Update Metadata
		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'default_priority', $default_priority );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'default_team_id', $default_team_id );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'required_skills', $skills );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'requires_human', $requires_human );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'ai_allowed', $ai_allowed );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'auto_assign', $auto_assign );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'show_product', $show_product );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'show_tags', $show_tags );
			DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'sub_taxonomies', wp_json_encode( $sub_tax_list ) );
		}

		return $term_id;
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

		$category_id = absint( $category_id );
		if ( ! $category_id ) {
			return false;
		}

		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			return DCTC_Support_Taxonomy_Service::delete_term( 'category', $category_id );
		}

		return false;
	}
}
