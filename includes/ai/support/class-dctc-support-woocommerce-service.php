<?php
/**
 * DCTC Support WooCommerce Service
 *
 * Extracts authorized WooCommerce customer order summary, order history,
 * and spending data for the agent support workspace.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_WooCommerce_Service
 */
class DCTC_Support_WooCommerce_Service {

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Get WooCommerce customer order context for a support ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_customer_wc_context( $ticket_id ) {
		if ( ! self::is_active() ) {
			return null;
		}

		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'view_woocommerce_data' ) ) {
			return null;
		}

		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return null;
		}

		$user_id = ! empty( $ticket['customer_wp_user_id'] ) ? absint( $ticket['customer_wp_user_id'] ) : 0;
		$email   = ! empty( $ticket['customer_email'] ) ? sanitize_email( $ticket['customer_email'] ) : '';

		if ( ! $user_id && ! $email ) {
			return null;
		}

		// Query customer orders
		$query_args = array(
			'limit'   => 5,
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		if ( $user_id ) {
			$query_args['customer_id'] = $user_id;
		} else {
			$query_args['billing_email'] = $email;
		}

		$orders = wc_get_orders( $query_args );
		$recent_orders = array();
		$total_spent   = 0.0;
		$order_count   = 0;

		if ( $user_id ) {
			$customer    = new WC_Customer( $user_id );
			$total_spent = (float) $customer->get_total_spent();
			$order_count = (int) $customer->get_order_count();
		} else {
			$order_count = count( $orders );
		}

		foreach ( $orders as $order ) {
			$items_names = array();
			foreach ( $order->get_items() as $item ) {
				$items_names[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
			}

			$recent_orders[] = array(
				'id'            => $order->get_id(),
				'number'        => $order->get_order_number(),
				'status'        => $order->get_status(),
				'status_name'   => wc_get_order_status_name( $order->get_status() ),
				'total'         => $order->get_formatted_order_total(),
				'date'          => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '',
				'view_url'      => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
				'items_summary' => implode( ', ', array_slice( $items_names, 0, 3 ) ),
			);
		}

		return array(
			'is_active'      => true,
			'order_count'    => $order_count,
			'total_spent'    => wc_price( $total_spent ),
			'recent_orders'  => $recent_orders,
		);
	}
}
