<?php
/**
 * DCTC Support Agent Service
 *
 * Manages support agent profiles, roles, availability, workload calculations,
 * and eligibility queries.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Agent_Service
 */
class DCTC_Support_Agent_Service {

	/**
	 * Get all agents.
	 *
	 * @param array $args Query filters (active, support_role, availability_status).
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_agents( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_agents';

		$where = '1=1';

		if ( isset( $args['active'] ) && '' !== $args['active'] ) {
			$active_val = $args['active'] ? 1 : 0;
			$where     .= $wpdb->prepare( ' AND active = %d', $active_val );
		}

		if ( ! empty( $args['support_role'] ) ) {
			$where .= $wpdb->prepare( ' AND support_role = %s', sanitize_key( $args['support_role'] ) );
		}

		if ( ! empty( $args['availability_status'] ) ) {
			$where .= $wpdb->prepare( ' AND availability_status = %s', sanitize_key( $args['availability_status'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM `$table` WHERE $where ORDER BY id ASC", ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as &$row ) {
			$user = get_userdata( $row['wp_user_id'] );
			$row['display_name'] = $user ? $user->display_name : 'Agent #' . $row['id'];
			$row['user_email']   = $user ? $user->user_email : '';
			$row['avatar_url']   = get_avatar_url( $row['wp_user_id'], array( 'size' => 64 ) );

			$row['skills'] = ! empty( $row['skills'] ) ? json_decode( $row['skills'], true ) : array();
			$row['skills'] = is_array( $row['skills'] ) ? $row['skills'] : array();

			$row['allowed_categories'] = ! empty( $row['allowed_categories'] ) ? json_decode( $row['allowed_categories'], true ) : array();
			$row['allowed_categories'] = is_array( $row['allowed_categories'] ) ? $row['allowed_categories'] : array();
		}

		return $rows;
	}

	/**
	 * Get agent by record ID.
	 *
	 * @param int $agent_id Support agent table ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_agent_by_id( $agent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_agents';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE id = %d", absint( $agent_id ) ),
			ARRAY_A
		);

		if ( $row ) {
			$user = get_userdata( $row['wp_user_id'] );
			$row['display_name'] = $user ? $user->display_name : 'Agent #' . $row['id'];
			$row['user_email']   = $user ? $user->user_email : '';
			$row['avatar_url']   = get_avatar_url( $row['wp_user_id'], array( 'size' => 64 ) );

			$row['skills'] = ! empty( $row['skills'] ) ? json_decode( $row['skills'], true ) : array();
			$row['skills'] = is_array( $row['skills'] ) ? $row['skills'] : array();

			$row['allowed_categories'] = ! empty( $row['allowed_categories'] ) ? json_decode( $row['allowed_categories'], true ) : array();
			$row['allowed_categories'] = is_array( $row['allowed_categories'] ) ? $row['allowed_categories'] : array();
		}

		return $row;
	}

	/**
	 * Get agent by WP User ID.
	 *
	 * @param int $wp_user_id WordPress User ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_agent_by_user_id( $wp_user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_agents';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `$table` WHERE wp_user_id = %d", absint( $wp_user_id ) ),
			ARRAY_A
		);

		if ( $row ) {
			$user = get_userdata( $row['wp_user_id'] );
			$row['display_name'] = $user ? $user->display_name : 'Agent #' . $row['id'];
			$row['user_email']   = $user ? $user->user_email : '';
			$row['avatar_url']   = get_avatar_url( $row['wp_user_id'], array( 'size' => 64 ) );

			$row['skills'] = ! empty( $row['skills'] ) ? json_decode( $row['skills'], true ) : array();
			$row['skills'] = is_array( $row['skills'] ) ? $row['skills'] : array();

			$row['allowed_categories'] = ! empty( $row['allowed_categories'] ) ? json_decode( $row['allowed_categories'], true ) : array();
			$row['allowed_categories'] = is_array( $row['allowed_categories'] ) ? $row['allowed_categories'] : array();
		}

		return $row;
	}

	/**
	 * Save or update an agent profile.
	 *
	 * @param array $data Agent data.
	 * @return int|false
	 */
	public static function save_agent( $data ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_agents' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_agents';

		$id                         = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$wp_user_id                 = ! empty( $data['wp_user_id'] ) ? absint( $data['wp_user_id'] ) : 0;
		$support_role               = ! empty( $data['support_role'] ) ? sanitize_key( $data['support_role'] ) : 'support';
		$seniority                  = ! empty( $data['seniority'] ) ? sanitize_key( $data['seniority'] ) : 'support';
		$active                     = isset( $data['active'] ) ? ( $data['active'] ? 1 : 0 ) : 1;
		$assignment_enabled         = isset( $data['assignment_enabled'] ) ? ( $data['assignment_enabled'] ? 1 : 0 ) : 1;
		$availability_status        = ! empty( $data['availability_status'] ) ? sanitize_key( $data['availability_status'] ) : 'available';
		$max_active_tickets         = isset( $data['max_active_tickets'] ) ? max( 1, absint( $data['max_active_tickets'] ) ) : 10;
		$notification_email_enabled = isset( $data['notification_email_enabled'] ) ? ( $data['notification_email_enabled'] ? 1 : 0 ) : 1;

		$skills = isset( $data['skills'] ) && is_array( $data['skills'] )
			? wp_json_encode( array_map( 'sanitize_key', $data['skills'] ) )
			: wp_json_encode( array() );

		$allowed_categories = isset( $data['allowed_categories'] ) && is_array( $data['allowed_categories'] )
			? wp_json_encode( array_map( 'absint', $data['allowed_categories'] ) )
			: wp_json_encode( array() );

		if ( ! $id && ! $wp_user_id ) {
			return false;
		}

		// Check if an agent record with this wp_user_id already exists to prevent duplicate key error.
		if ( ! $id && $wp_user_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_id = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM `$table` WHERE wp_user_id = %d LIMIT 1", $wp_user_id )
			);
			if ( $existing_id ) {
				$id = absint( $existing_id );
			}
		}

		$fields = array(
			'support_role'               => $support_role,
			'seniority'                  => $seniority,
			'skills'                     => $skills,
			'allowed_categories'         => $allowed_categories,
			'active'                     => $active,
			'assignment_enabled'         => $assignment_enabled,
			'availability_status'        => $availability_status,
			'max_active_tickets'         => $max_active_tickets,
			'notification_email_enabled' => $notification_email_enabled,
			'updated_at'                 => current_time( 'mysql' ),
		);

		if ( $id ) {
			$wpdb->suppress_errors( true );
			$updated = $wpdb->update( $table, $fields, array( 'id' => $id ) );
			$wpdb->suppress_errors( false );
			return false !== $updated ? $id : false;
		} else {
			$fields['wp_user_id']               = $wp_user_id;
			$fields['current_active_tickets']   = 0;
			$fields['notification_preferences'] = wp_json_encode( array( 'all' => true ) );
			$fields['created_at']               = current_time( 'mysql' );
			$wpdb->suppress_errors( true );
			$inserted = $wpdb->insert( $table, $fields );
			$wpdb->suppress_errors( false );
			return $inserted ? $wpdb->insert_id : false;
		}
	}

	/**
	 * Recalculate and update current active ticket workload for an agent.
	 *
	 * @param int $agent_id Agent ID.
	 * @return int New active ticket count.
	 */
	public static function update_workload( $agent_id ) {
		global $wpdb;
		$table_agents      = $wpdb->prefix . 'dctc_support_agents';
		$table_tickets     = $wpdb->prefix . 'dctc_support_tickets';
		$table_ticket_meta = $wpdb->prefix . 'dctc_support_ticket_meta';
		$agent_id          = absint( $agent_id );

		// Active tickets are those in open, pending, waiting_customer states
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT t.id) FROM `$table_tickets` t 
				INNER JOIN `$table_ticket_meta` tm ON t.id = tm.ticket_id 
				WHERE tm.meta_key = 'assigned_agent_id' AND tm.meta_value = %d 
				AND t.status IN ('new', 'open', 'pending', 'waiting_customer')",
				$agent_id
			)
		);

		$wpdb->update(
			$table_agents,
			array( 'current_active_tickets' => $count ),
			array( 'id' => $agent_id ),
			array( '%d' ),
			array( '%d' )
		);

		return $count;
	}

	/**
	 * Delete an agent.
	 *
	 * @param int $agent_id Agent ID.
	 * @return bool
	 */
	public static function delete_agent( $agent_id ) {
		if ( ! DCTC_Support_Permission_Service::current_user_can_support( 'manage_agents' ) ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'dctc_support_agents';

		$deleted = $wpdb->delete( $table, array( 'id' => absint( $agent_id ) ), array( '%d' ) );
		return (bool) $deleted;
	}
}
