<?php
/**
 * DCTC AI WooCommerce Sales Assistant
 *
 * Provides dedicated WooCommerce product search, recommendation engine,
 * cart-aware context, and secure customer order status lookups.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_AI_WooCommerce
 */
class DCTC_AI_WooCommerce {

	use DCTC_AI_REST_Helpers;

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Search WooCommerce products with rich attributes, pricing, stock, and ratings.
	 *
	 * @param string $query
	 * @param int    $limit
	 * @return array
	 */
	public static function search_products( $query = '', $limit = 4 ) {
		if ( ! self::is_active() ) {
			return array();
		}

		$limit    = min( 10, max( 1, intval( $limit ) ) );
		$products = array();

		$args = array(
			'status' => 'publish',
			'limit'  => $limit,
		);

		if ( ! empty( $query ) ) {
			$args['s'] = sanitize_text_field( $query );
		} else {
			$args['featured'] = true;
		}

		$wc_products = function_exists( 'wc_get_products' ) ? wc_get_products( $args ) : array();

		foreach ( $wc_products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$products[] = self::format_product_data( $product );
		}

		return $products;
	}

	/**
	 * Get featured, on-sale, or top-selling products.
	 *
	 * @param string $type 'featured' | 'on_sale' | 'popular'
	 * @param int    $limit
	 * @return array
	 */
	public static function get_recommendations( $type = 'popular', $limit = 4 ) {
		if ( ! self::is_active() || ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$limit = min( 10, max( 1, intval( $limit ) ) );
		$args  = array(
			'status' => 'publish',
			'limit'  => $limit,
		);

		if ( $type === 'featured' ) {
			$args['featured'] = true;
		} elseif ( $type === 'on_sale' ) {
			$args['on_sale'] = true;
		} else {
			$args['orderby'] = 'popularity';
			$args['order']   = 'DESC';
		}

		$wc_products = wc_get_products( $args );
		$products    = array();

		foreach ( $wc_products as $product ) {
			if ( $product instanceof WC_Product ) {
				$products[] = self::format_product_data( $product );
			}
		}

		return $products;
	}

	/**
	 * Format a WooCommerce product object into clean structured array for AI & Frontend.
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	public static function format_product_data( $product ) {
		$id        = $product->get_id();
		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : wc_placeholder_img_src( 'medium' );

		$price         = $product->get_price();
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$is_on_sale    = $product->is_on_sale();

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '$';
		$formatted_price = function_exists( 'wc_price' ) ? html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' ) : ( $currency_symbol . $price );

		$short_desc = wp_strip_all_tags( $product->get_short_description() );
		if ( empty( $short_desc ) ) {
			$short_desc = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 18 );
		}

		$add_to_cart_url = $product->is_type( 'simple' ) && $product->is_in_stock()
			? add_query_arg( 'add-to-cart', $id, $product->get_permalink() )
			: $product->get_permalink();

		return array(
			'id'              => $id,
			'title'           => $product->get_name(),
			'permalink'       => $product->get_permalink(),
			'image'           => $image_url,
			'price'           => $price,
			'regular_price'   => $regular_price,
			'sale_price'      => $sale_price,
			'is_on_sale'      => (bool) $is_on_sale,
			'formatted_price' => $formatted_price,
			'currency_symbol' => $currency_symbol,
			'in_stock'        => $product->is_in_stock(),
			'stock_status'    => $product->get_stock_status(),
			'stock_quantity'  => $product->get_stock_quantity(),
			'rating'          => (float) $product->get_average_rating(),
			'rating_count'    => (int) $product->get_rating_count(),
			'short_desc'      => $short_desc,
			'type'            => $product->get_type(),
			'add_to_cart_url' => $add_to_cart_url,
		);
	}

	/**
	 * Get current visitor active cart context if available.
	 *
	 * @return array
	 */
	public static function get_cart_context() {
		if ( ! self::is_active() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array(
				'has_cart'        => false,
				'item_count'      => 0,
				'items'           => array(),
				'total'           => 0,
				'formatted_total' => '',
				'checkout_url'    => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
				'cart_url'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
			);
		}

		$cart  = WC()->cart;
		$items = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			if ( $product instanceof WC_Product ) {
				$items[] = array(
					'product_id' => $product->get_id(),
					'name'       => $product->get_name(),
					'quantity'   => $cart_item['quantity'],
					'price'      => $product->get_price(),
					'subtotal'   => wp_strip_all_tags( wc_price( $cart_item['line_total'] ) ),
				);
			}
		}

		return array(
			'has_cart'        => count( $items ) > 0,
			'item_count'      => $cart->get_cart_contents_count(),
			'items'           => $items,
			'total'           => $cart->get_cart_contents_total(),
			'formatted_total' => wp_strip_all_tags( $cart->get_cart_total() ),
			'checkout_url'    => wc_get_checkout_url(),
			'cart_url'        => wc_get_cart_url(),
		);
	}

	/**
	 * Secure Customer Order Status Lookup.
	 *
	 * Enforces strict ownership checks so customers can only access their own orders.
	 *
	 * @param int|string $order_id
	 * @param string     $billing_email
	 * @param int        $user_id
	 * @return array|\WP_Error
	 */
	public static function lookup_order_status( $order_id, $billing_email = '', $user_id = 0 ) {
		if ( ! self::is_active() || ! function_exists( 'wc_get_order' ) ) {
			return new \WP_Error( 'wc_inactive', __( 'WooCommerce is not active.', 'dragwyb-click-to-chat' ), array( 'status' => 400 ) );
		}

		$order_id = absint( $order_id );
		if ( $order_id <= 0 ) {
			return new \WP_Error( 'invalid_order_id', __( 'Please provide a valid numeric Order ID.', 'dragwyb-click-to-chat' ), array( 'status' => 400 ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! ( $order instanceof WC_Order ) ) {
			return new \WP_Error( 'order_not_found', __( 'Order not found. Please verify your Order ID.', 'dragwyb-click-to-chat' ), array( 'status' => 404 ) );
		}

		// Non-logged-in visitors cannot access order status
		if ( $user_id <= 0 && ! is_user_logged_in() ) {
			$settings  = class_exists( 'DCTC_AI_Settings_Handler' ) ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : array();
			$login_msg = ! empty( $settings['chatbot']['order_tracking_login_msg'] )
				? $settings['chatbot']['order_tracking_login_msg']
				: __( 'You must be logged in to your account to view or track orders.', 'dragwyb-click-to-chat' );
			return new \WP_Error( 'login_required', $login_msg, array( 'status' => 401 ) );
		}

		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}

		// Security Ownership Verification:
		// 1. If administrator/shop manager -> allow access.
		// 2. If order belongs to this user ID -> allow access.
		// 3. If order billing email matches user's registered account email or verified provided email -> allow access.
		$is_verified         = false;
		$order_user_id       = (int) $order->get_user_id();
		$order_billing_email = strtolower( trim( $order->get_billing_email() ) );

		$current_user_data  = $user_id > 0 ? get_userdata( $user_id ) : null;
		$current_user_email = $current_user_data ? strtolower( trim( $current_user_data->user_email ) ) : '';

		if ( $user_id > 0 && ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) ) {
			$is_verified = true;
		}

		if ( ! $is_verified && $user_id > 0 && $order_user_id > 0 && $order_user_id === $user_id ) {
			$is_verified = true;
		}

		if ( ! $is_verified && ! empty( $current_user_email ) && hash_equals( $order_billing_email, $current_user_email ) ) {
			$is_verified = true;
		}

		if ( ! $is_verified && ! empty( $billing_email ) ) {
			$provided_email = strtolower( trim( $billing_email ) );
			if ( ! empty( $provided_email ) && hash_equals( $order_billing_email, $provided_email ) ) {
				$is_verified = true;
			}
		}

		if ( ! $is_verified ) {
			$settings     = class_exists( 'DCTC_AI_Settings_Handler' ) ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : array();
			$mismatch_msg = ! empty( $settings['chatbot']['order_mismatch_msg'] )
				? $settings['chatbot']['order_mismatch_msg']
				: __( 'This order was purchased with a different email address. For privacy and security reasons, order details cannot be displayed.', 'dragwyb-click-to-chat' );

			return new \WP_Error(
				'order_email_mismatch',
				$mismatch_msg,
				array( 'status' => 403 )
			);
		}

		$items_summary = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$items_summary[] = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'subtotal' => wp_strip_all_tags( wc_price( $item->get_subtotal() ) ),
			);
		}

		$status      = $order->get_status();
		$status_name = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $status ) : ucfirst( $status );

		return array(
			'success'          => true,
			'order_id'         => $order->get_id(),
			'order_number'     => $order->get_order_number(),
			'status'           => $status,
			'status_label'     => $status_name,
			'date_created'     => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '',
			'formatted_total'  => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'payment_method'   => $order->get_payment_method_title(),
			'item_count'       => $order->get_item_count(),
			'items'            => $items_summary,
			'shipping_address' => $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address(),
		);
	}

	/**
	 * Build dynamic WooCommerce context string for LLM system prompt.
	 *
	 * @param string $prompt
	 * @return string
	 */
	public static function build_llm_product_context( $prompt ) {
		if ( ! self::is_active() ) {
			return '';
		}

		// Detect if prompt is asking about products, buying, price, stock, or store catalog
		$is_product_query = (bool) preg_match(
			'/\b(product|products|buy|purchase|price|cost|stock|available|store|shop|recommend|recommendation|compare|shoes|shirt|item|items|catalog|order|shipping)\b/i',
			$prompt
		);

		if ( ! $is_product_query ) {
			return '';
		}

		$products = self::search_products( $prompt, 4 );
		if ( empty( $products ) ) {
			$products = self::get_recommendations( 'popular', 4 );
		}

		if ( empty( $products ) ) {
			return '';
		}

		$lines = array( "\n\n### Current Live WooCommerce Store Catalog:" );
		foreach ( $products as $p ) {
			$stock_str = $p['in_stock'] ? 'In Stock' : 'Out of Stock';
			$sale_str  = $p['is_on_sale'] ? " (ON SALE! Regular: {$p['currency_symbol']}{$p['regular_price']})" : '';
			$lines[]   = "- [{$p['title']}]({$p['permalink']}): Price {$p['formatted_price']}{$sale_str} | Status: {$stock_str} | Rating: {$p['rating']}/5 ({$p['rating_count']} reviews). Description: {$p['short_desc']}";
		}

		$cart = self::get_cart_context();
		if ( $cart['has_cart'] && ! empty( $cart['items'] ) ) {
			$lines[] = "\nCustomer's Current Active Cart ({$cart['item_count']} items, Total: {$cart['formatted_total']}):";
			foreach ( $cart['items'] as $it ) {
				$lines[] = "  * {$it['name']} x {$it['quantity']} ({$it['subtotal']})";
			}
			$lines[] = 'Direct Checkout URL: ' . $cart['checkout_url'];
		}

		return implode( "\n", $lines );
	}

	/**
	 * REST Callback: Product search API.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_search_products( $request ) {
		$query = sanitize_text_field( $request->get_param( 'query' ) ?? '' );
		$limit = absint( $request->get_param( 'limit' ) ?? 4 );

		$products = self::search_products( $query, $limit );

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'products' => $products,
				'count'    => count( $products ),
			),
			200
		);
	}

	/**
	 * REST Callback: Get active cart context.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_get_cart( $request ) {
		$cart = self::get_cart_context();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'cart'    => $cart,
			),
			200
		);
	}

	/**
	 * REST Callback: Secure Order Status Lookup.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function rest_lookup_order( $request ) {
		if ( ! is_user_logged_in() ) {
			$settings  = class_exists( 'DCTC_AI_Settings_Handler' ) ? DCTC_AI_Settings_Handler::dctc_ai_get_all_settings() : array();
			$login_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
			$login_msg = ! empty( $settings['chatbot']['order_tracking_login_msg'] )
				? str_replace( '{login_url}', $login_url, $settings['chatbot']['order_tracking_login_msg'] )
				/* translators: %s: My account login URL */
				: sprintf( __( 'Please <a href="%s">log in to your account</a> to track your orders.', 'dragwyb-click-to-chat' ), esc_url( $login_url ) );
			return $this->error_response( $login_msg, 401 );
		}

		$params   = $request->get_json_params();
		$order_id = absint( $params['order_id'] ?? $request->get_param( 'order_id' ) );
		$email    = sanitize_email( $params['email'] ?? $request->get_param( 'email' ) );
		$user_id  = get_current_user_id();

		$result = self::lookup_order_status( $order_id, $email, $user_id );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result->get_error_message(), $result->get_error_data()['status'] ?? 400 );
		}

		return new \WP_REST_Response( $result, 200 );
	}
}
