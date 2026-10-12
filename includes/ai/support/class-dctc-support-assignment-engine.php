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

		$algorithm            = ! empty( $settings['assignment_algorithm'] ) ? $settings['assignment_algorithm'] : 'skill_match';
		$respect_availability = ! isset( $settings['respect_availability'] ) || (bool) $settings['respect_availability'];
		$respect_workload     = ! isset( $settings['respect_workload'] ) || (bool) $settings['respect_workload'];
		$fallback_agent_id    = ! empty( $settings['fallback_agent_id'] ) ? absint( $settings['fallback_agent_id'] ) : 0;

		global $wpdb;
		$table_agents = $wpdb->prefix . 'dctc_support_agents';

		// 1. Fetch All Active Candidates
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

		// 2. CHECK IF TICKET IS A LEAD GENERATION TICKET
		// Lead generated tickets ALWAYS assign to Administrator or Support Manager
		$is_lead_ticket = false;
		if ( ! empty( $ticket['interaction_type'] ) && 'LEAD_GENERATION' === strtoupper( $ticket['interaction_type'] ) ) {
			$is_lead_ticket = true;
		} elseif ( ! empty( $ticket['intent_types'] ) && ( false !== strpos( (string) $ticket['intent_types'], 'lead_generation' ) ) ) {
			$is_lead_ticket = true;
		} elseif ( ! empty( $ticket['lead_id'] ) && (int) $ticket['lead_id'] > 0 ) {
			$is_lead_ticket = true;
		} elseif ( ! empty( $ticket['subject'] ) && 0 === strpos( $ticket['subject'], '[Lead]' ) ) {
			$is_lead_ticket = true;
		} else {
			$lead_meta = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, 'lead_id', true );
			if ( ! empty( $lead_meta ) && (int) $lead_meta > 0 ) {
				$is_lead_ticket = true;
			}
		}

		if ( $is_lead_ticket ) {
			$lead_managers = array();
			foreach ( $all_agents as $agent ) {
				$is_admin_or_mgr = in_array( $agent['support_role'], array( 'admin', 'manager' ), true )
					|| in_array( $agent['seniority'], array( 'manager' ), true )
					|| user_can( $agent['wp_user_id'], 'manage_options' );

				if ( $is_admin_or_mgr ) {
					if ( $respect_workload && $agent['current_active_tickets'] >= $agent['max_active_tickets'] ) {
						continue;
					}
					$lead_managers[] = $agent;
				}
			}

			// If all managers are at capacity, ignore workload limit for leads to ensure an admin/manager handles it
			if ( empty( $lead_managers ) ) {
				foreach ( $all_agents as $agent ) {
					if ( in_array( $agent['support_role'], array( 'admin', 'manager' ), true ) || user_can( $agent['wp_user_id'], 'manage_options' ) ) {
						$lead_managers[] = $agent;
					}
				}
			}

			if ( ! empty( $lead_managers ) ) {
				usort(
					$lead_managers,
					function ( $a, $b ) {
						if ( (int) $a['current_active_tickets'] === (int) $b['current_active_tickets'] ) {
							$time_a = ! empty( $a['last_assigned_at'] ) ? strtotime( $a['last_assigned_at'] ) : 0;
							$time_b = ! empty( $b['last_assigned_at'] ) ? strtotime( $b['last_assigned_at'] ) : 0;
							return $time_a <=> $time_b;
						}
						return (int) $a['current_active_tickets'] <=> (int) $b['current_active_tickets'];
					}
				);

				$selected_lead_agent = $lead_managers[0];
				$assigned_id         = (int) $selected_lead_agent['id'];

				DCTC_Support_Ticket_Service::assign_ticket(
					$ticket_id,
					$assigned_id,
					0,
					'lead_auto_routing',
					'Auto-assigned to Administrator / Support Manager for Lead follow-up',
					0
				);

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
		}

		// 3. GATHER TICKET CATEGORY & SUBTAXONOMY TERMS
		$ticket_cat_id = ! empty( $ticket['category_id'] ) ? absint( $ticket['category_id'] ) : 0;

		$ticket_terms_by_tax = array();
		$ticket_term_ids     = array();
		$ticket_term_slugs   = array();

		if ( class_exists( 'DCTC_Support_Taxonomy_Service' ) ) {
			$terms = DCTC_Support_Taxonomy_Service::get_object_terms( $ticket_id, '', 'ticket' );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term_row ) {
					$tax_slug = ! empty( $term_row['taxonomy_slug'] ) ? $term_row['taxonomy_slug'] : 'tag';
					$term_id  = absint( $term_row['id'] );
					$t_slug   = ! empty( $term_row['slug'] ) ? $term_row['slug'] : '';

					if ( ! isset( $ticket_terms_by_tax[ $tax_slug ] ) ) {
						$ticket_terms_by_tax[ $tax_slug ] = array();
					}
					$ticket_terms_by_tax[ $tax_slug ][] = $term_id;
					if ( $t_slug ) {
						$ticket_terms_by_tax[ $tax_slug ][] = $t_slug;
					}
					$ticket_term_ids[]   = $term_id;
					$ticket_term_slugs[] = $t_slug;
				}
			}
		}

		// Also include tags from ticket record if present
		if ( ! empty( $ticket['tags'] ) && is_array( $ticket['tags'] ) ) {
			foreach ( $ticket['tags'] as $tag_item ) {
				$t_id   = is_array( $tag_item ) && ! empty( $tag_item['id'] ) ? absint( $tag_item['id'] ) : ( is_numeric( $tag_item ) ? absint( $tag_item ) : 0 );
				$t_slug = is_array( $tag_item ) && ! empty( $tag_item['slug'] ) ? $tag_item['slug'] : ( is_string( $tag_item ) ? sanitize_title( $tag_item ) : '' );
				if ( ! isset( $ticket_terms_by_tax['tag'] ) ) {
					$ticket_terms_by_tax['tag'] = array();
				}
				if ( $t_id ) {
					$ticket_terms_by_tax['tag'][] = $t_id;
					$ticket_term_ids[]           = $t_id;
				}
				if ( $t_slug ) {
					$ticket_terms_by_tax['tag'][] = $t_slug;
					$ticket_term_slugs[]         = $t_slug;
				}
			}
		}

		// Also check product meta if present
		$prod_id = ! empty( $ticket['product_id'] ) ? absint( $ticket['product_id'] ) : (int) DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, 'product_id', true );
		$prod_name = ! empty( $ticket['product'] ) ? $ticket['product'] : (string) DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, 'product', true );
		if ( empty( $prod_name ) ) {
			$prod_name = (string) DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, 'product_name', true );
		}

		if ( $prod_id > 0 || ! empty( $prod_name ) ) {
			if ( ! isset( $ticket_terms_by_tax['product'] ) ) {
				$ticket_terms_by_tax['product'] = array();
			}

			global $wpdb;
			$table_terms = $wpdb->prefix . 'dctc_support_terms';
			$table_meta  = $wpdb->prefix . 'dctc_support_term_meta';
			$prod_row    = null;

			if ( $prod_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$prod_row = $wpdb->get_row( $wpdb->prepare( "SELECT id, slug, name FROM `$table_terms` WHERE taxonomy_slug = 'product' AND id = %d LIMIT 1", $prod_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( ! $prod_row ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
					$prod_row = $wpdb->get_row( $wpdb->prepare( "SELECT t.id, t.slug, t.name FROM `$table_terms` t INNER JOIN `$table_meta` m ON t.id = m.term_id WHERE t.taxonomy_slug = 'product' AND m.meta_key = 'wc_product_id' AND m.meta_value = %s LIMIT 1", (string) $prod_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
				$ticket_terms_by_tax['product'][] = $prod_id;
				$ticket_term_ids[]               = $prod_id;
			}

			if ( ! $prod_row && ! empty( $prod_name ) ) {
				$prod_slug = sanitize_title( $prod_name );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$prod_row = $wpdb->get_row( $wpdb->prepare( "SELECT id, slug, name FROM `$table_terms` WHERE taxonomy_slug = 'product' AND (slug = %s OR name = %s) LIMIT 1", $prod_slug, sanitize_text_field( $prod_name ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$ticket_terms_by_tax['product'][] = $prod_slug;
				$ticket_term_slugs[]             = $prod_slug;
			}

			if ( $prod_row ) {
				$p_id   = absint( $prod_row['id'] );
				$p_slug = $prod_row['slug'];
				if ( ! in_array( $p_id, $ticket_terms_by_tax['product'], true ) ) {
					$ticket_terms_by_tax['product'][] = $p_id;
					$ticket_term_ids[]               = $p_id;
				}
				if ( ! in_array( $p_slug, $ticket_terms_by_tax['product'], true ) ) {
					$ticket_terms_by_tax['product'][] = $p_slug;
					$ticket_term_slugs[]             = $p_slug;
				}

				// If ticket category is missing, derive it from the product's category
				if ( 0 === $ticket_cat_id ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
					$derived_cat = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM `$table_meta` WHERE term_id = %d AND meta_key = 'category_id' LIMIT 1", $p_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					if ( $derived_cat && (int) $derived_cat > 0 ) {
						$ticket_cat_id = absint( $derived_cat );
					}
				}
			}
		}

		// 4. FILTER BY WORKLOAD LIMITS
		$eligible_agents = array();
		foreach ( $all_agents as $agent ) {
			if ( $respect_workload && $agent['current_active_tickets'] >= $agent['max_active_tickets'] ) {
				continue; // Overloaded
			}
			$eligible_agents[] = $agent;
		}

		if ( empty( $eligible_agents ) ) {
			return self::handle_fallback( $ticket_id, $fallback_agent_id, 'All eligible agents are currently at max capacity' );
		}

		// 5. SCORE CANDIDATES BASED ON CATEGORY & SUBTAXONOMIES MATCHING
		$scored_agents = array();
		foreach ( $eligible_agents as $agent ) {
			$score = 100;

			$allowed_cats = ! empty( $agent['allowed_categories'] ) && is_array( $agent['allowed_categories'] )
				? array_map( 'intval', $agent['allowed_categories'] )
				: array();

			$allowed_subtax = ! empty( $agent['allowed_subtaxonomies'] ) && is_array( $agent['allowed_subtaxonomies'] )
				? $agent['allowed_subtaxonomies']
				: array();

			// CATEGORY MATCHING (+100 points if category matches)
			$has_cat_match = false;
			if ( $ticket_cat_id > 0 ) {
				if ( ! empty( $allowed_cats ) ) {
					if ( in_array( $ticket_cat_id, $allowed_cats, true ) ) {
						$score        += 100;
						$has_cat_match = true;
					} else {
						// Authorized for specific categories but NOT this one
						$score -= 150;
					}
				} else {
					// Handles all categories generally
					$score += 30;
				}
			}

			// SUBTAXONOMIES / ITEMS MATCHING (+80 points per matching item)
			$subtax_matches = 0;
			if ( ! empty( $allowed_subtax ) && ! empty( $ticket_terms_by_tax ) ) {
				foreach ( $allowed_subtax as $tax_slug => $agent_tax_items ) {
					if ( empty( $agent_tax_items ) || ! is_array( $agent_tax_items ) ) {
						continue;
					}

					if ( isset( $ticket_terms_by_tax[ $tax_slug ] ) ) {
						$ticket_items_for_tax = $ticket_terms_by_tax[ $tax_slug ];
						foreach ( $agent_tax_items as $allowed_item ) {
							$is_match = in_array( (string) $allowed_item, array_map( 'strval', $ticket_items_for_tax ), true )
								|| ( is_numeric( $allowed_item ) && in_array( (int) $allowed_item, array_map( 'intval', $ticket_term_ids ), true ) );

							if ( $is_match ) {
								$score          += 80;
								$subtax_matches += 1;
							}
						}
					}
				}
			}

			// High synergy bonus: Agent matches BOTH category and subtaxonomies
			if ( $has_cat_match && $subtax_matches > 0 ) {
				$score += 50;
			}

			// Seniority & Urgent Priority (+25 points for senior/manager on urgent tickets)
			if ( 'urgent' === $ticket['priority'] ) {
				if ( in_array( $agent['seniority'], array( 'senior', 'manager' ), true ) ) {
					$score += 25;
				}
			}

			// Workload balancing penalty (-10 points per active ticket)
			$score -= ( (int) $agent['current_active_tickets'] * 10 );

			$agent['calculated_score'] = $score;
			$agent['subtax_matches']   = $subtax_matches;
			$agent['has_cat_match']    = $has_cat_match;
			$scored_agents[]           = $agent;
		}

		// 6. SELECT WINNING AGENT
		usort(
			$scored_agents,
			function ( $a, $b ) {
				if ( $a['calculated_score'] === $b['calculated_score'] ) {
					if ( (int) $a['current_active_tickets'] === (int) $b['current_active_tickets'] ) {
						$time_a = ! empty( $a['last_assigned_at'] ) ? strtotime( $a['last_assigned_at'] ) : 0;
						$time_b = ! empty( $b['last_assigned_at'] ) ? strtotime( $b['last_assigned_at'] ) : 0;
						return $time_a <=> $time_b;
					}
					return (int) $a['current_active_tickets'] <=> (int) $b['current_active_tickets'];
				}
				return $b['calculated_score'] <=> $a['calculated_score'];
			}
		);

		$selected_agent = ! empty( $scored_agents[0] ) ? $scored_agents[0] : null;
		if ( ! $selected_agent ) {
			return self::handle_fallback( $ticket_id, $fallback_agent_id, 'Assignment algorithm yielded no agent' );
		}

		// 7. EXECUTE ASSIGNMENT
		$assigned_id = (int) $selected_agent['id'];
		$match_info  = '';
		if ( ! empty( $selected_agent['has_cat_match'] ) && ! empty( $selected_agent['subtax_matches'] ) ) {
			$match_info = ' (Matched Category & Subtaxonomies)';
		} elseif ( ! empty( $selected_agent['has_cat_match'] ) ) {
			$match_info = ' (Matched Category)';
		}

		$reason = sprintf( 'Auto-assigned via taxonomy routing%s (Score: %d)', $match_info, $selected_agent['calculated_score'] );

		DCTC_Support_Ticket_Service::assign_ticket(
			$ticket_id,
			$assigned_id,
			0,
			'automatic_taxonomy_match',
			$reason,
			0
		);

		// Update agent's last_assigned_at timestamp
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
