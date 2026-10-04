<?php
/**
 * DCTC Support Taxonomy Service
 *
 * Manages extensible support taxonomies (Categories, Tags, Products, and Custom Taxonomies)
 * and their associated terms.
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

	const TAXONOMY_OPTION = 'dctc_support_custom_taxonomies';

	/**
	 * Get all registered taxonomies (default + user-created).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_taxonomies() {
		$default_taxonomies = array(
			array(
				'slug'        => 'category',
				'name'        => __( 'Categories', 'dragwyb-click-to-chat' ),
				'description' => __( 'Main routing taxonomy with priority and staff assignment rules.', 'dragwyb-click-to-chat' ),
				'is_system'   => true,
				'icon'        => '📁',
				'color'       => '#4F46E5',
			),
			array(
				'slug'        => 'tag',
				'name'        => __( 'Tags', 'dragwyb-click-to-chat' ),
				'description' => __( 'Visual classification tags for fast identification and badge design.', 'dragwyb-click-to-chat' ),
				'is_system'   => true,
				'icon'        => '🏷️',
				'color'       => '#D97706',
			),
			array(
				'slug'        => 'product',
				'name'        => __( 'Products', 'dragwyb-click-to-chat' ),
				'description' => __( 'WooCommerce and custom products for support catalog item routing.', 'dragwyb-click-to-chat' ),
				'is_system'   => true,
				'icon'        => '📦',
				'color'       => '#059669',
			),
		);

		$custom = get_option( self::TAXONOMY_OPTION, array() );
		$custom = is_array( $custom ) ? $custom : array();

		return array_merge( $default_taxonomies, $custom );
	}

	/**
	 * Save / Add / Update a custom taxonomy.
	 *
	 * @param array $data Taxonomy payload (name, slug, description, color, icon).
	 * @return array<string, mixed>|false
	 */
	public static function save_taxonomy( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		$name  = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug  = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$desc  = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$color = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#6366F1';
		$icon  = ! empty( $data['icon'] ) ? sanitize_text_field( $data['icon'] ) : '📑';

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		// Prevent overriding core system slugs
		if ( in_array( $slug, array( 'category', 'tag', 'product' ), true ) ) {
			return false;
		}

		$custom = get_option( self::TAXONOMY_OPTION, array() );
		$custom = is_array( $custom ) ? $custom : array();

		$found_index = -1;
		foreach ( $custom as $idx => $t ) {
			if ( $t['slug'] === $slug ) {
				$found_index = $idx;
				break;
			}
		}

		$tax_item = array(
			'slug'        => $slug,
			'name'        => $name,
			'description' => $desc,
			'color'       => $color ? $color : '#6366F1',
			'icon'        => $icon,
			'is_system'   => false,
		);

		if ( $found_index >= 0 ) {
			$custom[ $found_index ] = $tax_item;
		} else {
			$custom[] = $tax_item;
		}

		update_option( self::TAXONOMY_OPTION, $custom );

		return $tax_item;
	}

	/**
	 * Delete a custom taxonomy.
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
			return false;
		}

		$custom = get_option( self::TAXONOMY_OPTION, array() );
		$custom = is_array( $custom ) ? $custom : array();

		$filtered = array();
		foreach ( $custom as $t ) {
			if ( $t['slug'] !== $slug ) {
				$filtered[] = $t;
			}
		}

		update_option( self::TAXONOMY_OPTION, $filtered );

		// Also delete all terms for this taxonomy
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_taxonomy_terms';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
		if ( $table_exists ) {
			$wpdb->delete( $table, array( 'taxonomy_slug' => $slug ), array( '%s' ) );
		}

		return true;
	}

	/**
	 * Get terms for a taxonomy.
	 *
	 * @param string $taxonomy_slug Taxonomy slug.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_terms( $taxonomy_slug ) {
		$taxonomy_slug = sanitize_title( $taxonomy_slug );

		if ( 'category' === $taxonomy_slug ) {
			return DCTC_Support_Category_Service::get_categories();
		} elseif ( 'tag' === $taxonomy_slug ) {
			return DCTC_Support_Tag_Service::get_tags();
		} elseif ( 'product' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::get_products() : array();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_taxonomy_terms';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
		if ( ! $table_exists ) {
			if ( class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::create_tables();
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE taxonomy_slug = %s ORDER BY name ASC", $taxonomy_slug ),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Save / Add / Update a term inside a custom taxonomy.
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
			return DCTC_Support_Category_Service::save_category( $data );
		} elseif ( 'tag' === $taxonomy_slug ) {
			return DCTC_Support_Tag_Service::save_tag( $data );
		} elseif ( 'product' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::save_product( $data ) : false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_taxonomy_terms';

		$id     = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$name   = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug   = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$color  = ! empty( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '#4F46E5';
		$desc   = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$status = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$fields = array(
			'taxonomy_slug' => $taxonomy_slug,
			'name'          => $name,
			'slug'          => $slug,
			'color'         => $color ? $color : '#4F46E5',
			'description'   => $desc,
			'status'        => $status,
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
			return DCTC_Support_Category_Service::delete_category( $term_id );
		} elseif ( 'tag' === $taxonomy_slug ) {
			return DCTC_Support_Tag_Service::delete_tag( $term_id );
		} elseif ( 'product' === $taxonomy_slug ) {
			return class_exists( 'DCTC_Support_Product_Service' ) ? DCTC_Support_Product_Service::delete_product( $term_id ) : false;
		}

		global $wpdb;
		$table   = $wpdb->prefix . 'dctc_support_taxonomy_terms';
		$deleted = $wpdb->delete(
			$table,
			array( 'id' => $term_id, 'taxonomy_slug' => $taxonomy_slug ),
			array( '%d', '%s' )
		);

		return (bool) $deleted;
	}
}
