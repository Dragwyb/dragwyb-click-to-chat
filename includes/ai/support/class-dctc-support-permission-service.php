<?php
/**
 * DCTC Support Permission Service
 *
 * Handles granular staff capabilities, role permissions,
 * object-level ticket authorization, and customer ownership checks.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Permission_Service
 */
class DCTC_Support_Permission_Service {

	/**
	 * Default role permission matrix
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function get_default_permissions() {
		return array(
			'admin'   => array(
				'view_tickets'           => true,
				'view_all_tickets'       => true,
				'view_assigned_tickets'  => true,
				'create_ticket'          => true,
				'reply_ticket'           => true,
				'reply_customer'         => true,
				'internal_note'          => true,
				'take_ai_control'        => true,
				'release_ai_control'     => true,
				'assign_ticket'          => true,
				'reassign_ticket'        => true,
				'change_status'          => true,
				'change_priority'        => true,
				'manage_tags'            => true,
				'manage_categories'      => true,
				'manage_agents'          => true,
				'manage_settings'        => true,
				'delete_ticket'          => true,
				'view_customer_data'     => true,
				'view_woocommerce_data'  => true,
			),
			'manager' => array(
				'view_tickets'           => true,
				'view_all_tickets'       => true,
				'view_assigned_tickets'  => true,
				'create_ticket'          => true,
				'reply_ticket'           => true,
				'reply_customer'         => true,
				'internal_note'          => true,
				'take_ai_control'        => true,
				'release_ai_control'     => true,
				'assign_ticket'          => true,
				'reassign_ticket'        => true,
				'change_status'          => true,
				'change_priority'        => true,
				'manage_tags'            => true,
				'manage_categories'      => true,
				'manage_agents'          => true,
				'manage_settings'        => false,
				'delete_ticket'          => true,
				'view_customer_data'     => true,
				'view_woocommerce_data'  => true,
			),
			'senior'  => array(
				'view_tickets'           => true,
				'view_all_tickets'       => true,
				'view_assigned_tickets'  => true,
				'create_ticket'          => true,
				'reply_ticket'           => true,
				'reply_customer'         => true,
				'internal_note'          => true,
				'take_ai_control'        => true,
				'release_ai_control'     => true,
				'assign_ticket'          => true,
				'reassign_ticket'        => true,
				'change_status'          => true,
				'change_priority'        => true,
				'manage_tags'            => false,
				'manage_categories'      => false,
				'manage_agents'          => false,
				'manage_settings'        => false,
				'delete_ticket'          => false,
				'view_customer_data'     => true,
				'view_woocommerce_data'  => true,
			),
			'support' => array(
				'view_tickets'           => true,
				'view_all_tickets'       => false,
				'view_assigned_tickets'  => true,
				'create_ticket'          => true,
				'reply_ticket'           => true,
				'reply_customer'         => true,
				'internal_note'          => true,
				'take_ai_control'        => true,
				'release_ai_control'     => true,
				'assign_ticket'          => false,
				'reassign_ticket'        => false,
				'change_status'          => true,
				'change_priority'        => false,
				'manage_tags'            => false,
				'manage_categories'      => false,
				'manage_agents'          => false,
				'manage_settings'        => false,
				'delete_ticket'          => false,
				'view_customer_data'     => true,
				'view_woocommerce_data'  => true,
			),
			'fresher' => array(
				'view_tickets'           => true,
				'view_all_tickets'       => false,
				'view_assigned_tickets'  => true,
				'create_ticket'          => true,
				'reply_ticket'           => true,
				'reply_customer'         => true,
				'internal_note'          => true,
				'take_ai_control'        => false,
				'release_ai_control'     => false,
				'assign_ticket'          => false,
				'reassign_ticket'        => false,
				'change_status'          => false,
				'change_priority'        => false,
				'manage_tags'            => false,
				'manage_categories'      => false,
				'manage_agents'          => false,
				'manage_settings'        => false,
				'delete_ticket'          => false,
				'view_customer_data'     => false,
				'view_woocommerce_data'  => false,
			),
		);
	}

	/**
	 * Check if current user has a specific support capability.
	 *
	 * @param string   $capability Capability slug (e.g. 'view_tickets', 'take_ai_control').
	 * @param int|null $user_id    Optional WP User ID (defaults to current user).
	 * @return bool
	 */
	public static function current_user_can_support( $capability, $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// WordPress Administrators have full support access by default
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Look up agent record
		global $wpdb;
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$agent = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table_agents` WHERE wp_user_id = %d AND active = 1", $user_id ),
			ARRAY_A
		);

		if ( ! $agent ) {
			return false;
		}

		$role        = ! empty( $agent['support_role'] ) ? $agent['support_role'] : 'support';
		$matrix      = self::get_default_permissions();
		$role_matrix = isset( $matrix[ $role ] ) ? $matrix[ $role ] : $matrix['support'];

		return ! empty( $role_matrix[ $capability ] );
	}

	/**
	 * Check if a user can access a specific ticket.
	 *
	 * @param array|object $ticket  The ticket object/array.
	 * @param int|null     $user_id Optional WP User ID.
	 * @return bool
	 */
	public static function can_access_ticket( $ticket, $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$ticket_array = (array) $ticket;

		// Customer check: Does user own this ticket?
		if ( ! empty( $ticket_array['customer_wp_user_id'] ) && (int) $ticket_array['customer_wp_user_id'] === $user_id ) {
			return true;
		}

		// Admin check
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		// Check if user has view_all_tickets capability
		if ( self::current_user_can_support( 'view_all_tickets', $user_id ) ) {
			return true;
		}

		// Check if user is the assigned agent
		global $wpdb;
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$agent = $wpdb->get_row(
			$wpdb->prepare( "SELECT id FROM `$table_agents` WHERE wp_user_id = %d AND active = 1", $user_id ),
			ARRAY_A
		);

		if ( $agent && ! empty( $ticket_array['assigned_agent_id'] ) ) {
			return (int) $ticket_array['assigned_agent_id'] === (int) $agent['id'];
		}

		return false;
	}
}
