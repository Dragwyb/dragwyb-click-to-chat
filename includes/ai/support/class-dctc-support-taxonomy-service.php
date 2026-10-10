<?php
/**
 * DCTC Support Taxonomy Service
 *
 * Unified taxonomy, terms, term metadata, and polymorphic relationships engine
 * for Support Center (Categories, Tags, Products, and Custom Taxonomies).
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Taxonomy_Service
 */
class DCTC_Support_Taxonomy_Service {

	/**
	 * Get all registered taxonomies (default system + custom user-defined).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_taxonomies() {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_taxonomies';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
		if ( ! $table_exists ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::create_tables();
			} else {
				return array();
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY display_order ASC, name ASC", ARRAY_A );

		if ( empty( $rows ) ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) && class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::seed_default_data();
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$rows = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY display_order ASC, name ASC", ARRAY_A );
			}
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$row['is_system']    = ! empty( $row['is_system'] );
			$row['hierarchical'] = ! empty( $row['hierarchical'] );
		}

		return $rows;
	}

	/**
	 * Get single taxonomy by slug.
	 *
	 * @param string $slug Taxonomy slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_taxonomy( $slug ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_taxonomies';
		$slug  = sanitize_title( $slug );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE slug = %s", $slug ),
			ARRAY_A
		);

		if ( $row ) {
			$row['is_system']    = ! empty( $row['is_system'] );
			$row['hierarchical'] = ! empty( $row['hierarchical'] );
		}

		return $row;
	}

	/**
	 * Save / Add / Update a taxonomy definition.
	 *
	 * @param array $data Taxonomy payload.
	 * @return array<string, mixed>|false
	 */
	public static function save_taxonomy( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_taxonomies';

		$name          = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug          = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$desc          = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$color         = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#6366F1';
		$icon_dashicon = ! empty( $data['icon_dashicon'] ) ? sanitize_text_field( $data['icon_dashicon'] ) : 'dashicons-category';
		$image_url     = ! empty( $data['image_url'] ) ? esc_url_raw( $data['image_url'] ) : '';
		$icon_type     = ! empty( $data['icon_type'] ) ? sanitize_key( $data['icon_type'] ) : ( ! empty( $image_url ) ? 'custom' : 'preset' );
		$hierarchical  = ! empty( $data['hierarchical'] ) ? 1 : 0;
		$display_order = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 10;

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		// Prevent overriding core system flags
		$is_system = in_array( $slug, array( 'category', 'tag', 'product' ), true ) ? 1 : 0;

		$fields = array(
			'slug'          => $slug,
			'name'          => $name,
			'description'   => $desc,
			'is_system'     => $is_system,
			'hierarchical'  => $hierarchical,
			'icon_type'     => $icon_type,
			'icon_dashicon' => $icon_dashicon,
			'image_url'     => $image_url,
			'color'         => $color ? $color : '#6366F1',
			'display_order' => $display_order,
			'updated_at'    => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$table` WHERE slug = %s", $slug ) );

		if ( $existing_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, $fields, array( 'id' => (int) $existing_id ) );
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( $table, $fields );
		}

		return self::get_taxonomy( $slug );
	}

	/**
	 * Delete a custom taxonomy and its associated terms, metadata, and relationships.
	 *
	 * @param string $slug Taxonomy slug.
	 * @return bool
	 */
	public static function delete_taxonomy( $slug ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		$slug = sanitize_title( $slug );
		if ( in_array( $slug, array( 'category', 'tag', 'product' ), true ) ) {
			return false; // System taxonomies cannot be deleted
		}

		global $wpdb;
		$table_taxonomies    = $wpdb->prefix . 'dctc_support_taxonomies';
		$table_terms         = $wpdb->prefix . 'dctc_support_terms';
		$table_term_meta     = $wpdb->prefix . 'dctc_support_term_meta';
		$table_relationships = $wpdb->prefix . 'dctc_support_term_relationships';

		// Get all term IDs under this taxonomy to clean up meta & relationships
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$term_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM `$table_terms` WHERE taxonomy_slug = %s", $slug ) );

		if ( ! empty( $term_ids ) ) {
			$ids_placeholder = implode( ',', array_map( 'absint', $term_ids ) );
			$wpdb->query( "DELETE FROM `$table_term_meta` WHERE term_id IN ($ids_placeholder)" );
			$wpdb->query( "DELETE FROM `$table_relationships` WHERE term_id IN ($ids_placeholder) OR (object_id IN ($ids_placeholder) AND object_type = 'term')" );
			$wpdb->delete( $table_terms, array( 'taxonomy_slug' => $slug ), array( '%s' ) );
		}

		// Delete taxonomy relationships
		$wpdb->delete( $table_relationships, array( 'taxonomy_slug' => $slug ), array( '%s' ) );

		// Delete taxonomy record
		$deleted = $wpdb->delete( $table_taxonomies, array( 'slug' => $slug ), array( '%s' ) );

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return (bool) $deleted;
	}

	/**
	 * Get terms for a given taxonomy.
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param array  $args Query arguments.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_terms( $taxonomy_slug, $args = array() ) {
		$taxonomy_slug = sanitize_title( $taxonomy_slug );

		if ( 'category' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Category_Service' ) ? DCTC_Support_Category_Service::get_categories( $args ) : array();
		} elseif ( 'tag' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Tag_Service' ) ? DCTC_Support_Tag_Service::get_tags() : array();
		} elseif ( 'product' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::get_products( $args ) : array();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_terms';

		$where = $wpdb->prepare( 'taxonomy_slug = %s', $taxonomy_slug );
		if ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) {
			$where .= $wpdb->prepare( ' AND status = %s', sanitize_key( $args['status'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( "SELECT * FROM `$table` WHERE $where ORDER BY display_order ASC, name ASC", ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get single term by ID.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_term( $term_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_terms';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE id = %d", absint( $term_id ) ),
			ARRAY_A
		);
	}

	/**
	 * Save / Add / Update a term inside a taxonomy.
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param array  $data Term data.
	 * @return int|false Term ID or false on failure.
	 */
	public static function save_term( $taxonomy_slug, $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		$taxonomy_slug = sanitize_title( $taxonomy_slug );

		if ( 'category' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Category_Service' ) ? DCTC_Support_Category_Service::save_category( $data ) : false;
		} elseif ( 'tag' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Tag_Service' ) ? DCTC_Support_Tag_Service::save_tag( $data ) : false;
		} elseif ( 'product' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::save_product( $data ) : false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_terms';

		$id            = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$parent_id     = ! empty( $data['parent_id'] ) ? absint( $data['parent_id'] ) : 0;
		$name          = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug          = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$color         = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#4F46E5';
		$desc          = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$status        = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';
		$display_order = isset( $data['display_order'] ) ? intval( $data['display_order'] ) : 0;

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$fields = array(
			'taxonomy_slug' => $taxonomy_slug,
			'parent_id'     => $parent_id,
			'name'          => $name,
			'slug'          => $slug,
			'color'         => $color ? $color : '#4F46E5',
			'description'   => $desc,
			'status'        => $status,
			'display_order' => $display_order,
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
	 * Delete a term from a taxonomy.
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param int    $term_id Term ID.
	 * @return bool
	 */
	public static function delete_term( $taxonomy_slug, $term_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		$taxonomy_slug = sanitize_title( $taxonomy_slug );
		$term_id       = absint( $term_id );

		if ( 'category' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Category_Service' ) ? DCTC_Support_Category_Service::delete_category( $term_id ) : false;
		} elseif ( 'tag' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Tag_Service' ) ? DCTC_Support_Tag_Service::delete_tag( $term_id ) : false;
		} elseif ( 'product' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::delete_product( $term_id ) : false;
		}

		global $wpdb;
		$table_terms         = $wpdb->prefix . 'dctc_support_terms';
		$table_term_meta     = $wpdb->prefix . 'dctc_support_term_meta';
		$table_relationships = $wpdb->prefix . 'dctc_support_term_relationships';

		// Clean up meta and relationships
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table_term_meta, array( 'term_id' => $term_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table_relationships, array( 'term_id' => $term_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table_relationships,
			array(
				'object_id'   => $term_id,
				'object_type' => 'term',
			),
			array( '%d', '%s' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_terms,
			array(
				'id'            => $term_id,
				'taxonomy_slug' => $taxonomy_slug,
			),
			array( '%d', '%s' )
		);

		return (bool) $deleted;
	}

	/**
	 * Get term metadata.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single Return single string/array or all entries.
	 * @return mixed
	 */
	public static function get_term_meta( $term_id, $meta_key = '', $single = true ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'dctc_support_term_meta';
		$term_id = absint( $term_id );

		if ( empty( $meta_key ) ) {
			return self::get_all_term_meta( $term_id );
		}

		$meta_key = sanitize_key( $meta_key );
		if ( $single ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$val = $wpdb->get_var(
				$wpdb->prepare( "SELECT meta_value FROM `$table` WHERE term_id = %d AND meta_key = %s LIMIT 1", $term_id, $meta_key )
			);
			return $val;
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			return $wpdb->get_col(
				$wpdb->prepare( "SELECT meta_value FROM `$table` WHERE term_id = %d AND meta_key = %s", $term_id, $meta_key )
			);
		}
	}

	/**
	 * Get all metadata for a term as associative key => value map.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string, mixed>
	 */
	public static function get_all_term_meta( $term_id ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'dctc_support_term_meta';
		$term_id = absint( $term_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT meta_key, meta_value FROM `$table` WHERE term_id = %d", $term_id ),
			ARRAY_A
		);

		$map = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$map[ $row['meta_key'] ] = $row['meta_value'];
			}
		}

		return $map;
	}

	/**
	 * Update or insert term metadata.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value (scalar or array).
	 * @return bool
	 */
	public static function update_term_meta( $term_id, $meta_key, $meta_value ) {
		global $wpdb;
		$table    = $wpdb->prefix . 'dctc_support_term_meta';
		$term_id  = absint( $term_id );
		$meta_key = sanitize_key( $meta_key );

		if ( empty( $term_id ) || empty( $meta_key ) ) {
			return false;
		}

		$val_str = is_array( $meta_value ) || is_object( $meta_value )
			? wp_json_encode( $meta_value )
			: (string) $meta_value;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_id FROM `$table` WHERE term_id = %d AND meta_key = %s", $term_id, $meta_key )
		);

		if ( $existing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array( 'meta_value' => $val_str ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				array( 'meta_id' => (int) $existing ),
				array( '%s' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				array(
					'term_id'    => $term_id,
					'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $val_str, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				),
				array( '%d', '%s', '%s' )
			);
		}

		return true;
	}

	/**
	 * Delete term metadata.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $meta_key Optional meta key.
	 * @return bool
	 */
	public static function delete_term_meta( $term_id, $meta_key = '' ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'dctc_support_term_meta';
		$term_id = absint( $term_id );

		if ( empty( $term_id ) ) {
			return false;
		}

		if ( ! empty( $meta_key ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$table,
				array(
					'term_id'  => $term_id,
					'meta_key' => sanitize_key( $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				),
				array( '%d', '%s' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$table,
				array( 'term_id' => $term_id ),
				array( '%d' )
			);
		}

		return true;
	}

	/**
	 * Get terms associated with an object (ticket, product/term, etc.).
	 *
	 * @param int    $object_id Object ID (e.g. ticket_id).
	 * @param string $taxonomy_slug Optional taxonomy slug filter.
	 * @param string $object_type Object type ('ticket', 'term', etc.).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_object_terms( $object_id, $taxonomy_slug = '', $object_type = 'ticket' ) {
		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$table_rel   = $wpdb->prefix . 'dctc_support_term_relationships';
		$object_id   = absint( $object_id );
		$object_type = sanitize_key( $object_type );

		$where = $wpdb->prepare( 'r.object_id = %d AND r.object_type = %s', $object_id, $object_type );
		if ( ! empty( $taxonomy_slug ) ) {
			$where .= $wpdb->prepare( ' AND r.taxonomy_slug = %s', sanitize_title( $taxonomy_slug ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results(
			"SELECT t.* FROM `$table_terms` t INNER JOIN `$table_rel` r ON t.id = r.term_id WHERE $where ORDER BY t.name ASC",
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Set / Sync terms for an object.
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms_input Array of term IDs or names.
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @param string $object_type Object type ('ticket', 'term', etc.).
	 * @return bool
	 */
	public static function set_object_terms( $object_id, $terms_input = array(), $taxonomy_slug = 'tag', $object_type = 'ticket' ) {
		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$table_rel   = $wpdb->prefix . 'dctc_support_term_relationships';

		$object_id     = absint( $object_id );
		$taxonomy_slug = sanitize_title( $taxonomy_slug );
		$object_type   = sanitize_key( $object_type );
		$terms_input   = is_array( $terms_input ) ? $terms_input : array();

		// Delete existing associations for this object and taxonomy
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table_rel,
			array(
				'object_id'     => $object_id,
				'object_type'   => $object_type,
				'taxonomy_slug' => $taxonomy_slug,
			),
			array( '%d', '%s', '%s' )
		);

		if ( empty( $terms_input ) ) {
			return true;
		}

		$term_ids = array();
		foreach ( $terms_input as $item ) {
			if ( is_numeric( $item ) && (int) $item > 0 ) {
				$term_ids[] = absint( $item );
			} elseif ( is_string( $item ) && '' !== trim( $item ) ) {
				$name = sanitize_text_field( trim( $item ) );
				$slug = sanitize_title( $name );
				if ( empty( $slug ) ) {
					continue;
				}

				// Find or create term
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$existing_id = $wpdb->get_var(
					$wpdb->prepare( "SELECT id FROM `$table_terms` WHERE taxonomy_slug = %s AND (slug = %s OR name = %s)", $taxonomy_slug, $slug, $name )
				);

				if ( $existing_id ) {
					$term_ids[] = (int) $existing_id;
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->insert(
						$table_terms,
						array(
							'taxonomy_slug' => $taxonomy_slug,
							'parent_id'     => 0,
							'name'          => $name,
							'slug'          => $slug,
							'color'         => '#4F46E5',
							'status'        => 'active',
							'created_at'    => current_time( 'mysql' ),
							'updated_at'    => current_time( 'mysql' ),
						)
					);
					if ( $wpdb->insert_id ) {
						$term_ids[] = (int) $wpdb->insert_id;
					}
				}
			}
		}

		$term_ids = array_unique( array_filter( $term_ids ) );
		foreach ( $term_ids as $term_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				$table_rel,
				array(
					'object_id'     => $object_id,
					'object_type'   => $object_type,
					'term_id'       => $term_id,
					'taxonomy_slug' => $taxonomy_slug,
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s', '%s' )
			);
		}

		return true;
	}
}
