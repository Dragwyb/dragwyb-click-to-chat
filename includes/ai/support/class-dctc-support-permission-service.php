<?php
/**
 * DCTC Support Permission Service
 *
 * Handles granular staff capabilities, role permissions,
 * dynamic permission matrices, and object-level ticket authorization.
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
	 * Option key for stored role permissions matrix.
	 */
	const OPTION_ROLE_PERMS = 'dctc_support_role_permissions';

	/**
	 * Default role permission matrix.
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
				'full_admin_access'      => true,
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
				'manage_settings'        => true,
				'delete_ticket'          => true,
				'view_customer_data'     => true,
				'view_woocommerce_data'  => true,
				'full_admin_access'      => false,
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
				'manage_tags'            => true,
				'manage_categories'      => false,
				'manage_agents'          => false,
				'manage_settings'        => false,
				'delete_ticket'          => false,
				'view_customer_data'     => true,
				'view_woocommerce_data'  => true,
				'full_admin_access'      => false,
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
				'full_admin_access'      => false,
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
				'full_admin_access'      => false,
			),
		);
	}

	/**
	 * Get the active role permissions matrix (defaults merged with custom options).
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function get_role_permissions() {
		$defaults = self::get_default_permissions();
		$custom   = get_option( self::OPTION_ROLE_PERMS, array() );

		if ( ! is_array( $custom ) || empty( $custom ) ) {
			return $defaults;
		}

		$merged = $defaults;
		foreach ( $defaults as $role => $caps ) {
			if ( isset( $custom[ $role ] ) && is_array( $custom[ $role ] ) ) {
				foreach ( $caps as $cap => $def_val ) {
					if ( isset( $custom[ $role ][ $cap ] ) ) {
						$merged[ $role ][ $cap ] = (bool) $custom[ $role ][ $cap ];
					}
				}
			}
		}

		return $merged;
	}

	/**
	 * Save updated role permissions matrix.
	 *
	 * @param array<string, array<string, bool>> $matrix Role permissions.
	 * @return bool
	 */
	public static function save_role_permissions( $matrix ) {
		if ( ! self::current_user_can_support( 'manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$sanitized = array();
		$defaults  = self::get_default_permissions();

		foreach ( $defaults as $role => $caps ) {
			$sanitized[ $role ] = array();
			foreach ( $caps as $cap => $val ) {
				$sanitized[ $role ][ $cap ] = isset( $matrix[ $role ][ $cap ] ) ? (bool) $matrix[ $role ][ $cap ] : false;
			}
		}

		return update_option( self::OPTION_ROLE_PERMS, $sanitized );
	}

	/**
	 * Get complete permission map for a specific user.
	 *
	 * @param int|null $user_id Optional WP user ID.
	 * @return array<string, bool>
	 */
	public static function get_user_permissions( $user_id = null ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return array();
		}

		// WordPress site admin has full permissions
		$is_wp_admin = user_can( $user_id, 'manage_options' );

		// Look up agent record
		global $wpdb;
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$agent = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table_agents` WHERE wp_user_id = %d AND active = 1", $user_id ),
			ARRAY_A
		);

		$all_caps = array_keys( self::get_default_permissions()['admin'] );
		$matrix   = self::get_role_permissions();
		$role     = $agent && ! empty( $agent['support_role'] ) ? $agent['support_role'] : ( $is_wp_admin ? 'admin' : '' );

		$perms = array();
		foreach ( $all_caps as $cap ) {
			if ( $is_wp_admin ) {
				$perms[ $cap ] = true;
			} elseif ( $role && isset( $matrix[ $role ] ) ) {
				$perms[ $cap ] = ! empty( $matrix[ $role ]['full_admin_access'] ) || ! empty( $matrix[ $role ][ $cap ] );
			} else {
				$perms[ $cap ] = false;
			}
		}

		$perms['is_admin']           = $is_wp_admin || ! empty( $perms['full_admin_access'] );
		$perms['support_role']       = $role ? $role : ( $is_wp_admin ? 'admin' : 'none' );
		$perms['is_agent']           = ! empty( $agent );
		$perms['agent_id']           = $agent ? (int) $agent['id'] : null;
		$perms['availability_status'] = $agent ? $agent['availability_status'] : 'offline';

		return $perms;
	}

	/**
	 * Check if current user has a specific support capability.
	 *
	 * @param string   $capability Capability slug (e.g. 'view_tickets', 'manage_agents').
	 * @param int|null $user_id    Optional WP User ID.
	 * @return bool
	 */
	public static function current_user_can_support( $capability, $user_id = null ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// WordPress Administrators have full support access by default
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$perms = self::get_user_permissions( $user_id );
		return ! empty( $perms[ $capability ] ) || ! empty( $perms['full_admin_access'] );
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

		// Admin / Manager check
		if ( user_can( $user_id, 'manage_options' ) || self::current_user_can_support( 'full_admin_access', $user_id ) ) {
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

