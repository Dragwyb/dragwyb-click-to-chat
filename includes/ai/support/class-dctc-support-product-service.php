<?php
/**
 * DCTC Support Product Service
 *
 * Product taxonomy layer built on the unified Taxonomy & Term architecture.
 * Manages support products catalog (custom and WooCommerce synced)
 * with category relationships, sku, price, and conditional ticket classification.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Product_Service
 */
class DCTC_Support_Product_Service {

	/**
	 * Get all support products (merged with WooCommerce products if available).
	 *
	 * @param array $args Query arguments (e.g. status, category_id, search).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_products( $args = array() ) {
		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$table_meta  = $wpdb->prefix . 'dctc_support_term_meta';
		$table_rel   = $wpdb->prefix . 'dctc_support_term_relationships';

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
		$where  = "t.taxonomy_slug = 'product'";

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where .= $wpdb->prepare( ' AND t.status = %s', $status );
		}

		if ( ! empty( $args['category_id'] ) ) {
			$cat_id = absint( $args['category_id'] );
			$where .= $wpdb->prepare(
				" AND t.id IN (
					SELECT term_id FROM `$table_meta` WHERE meta_key = 'category_id' AND meta_value = %s
					UNION
					SELECT object_id FROM `$table_rel` WHERE object_type = 'term' AND term_id = %d
				)",
				(string) $cat_id,
				$cat_id
			);
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where .= $wpdb->prepare(
				" AND (t.name LIKE %s OR t.slug LIKE %s OR t.id IN (SELECT term_id FROM `$table_meta` WHERE meta_key = 'sku' AND meta_value LIKE %s))",
				$search,
				$search,
				$search
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows     = $wpdb->get_results( "SELECT t.* FROM `$table_terms` t WHERE $where ORDER BY t.name ASC", ARRAY_A );
		$products = is_array( $rows ) ? $rows : array();

		// If no custom products yet and WooCommerce is active, automatically sync WooCommerce products
		if ( empty( $products ) && empty( $args['search'] ) && empty( $args['category_id'] ) && post_type_exists( 'product' ) ) {
			self::sync_woocommerce_products();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows     = $wpdb->get_results( "SELECT t.* FROM `$table_terms` t WHERE $where ORDER BY t.name ASC", ARRAY_A );
			$products = is_array( $rows ) ? $rows : array();
		}

		if ( empty( $products ) ) {
			return array();
		}

		$term_ids = wp_list_pluck( $products, 'id' );
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

		foreach ( $products as &$prod ) {
			$pid  = (int) $prod['id'];
			$meta = isset( $meta_map[ $pid ] ) ? $meta_map[ $pid ] : array();

			$prod['category_id']   = ! empty( $meta['category_id'] ) ? absint( $meta['category_id'] ) : 0;
			$prod['wc_product_id'] = ! empty( $meta['wc_product_id'] ) ? absint( $meta['wc_product_id'] ) : 0;
			$prod['sku']           = isset( $meta['sku'] ) ? (string) $meta['sku'] : '';
			$prod['price']         = isset( $meta['price'] ) ? floatval( $meta['price'] ) : 0.00;
		}

		return $products;
	}

	/**
	 * Get product by ID.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_product( $product_id ) {
		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$product_id  = absint( $product_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table_terms` WHERE id = %d AND taxonomy_slug = 'product'", $product_id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$meta = class_exists( 'DCTC_Support_Taxonomy_Service' )
			? DCTC_Support_Taxonomy_Service::get_all_term_meta( $product_id )
			: array();

		$row['category_id']   = ! empty( $meta['category_id'] ) ? absint( $meta['category_id'] ) : 0;
		$row['wc_product_id'] = ! empty( $meta['wc_product_id'] ) ? absint( $meta['wc_product_id'] ) : 0;
		$row['sku']           = isset( $meta['sku'] ) ? (string) $meta['sku'] : '';
		$row['price']         = isset( $meta['price'] ) ? floatval( $meta['price'] ) : 0.00;

		return $row;
	}

	/**
	 * Save / Add / Update a product term and metadata.
	 *
	 * @param array $data Product payload.
	 * @return int|false Product ID or false on failure.
	 */
	public static function save_product( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$table_rel   = $wpdb->prefix . 'dctc_support_term_relationships';

		$id            = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$name          = ! empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$slug          = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
		$category_id   = ! empty( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;
		$wc_product_id = ! empty( $data['wc_product_id'] ) ? absint( $data['wc_product_id'] ) : 0;
		$sku           = isset( $data['sku'] ) ? sanitize_text_field( $data['sku'] ) : '';
		$price         = isset( $data['price'] ) ? floatval( $data['price'] ) : 0.00;
		$status        = ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';

		if ( empty( $name ) || empty( $slug ) ) {
			return false;
		}

		$fields = array(
			'taxonomy_slug' => 'product',
			'parent_id'     => 0,
			'name'          => $name,
			'slug'          => $slug,
			'color'         => '#059669',
			'status'        => $status,
			'updated_at'    => current_time( 'mysql' ),
		);

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_terms, $fields, array( 'id' => $id ) );
			$product_id = $id;
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$inserted   = $wpdb->insert( $table_terms, $fields );
			$product_id = $inserted ? (int) $wpdb->insert_id : 0;
		}

		if ( ! $product_id ) {
			return false;
		}

		// Save product metadata
		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			DCTC_Support_Taxonomy_Service::update_term_meta( $product_id, 'category_id', $category_id );
			DCTC_Support_Taxonomy_Service::update_term_meta( $product_id, 'wc_product_id', $wc_product_id );
			DCTC_Support_Taxonomy_Service::update_term_meta( $product_id, 'sku', $sku );
			DCTC_Support_Taxonomy_Service::update_term_meta( $product_id, 'price', $price );
		}

		// Sync Category Relationship
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table_rel,
			array(
				'object_id'     => $product_id,
				'object_type'   => 'term',
				'taxonomy_slug' => 'category',
			),
			array( '%d', '%s', '%s' )
		);

		if ( $category_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_rel,
				array(
					'object_id'     => $product_id,
					'object_type'   => 'term',
					'term_id'       => $category_id,
					'taxonomy_slug' => 'category',
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s', '%s' )
			);
		}

		return $product_id;
	}

	/**
	 * Delete a product by ID.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function delete_product( $product_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return false;
		}

		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			return DCTC_Support_Taxonomy_Service::delete_term( 'product', $product_id );
		}

		return false;
	}

	/**
	 * Sync published WooCommerce products into the support products taxonomy terms.
	 *
	 * @return int Number of products synced.
	 */
	public static function sync_woocommerce_products() {
		if ( ! post_type_exists( 'product' ) ) {
			return 0;
		}

		global $wpdb;
		$table_terms = $wpdb->prefix . 'dctc_support_terms';
		$table_meta  = $wpdb->prefix . 'dctc_support_term_meta';

		$wc_posts = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$synced = 0;
		foreach ( $wc_posts as $p ) {
			$slug = $p->post_name ? $p->post_name : sanitize_title( $p->post_title );

			// Check if already exists by wc_product_id meta or slug
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$existing_by_meta = $wpdb->get_var(
				$wpdb->prepare( "SELECT term_id FROM `$table_meta` WHERE meta_key = 'wc_product_id' AND meta_value = %s", (string) $p->ID )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$existing_by_slug = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM `$table_terms` WHERE taxonomy_slug = 'product' AND slug = %s", $slug )
			);

			$term_id = $existing_by_meta ? (int) $existing_by_meta : ( $existing_by_slug ? (int) $existing_by_slug : 0 );

			$price = 0.00;
			$sku   = '';
			if ( function_exists( 'wc_get_product' ) ) {
				$wc_prod = wc_get_product( $p->ID );
				if ( $wc_prod ) {
					$price = (float) $wc_prod->get_price();
					$sku   = (string) $wc_prod->get_sku();
				}
			}

			if ( ! $term_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$table_terms,
					array(
						'taxonomy_slug' => 'product',
						'parent_id'     => 0,
						'name'          => $p->post_title,
						'slug'          => $slug,
						'color'         => '#059669',
						'status'        => 'active',
						'created_at'    => current_time( 'mysql' ),
						'updated_at'    => current_time( 'mysql' ),
					)
				);
				$term_id = (int) $wpdb->insert_id;
				++$synced;
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_terms,
					array(
						'name'       => $p->post_title,
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => $term_id )
				);
			}

			if ( $term_id && class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
				DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'wc_product_id', $p->ID );
				DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'sku', $sku );
				DCTC_Support_Taxonomy_Service::update_term_meta( $term_id, 'price', $price );
			}
		}

		return $synced;
	}
}
