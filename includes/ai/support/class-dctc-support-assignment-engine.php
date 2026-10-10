<?php
/**
 * DCTC Support Assignment Engine
 *
 * Implements deterministic ticket routing and fair workload distribution:
 * Eligibility -> Workload Limits -> Ranking -> Least Loaded / Round Robin -> Fallback.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Assignment_Engine
 */
class DCTC_Support_Assignment_Engine {

	/**
	 * Automatically assign a ticket to the best eligible agent.
	 *
	 * @param int $ticket_id Support Ticket ID.
	 * @return int|false Assigned Agent ID or false if left unassigned.
	 */
	public static function assign_ticket_automatically( $ticket_id ) {
		$ticket_id = absint( $ticket_id );
		$ticket    = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return false;
		}

		$settings = get_option( 'dctc_support_settings', array() );
		if ( isset( $settings['auto_assign'] ) && ! $settings['auto_assign'] ) {
			return false;
		}

		$algorithm            = ! empty( $settings['assignment_algorithm'] ) ? $settings['assignment_algorithm'] : 'least_loaded';
		$respect_availability = ! isset( $settings['respect_availability'] ) || (bool) $settings['respect_availability'];
		$respect_workload     = ! isset( $settings['respect_workload'] ) || (bool) $settings['respect_workload'];
		$fallback_agent_id    = ! empty( $settings['fallback_agent_id'] ) ? absint( $settings['fallback_agent_id'] ) : 0;

		// 1. Gather Required Skills for the Ticket
		$required_skills = array();
		if ( ! empty( $ticket['category_id'] ) ) {
			$category = DCTC_Support_Category_Service::get_category( $ticket['category_id'] );
			if ( $category && ! empty( $category['required_skills'] ) ) {
				$required_skills = $category['required_skills'];
			}
		}

		// 2. Fetch All Active Candidates
		$candidate_args = array(
			'active'             => 1,
			'assignment_enabled' => 1,
		);
		if ( $respect_availability ) {
			$candidate_args['availability_status'] = 'available';
		}

		$all_agents = DCTC_Support_Agent_Service::get_agents( $candidate_args );
		if ( empty( $all_agents ) ) {
			return self::handle_fallback( $ticket_id, $fallback_agent_id, 'No available agents found' );
		}

		// 3. Filter by Category & Workload Limits
		$eligible_agents = array();
		foreach ( $all_agents as $agent ) {
			// Workload limit check
			if ( $respect_workload && $agent['current_active_tickets'] >= $agent['max_active_tickets'] ) {
				continue; // Overloaded
			}

			// Allowed categories check
			if ( ! empty( $agent['allowed_categories'] ) && ! empty( $ticket['category_id'] ) ) {
				if ( ! in_array( (int) $ticket['category_id'], array_map( 'intval', $agent['allowed_categories'] ), true ) ) {
					continue; // Not authorized for this category
				}
			}

			$eligible_agents[] = $agent;
		}

		if ( empty( $eligible_agents ) ) {
			return self::handle_fallback( $ticket_id, $fallback_agent_id, 'All eligible agents are currently at max capacity' );
		}

		// 4. Rank & Score Candidates
		$scored_agents = array();
		foreach ( $eligible_agents as $agent ) {
			$score = 100;

			// Skill Matching (+30 points per matching skill)
			$agent_skills = is_array( $agent['skills'] ) ? $agent['skills'] : array();
			$matched_skills = 0;
			if ( ! empty( $required_skills ) ) {
				foreach ( $required_skills as $req_skill ) {
					if ( in_array( $req_skill, $agent_skills, true ) ) {
						$score += 30;
						$matched_skills++;
					}
				}
				// If skills are required but agent has 0 matches, penalize
				if ( $matched_skills === 0 ) {
					$score -= 40;
				}
			}

			// Seniority & Urgent Priority (+25 points for senior/manager on urgent tickets)
			if ( 'urgent' === $ticket['priority'] ) {
				if ( in_array( $agent['seniority'], array( 'senior', 'manager' ), true ) ) {
					$score += 25;
				}
			}

			// Workload Penalty (-10 points per active ticket for balancing)
			$score -= ( (int) $agent['current_active_tickets'] * 10 );

			$agent['calculated_score'] = $score;
			$scored_agents[]           = $agent;
		}

		// 5. Select Winning Agent based on Configured Algorithm
		$selected_agent = null;

		if ( 'round_robin' === $algorithm ) {
			// Sort by last_assigned_at ASC (oldest assignment wins)
			usort( $scored_agents, function( $a, $b ) {
				$time_a = ! empty( $a['last_assigned_at'] ) ? strtotime( $a['last_assigned_at'] ) : 0;
				$time_b = ! empty( $b['last_assigned_at'] ) ? strtotime( $b['last_assigned_at'] ) : 0;
				return $time_a <=> $time_b;
			} );
			$selected_agent = $scored_agents[0];
		} elseif ( 'skill_match' === $algorithm ) {
			// Sort by calculated_score DESC
			usort( $scored_agents, function( $a, $b ) {
				return $b['calculated_score'] <=> $a['calculated_score'];
			} );
			$selected_agent = $scored_agents[0];
		} else {
			// Default: least_loaded with round-robin tiebreaker
			usort( $scored_agents, function( $a, $b ) {
				if ( (int) $a['current_active_tickets'] === (int) $b['current_active_tickets'] ) {
					$time_a = ! empty( $a['last_assigned_at'] ) ? strtotime( $a['last_assigned_at'] ) : 0;
					$time_b = ! empty( $b['last_assigned_at'] ) ? strtotime( $b['last_assigned_at'] ) : 0;
					return $time_a <=> $time_b;
				}
				return (int) $a['current_active_tickets'] <=> (int) $b['current_active_tickets'];
			} );
			$selected_agent = $scored_agents[0];
		}

		if ( ! $selected_agent ) {
			return self::handle_fallback( $ticket_id, $fallback_agent_id, 'Assignment algorithm yielded no agent' );
		}

		// 6. Execute Assignment
		$assigned_id = (int) $selected_agent['id'];
		$reason      = sprintf( 'Auto-assigned via %s algorithm (Score: %d)', $algorithm, $selected_agent['calculated_score'] );

		DCTC_Support_Ticket_Service::assign_ticket(
			$ticket_id,
			$assigned_id,
			0,
			'automatic_' . $algorithm,
			$reason,
			0 // system assigned
		);

		// Update agent's last_assigned_at timestamp
		global $wpdb;
		$table_agents = $wpdb->prefix . 'dctc_support_agents';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table_agents,
			array( 'last_assigned_at' => current_time( 'mysql' ) ),
			array( 'id' => $assigned_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $assigned_id;
	}

	/**
	 * Handle fallback agent routing when no standard candidates qualify.
	 *
	 * @param int    $ticket_id          Ticket ID.
	 * @param int    $fallback_agent_id  Configured fallback agent ID.
	 * @param string $reason             Reason for fallback.
	 * @return int|false
	 */
	private static function handle_fallback( $ticket_id, $fallback_agent_id, $reason ) {
		if ( $fallback_agent_id ) {
			DCTC_Support_Ticket_Service::assign_ticket(
				$ticket_id,
				$fallback_agent_id,
				0,
				'fallback',
				'Fallback assignment: ' . $reason,
				0
			);
			return $fallback_agent_id;
		}

		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			'assignment_unassigned_queue',
			'system',
			0,
			'System',
			null,
			null,
			array( 'reason' => $reason )
		);

		return false;
	}
}
