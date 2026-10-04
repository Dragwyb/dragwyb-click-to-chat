<?php
/**
 * DCTC Support Product Service
 *
 * Manages support products catalog (custom and WooCommerce synced)
 * for conditional ticket classification and routing.
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
		$table = $wpdb->prefix . 'dctc_support_products';

		// First check if table exists
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" );
		if ( ! $table_exists ) {
			if ( class_exists( 'DCTC_Support_DB' ) ) {
				DCTC_Support_DB::create_tables();
			}
		}

		$status = isset( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';
		$where  = '1=1';

		if ( ! empty( $status ) && 'all' !== $status ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}

		if ( ! empty( $args['category_id'] ) ) {
			$where .= $wpdb->prepare( ' AND category_id = %d', absint( $args['category_id'] ) );
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where .= $wpdb->prepare( ' AND (name LIKE %s OR sku LIKE %s)', $search, $search );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM `$table` WHERE $where ORDER BY name ASC", ARRAY_A );
		$products = is_array( $rows ) ? $rows : array();

		// If no custom products yet and WooCommerce is active, automatically sync WooCommerce products
		if ( empty( $products ) && post_type_exists( 'product' ) ) {
			self::sync_woocommerce_products();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( "SELECT * FROM `$table` WHERE $where ORDER BY name ASC", ARRAY_A );
			$products = is_array( $rows ) ? $rows : array();
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
		$table = $wpdb->prefix . 'dctc_support_products';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE id = %d", absint( $product_id ) ),
			ARRAY_A
		);
	}

	/**
	 * Save / Add / Update a product.
	 *
	 * @param array $data Product payload.
	 * @return int|false Product ID or false on failure.
	 */
	public static function save_product( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_categories' ) && ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_tags' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_products';

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
			'name'          => $name,
			'slug'          => $slug,
			'category_id'   => $category_id,
			'wc_product_id' => $wc_product_id,
			'sku'           => $sku,
			'price'         => $price,
			'status'        => $status,
		);

		if ( $id ) {
			$fields['updated_at'] = current_time( 'mysql' );
			$updated = $wpdb->update( $table, $fields, array( 'id' => $id ) );
			return false !== $updated ? $id : false;
		} else {
			$fields['created_at'] = current_time( 'mysql' );
			$fields['updated_at'] = current_time( 'mysql' );
			$inserted = $wpdb->insert( $table, $fields );
			return $inserted ? $wpdb->insert_id : false;
		}
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

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_products';

		$deleted = $wpdb->delete(
			$table,
			array( 'id' => absint( $product_id ) ),
			array( '%d' )
		);

		return (bool) $deleted;
	}

	/**
	 * Sync published WooCommerce products into the support products table.
	 *
	 * @return int Number of products synced.
	 */
	public static function sync_woocommerce_products() {
		if ( ! post_type_exists( 'product' ) ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_products';

		$wc_posts = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$synced = 0;
		foreach ( $wc_posts as $p ) {
			// Check if already exists by wc_product_id or title
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM `$table` WHERE wc_product_id = %d OR slug = %s", $p->ID, $p->post_name )
			);

			$price = 0.00;
			$sku   = '';
			if ( function_exists( 'wc_get_product' ) ) {
				$wc_prod = wc_get_product( $p->ID );
				if ( $wc_prod ) {
					$price = (float) $wc_prod->get_price();
					$sku   = (string) $wc_prod->get_sku();
				}
			}

			if ( ! $existing ) {
				$wpdb->insert(
					$table,
					array(
						'name'          => $p->post_title,
						'slug'          => $p->post_name,
						'category_id'   => 0,
						'wc_product_id' => $p->ID,
						'sku'           => $sku,
						'price'         => $price,
						'status'        => 'active',
						'created_at'    => current_time( 'mysql' ),
						'updated_at'    => current_time( 'mysql' ),
					)
				);
				$synced++;
			} else {
				$wpdb->update(
					$table,
					array(
						'name'          => $p->post_title,
						'sku'           => $sku,
						'price'         => $price,
						'updated_at'    => current_time( 'mysql' ),
					),
					array( 'id' => $existing )
				);
			}
		}

		return $synced;
	}
}
