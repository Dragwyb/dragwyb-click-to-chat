<?php
/**
 * DCTC AI Leads Controller
 *
 * Owns structured lead capture from AI conversations, email alerts,
 * outbound webhook dispatch, and admin lead management REST endpoints.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once DCTC_PLUGIN_DIR . 'includes/ai/trait-dctc-ai-rest-helpers.php';

class DCTC_AI_Leads_Controller {

	use DCTC_AI_REST_Helpers;

	/**
	 * Public REST permission check for capturing leads.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_check_capture( $request ) {
		if ( current_user_can( 'read' ) ) {
			return true;
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Admin REST permission check.
	 *
	 * @return bool
	 */
	public function permission_check_admin() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * REST callback: Capture new lead from chat.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function capture_lead( $request ) {
		// Simple IP rate-limiting for lead submissions (max 10 submissions per 10 minutes)
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! empty( $ip ) ) {
			$rl_key = 'dctc_ai_lead_rl_' . md5( $ip );
			$count  = (int) get_transient( $rl_key );
			if ( $count >= 10 ) {
				return $this->error_response( __( 'Too many submissions. Please wait a few minutes before trying again.', 'dragwyb-click-to-chat' ), 429 );
			}
			set_transient( $rl_key, $count + 1, 600 );
		}

		$params = $request->get_json_params();

		$name         = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$email        = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
		$phone        = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
		$company      = isset( $params['company'] ) ? sanitize_text_field( $params['company'] ) : '';
		$company_size = isset( $params['company_size'] ) ? sanitize_text_field( $params['company_size'] ) : '';
		$budget       = isset( $params['budget'] ) ? sanitize_text_field( $params['budget'] ) : '';
		$timeline     = isset( $params['timeline'] ) ? sanitize_text_field( $params['timeline'] ) : '';
		$interest     = isset( $params['interest'] ) ? sanitize_text_field( $params['interest'] ) : '';
		$requirement  = isset( $params['requirement'] ) ? sanitize_textarea_field( $params['requirement'] ) : '';
		$source_url   = isset( $params['source_url'] ) ? esc_url_raw( $params['source_url'] ) : '';
		$session_id   = isset( $params['session_id'] ) ? sanitize_text_field( $params['session_id'] ) : '';
		$consent      = isset( $params['consent'] ) ? (bool) $params['consent'] : true;

		if ( empty( $name ) && empty( $email ) && empty( $phone ) ) {
			return $this->error_response( __( 'Please provide at least a name, email address, or phone number.', 'dragwyb-click-to-chat' ), 400 );
		}

		if ( ! empty( $email ) && ! is_email( $email ) ) {
			return $this->error_response( __( 'Please provide a valid email address.', 'dragwyb-click-to-chat' ), 400 );
		}

		// Calculate Multi-Factor Explainable Lead Qualification & Scoring
		$scoring = $this->calculate_lead_score(
			array(
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'company'      => $company,
				'company_size' => $company_size,
				'budget'       => $budget,
				'timeline'     => $timeline,
				'interest'     => $interest,
				'requirement'  => $requirement,
			)
		);

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot      = isset( $settings['chatbot'] ) ? $settings['chatbot'] : array();

		$threshold             = isset( $bot['lead_qualification_threshold'] ) ? intval( $bot['lead_qualification_threshold'] ) : 70;
		$enable_support_ticket = false;
		if ( class_exists( 'DCTC_Helper' ) && method_exists( 'DCTC_Helper', 'is_support_enabled' ) && DCTC_Helper::is_support_enabled() ) {
			$enable_support_ticket = isset( $bot['enable_support_escalation'] ) ? (bool) $bot['enable_support_escalation'] : false;
		}

		$initial_status = ( $scoring['score'] >= $threshold ) ? 'qualified' : 'new';

		$lead_data = array(
			'session_id'      => $session_id,
			'name'            => $name,
			'email'           => $email,
			'phone'           => $phone,
			'company'         => $company,
			'company_size'    => $company_size,
			'budget'          => $budget,
			'timeline'        => $timeline,
			'interest'        => $interest,
			'requirement'     => $requirement,
			'source_url'      => $source_url,
			'score'           => $scoring['score'],
			'intent_level'    => $scoring['intent_level'],
			'score_breakdown' => $scoring['breakdown'],
			'status'          => $initial_status,
			'consent'         => $consent ? 1 : 0,
		);

		$lead_id = DCTC_AI_DB::save_lead( $lead_data );

		if ( ! $lead_id ) {
			return $this->error_response( __( 'Failed to save lead. Please try again.', 'dragwyb-click-to-chat' ), 500 );
		}

		$lead_data['id'] = $lead_id;

		// 1. Link lead to session and persist conversation in wp_dctc_ai_sessions table
		$session_messages = array();
		if ( ! empty( $params['messages'] ) && is_array( $params['messages'] ) ) {
			foreach ( $params['messages'] as $m ) {
				if ( ! is_array( $m ) || empty( $m['content'] ) ) {
					continue;
				}
				$r                  = isset( $m['role'] ) && in_array( $m['role'], array( 'bot', 'assistant' ), true ) ? 'assistant' : 'user';
				$session_messages[] = array(
					'role'        => $r,
					'sender_type' => isset( $m['sender_type'] ) ? sanitize_key( $m['sender_type'] ) : ( 'assistant' === $r ? 'ai_agent' : 'customer' ),
					'sender_name' => isset( $m['sender_name'] ) ? sanitize_text_field( $m['sender_name'] ) : ( 'assistant' === $r ? 'AI Assistant' : 'Customer' ),
					'content'     => wp_kses_post( is_string( $m['content'] ) ? $m['content'] : wp_json_encode( $m['content'] ) ),
					'created_at'  => isset( $m['created_at'] ) ? sanitize_text_field( $m['created_at'] ) : current_time( 'mysql', true ),
				);
			}
		}

		if ( ! empty( $session_id ) ) {
			global $wpdb;
			$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$existing_session = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, content FROM `{$table_sessions}` WHERE session_id = %s LIMIT 1", $session_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			if ( $existing_session ) {
				$update_data = array(
					'updated_at' => current_time( 'mysql', true ),
				);
				if ( ! empty( $email ) ) {
					$update_data['email'] = $email;
				}
				if ( ! empty( $session_messages ) && ( empty( $existing_session['content'] ) || '[]' === trim( (string) $existing_session['content'] ) ) ) {
					$update_data['content'] = wp_json_encode( $session_messages );
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_sessions,
					$update_data,
					array( 'session_id' => $session_id )
				);
			} else {
				// Save conversation to dctc_ai_sessions when save_chat was disabled
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert(
					$table_sessions,
					array(
						'session_id' => $session_id,
						'email'      => ! empty( $email ) ? $email : '',
						'model'      => 'lead_captured',
						'provider'   => 'lead_form',
						'content'    => ! empty( $session_messages ) ? wp_json_encode( $session_messages ) : wp_json_encode( array() ),
						'intent_tag' => 'lead_generation',
						'status'     => 'active',
						'created_at' => current_time( 'mysql', true ),
						'updated_at' => current_time( 'mysql', true ),
					)
				);
			}
		}

		// 2. Connect with Support Center if Support Center is enabled
		if ( $enable_support_ticket && class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			$support_settings = get_option( 'dctc_support_settings', array() );
			if ( ! empty( $support_settings['enabled'] ) ) {
				$summary_parts = array();
				if ( ! empty( $name ) ) {
					$summary_parts[] = 'Name: ' . $name;
				}
				if ( ! empty( $email ) ) {
					$summary_parts[] = 'Email: ' . $email;
				}
				if ( ! empty( $phone ) ) {
					$summary_parts[] = 'Phone: ' . $phone;
				}
				if ( ! empty( $company ) ) {
					$summary_parts[] = 'Company: ' . $company;
				}
				if ( ! empty( $company_size ) ) {
					$summary_parts[] = 'Company Size: ' . $company_size;
				}
				if ( ! empty( $budget ) ) {
					$summary_parts[] = 'Budget: ' . $budget;
				}
				if ( ! empty( $timeline ) ) {
					$summary_parts[] = 'Timeline: ' . $timeline;
				}
				if ( ! empty( $interest ) ) {
					$summary_parts[] = 'Interest: ' . $interest;
				}
				if ( ! empty( $requirement ) ) {
					$summary_parts[] = 'Requirement: ' . $requirement;
				}

				$lead_summary = implode( "\n", $summary_parts );

				$existing_ticket = ! empty( $session_id ) ? DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id ) : null;

				if ( $existing_ticket && ! empty( $existing_ticket['id'] ) ) {
					// UPDATE EXISTING TICKET for same session
					$ticket_id  = (int) $existing_ticket['id'];
					$ticket_obj = class_exists( 'DCTC_Support_Ticket' ) ? new DCTC_Support_Ticket( $ticket_id ) : null;

					if ( $ticket_obj && $ticket_obj->is_valid() ) {
						if ( ! empty( $email ) ) {
							$ticket_obj->update_email( $email );
						}
						if ( ! empty( $phone ) ) {
							$ticket_obj->update_phone( $phone );
						}
						if ( ! empty( $name ) ) {
							$ticket_obj->update_name( $name );
						}

						$ticket_obj->update_meta( 'lead_id', $lead_id, 'auto' );
						$ticket_obj->update_meta( 'lead_score', $scoring['score'], 'auto' );
						$ticket_obj->update_meta( 'intent_level', $scoring['intent_level'], 'auto' );
						if ( ! empty( $company ) ) {
							$ticket_obj->update_meta( 'company', $company, 'auto' );
						}
						if ( ! empty( $company_size ) ) {
							$ticket_obj->update_meta( 'company_size', $company_size, 'auto' );
						}
						if ( ! empty( $budget ) ) {
							$ticket_obj->update_meta( 'budget', $budget, 'auto' );
						}
						if ( ! empty( $timeline ) ) {
							$ticket_obj->update_meta( 'timeline', $timeline, 'auto' );
						}
						if ( ! empty( $interest ) ) {
							$ticket_obj->update_meta( 'interest', $interest, 'auto' );
							$ticket_obj->update_meta( 'product', $interest, 'auto' );
							if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
								DCTC_Support_Ticket_Service::sync_ticket_product_and_reassign( $ticket_id, $interest );
							}
						}
						if ( ! empty( $requirement ) ) {
							$ticket_obj->update_meta( 'requirement', $requirement, 'textarea' );
						}
						if ( ! empty( $source_url ) ) {
							$ticket_obj->update_meta( 'source_url', $source_url, 'url' );
						}
						$ticket_obj->update_meta( 'interaction_type', 'LEAD_GENERATION', 'auto' );

						// Update subject if generic
						if ( ! empty( $name ) && ( strpos( $existing_ticket['subject'], 'Support Request' ) !== false || strpos( $existing_ticket['subject'], 'Guest' ) !== false || empty( $existing_ticket['subject'] ) ) ) {
							DCTC_Support_Ticket_Service::update_ticket_properties( $ticket_id, array( 'subject' => '[Lead] ' . $name ) );
						}
					} else {
						// Fallback via DCTC_Support_Ticket_Service
						if ( ! empty( $email ) ) {
							global $wpdb;
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->update(
								$wpdb->prefix . 'dctc_support_tickets',
								array(
									'customer_email' => $email,
									'updated_at'     => current_time( 'mysql' ),
								),
								array( 'id' => $ticket_id )
							);
						}
						if ( ! empty( $phone ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'customer_phone', $phone );
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'phone', $phone );
						}
						if ( ! empty( $name ) ) {
							global $wpdb;
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->update( $wpdb->prefix . 'dctc_support_tickets', array( 'customer_name' => $name ), array( 'id' => $ticket_id ) );
						}
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'lead_id', $lead_id );
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'lead_score', $scoring['score'] );
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'intent_level', $scoring['intent_level'] );
						if ( ! empty( $company ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'company', $company );
						}
						if ( ! empty( $budget ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'budget', $budget );
						}
						if ( ! empty( $timeline ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'timeline', $timeline );
						}
						if ( ! empty( $interest ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'interest', $interest );
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'product', $interest );
							DCTC_Support_Ticket_Service::sync_ticket_product_and_reassign( $ticket_id, $interest );
						}
						if ( ! empty( $requirement ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'requirement', $requirement );
						}
					}

					// Add lead generation intent to existing intent types
					$existing_intent_types = DCTC_Support_Ticket_Service::get_ticket_meta( $ticket_id, 'intent_types', true );
					if ( ! empty( $existing_intent_types ) ) {
						$existint_intent_types = json_decode( $existing_intent_types, true );
						if ( is_array( $existint_intent_types ) ) {
							if ( ! in_array( 'lead_generation', $existint_intent_types, true ) ) {
								$existint_intent_types[] = 'lead_generation';
								DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'intent_types', $existint_intent_types );
							}
						}
					}

					// Log Event in support ticket activity
					if ( class_exists( 'DCTC_Support_Event_Service' ) ) {
						DCTC_Support_Event_Service::log_event(
							$ticket_id,
							'lead_captured',
							'Inquiry received',
							'Lead details submitted: ' . ( ! empty( $email ) ? $email : $name ),
							'customer',
							0,
							! empty( $name ) ? $name : 'Visitor'
						);
					}

					// Add internal note with lead details
					if ( class_exists( 'DCTC_Support_Note_Service' ) && ! empty( $lead_summary ) ) {
						DCTC_Support_Note_Service::add_note(
							$ticket_id,
							"📋 AI Lead Capture Form Submitted:\n" . $lead_summary . "\nScore: " . $scoring['score'] . '/100 (' . strtoupper( $scoring['intent_level'] ) . ')',
							0,
							false
						);
					}
				} else {
					// CREATE NEW TICKET if no ticket existed for this session
					$created = DCTC_Support_Ticket_Service::create_ticket(
						array(
							'subject'          => '[Lead] ' . ( ! empty( $name ) ? $name : ( ! empty( $email ) ? $email : 'Website Lead Inquiry' ) ),
							'session_id'       => $session_id,
							'customer_email'   => $email,
							'customer_name'    => $name,
							'origin_type'      => 'chatbot',
							'reply_surface'    => 'chatbot_widget',
							'interaction_type' => 'LEAD_GENERATION',
							'control_mode'     => 'ai',
							'initial_message'  => $lead_summary,
							'intent_types'     => 'lead_generation',
						)
					);

					if ( is_array( $created ) && ! empty( $created['id'] ) ) {
						$new_ticket_id = (int) $created['id'];
						if ( ! empty( $phone ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'customer_phone', $phone );
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'phone', $phone );
						}
						DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'lead_id', $lead_id );
						DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'lead_score', $scoring['score'] );
						DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'intent_level', $scoring['intent_level'] );
						if ( ! empty( $company ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'company', $company );
						}
						if ( ! empty( $company_size ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'company_size', $company_size );
						}
						if ( ! empty( $budget ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'budget', $budget );
						}
						if ( ! empty( $timeline ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'timeline', $timeline );
						}
						if ( ! empty( $interest ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'interest', $interest );
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'product', $interest );
							DCTC_Support_Ticket_Service::sync_ticket_product_and_reassign( $new_ticket_id, $interest );
						}
						if ( ! empty( $requirement ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'requirement', $requirement );
						}
						if ( ! empty( $source_url ) ) {
							DCTC_Support_Ticket_Service::update_ticket_meta( $new_ticket_id, 'source_url', $source_url );
						}
					}
				}
			}
		}

		// 3. Save confirmation response message in AI session and ticket conversation history
		$confirmation_message = __( 'Thank you! Your information has been received. Our team will contact you shortly.', 'dragwyb-click-to-chat' );
		if ( ! empty( $session_id ) ) {
			$time_now      = current_time( 'mysql' );
			$confirm_entry = array(
				'role'        => 'assistant',
				'sender_type' => 'ai_agent',
				'sender_name' => 'AI Assistant',
				'content'     => $confirmation_message,
				'created_at'  => $time_now,
			);

			// 3a. Update session table
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$session_row = $wpdb->get_row( $wpdb->prepare( "SELECT content FROM `$table_sessions` WHERE session_id = %s", $session_id ), ARRAY_A );
			if ( $session_row ) {
				$current_msgs = ! empty( $session_row['content'] ) ? json_decode( $session_row['content'], true ) : array();
				$current_msgs = is_array( $current_msgs ) ? $current_msgs : array();
				foreach ( $current_msgs as &$msg ) {
					if ( is_array( $msg ) ) {
						if ( isset( $msg['show_form'] ) && is_array( $msg['show_form'] ) ) {
							$msg['show_form']['show']        = false;
							$msg['show_form']['form_filled'] = true;
						} elseif ( ! empty( $msg['form_type'] ) || ( isset( $msg['content'] ) && false !== stripos( (string) $msg['content'], 'form below' ) ) ) {
							$msg['show_form'] = array(
								'form_type'   => 'lead_generate',
								'show'        => false,
								'form_filled' => true,
							);
						}
					}
				}
				unset( $msg );
				$current_msgs[] = $confirm_entry;
				$current_msgs   = array_slice( $current_msgs, -50 );
				$encoded_msgs   = wp_json_encode( $current_msgs );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_sessions,
					array(
						'content'    => $encoded_msgs,
						'updated_at' => $time_now,
					),
					array( 'session_id' => $session_id )
				);

				// 3b. Update linked ticket messages if ticket exists
				$target_ticket_id = ! empty( $ticket_id ) ? $ticket_id : ( ! empty( $new_ticket_id ) ? $new_ticket_id : 0 );
				if ( ! $target_ticket_id && class_exists( 'DCTC_Support_Ticket_Service' ) ) {
					$linked = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
					if ( $linked && ! empty( $linked['id'] ) ) {
						$target_ticket_id = (int) $linked['id'];
					}
				}

				if ( $target_ticket_id && class_exists( 'DCTC_Support_Ticket_Service' ) ) {
					DCTC_Support_Ticket_Service::update_ticket_meta( $target_ticket_id, '_dctc_ticket_messages', json_decode( $encoded_msgs ) );
				}
			}
		}

		// 4. Dispatch Email Notification
		if ( class_exists( 'DCTC_Support_Notification_Service' ) ) {
			DCTC_Support_Notification_Service::maybe_send_lead_email( $lead_data );
		}

		// 5. Dispatch Webhook
		$this->maybe_dispatch_webhook( $lead_data );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => $confirmation_message,
				'lead_id' => $lead_id,
				'score'   => $scoring['score'],
				'status'  => $initial_status,
			),
			200
		);
	}

	/**
	 * Compute explainable multi-factor score and intent level for a lead.
	 *
	 * @param array $params
	 * @return array{score: int, intent_level: string, breakdown: array}
	 */
	public function calculate_lead_score( array $params ) {
		$breakdown = array();
		$total     = 0;

		// 1. Contact Information Completeness (Max 55)
		$contact_pts = 0;
		if ( ! empty( $params['name'] ) ) {
			$contact_pts += 15;
		}
		if ( ! empty( $params['email'] ) && is_email( $params['email'] ) ) {
			$contact_pts += 20;
		}
		if ( ! empty( $params['phone'] ) && strlen( $params['phone'] ) >= 7 ) {
			$contact_pts += 20;
		}

		$breakdown[] = array(
			'factor' => __( 'Contact Information', 'dragwyb-click-to-chat' ),
			'points' => $contact_pts,
			'max'    => 55,
			/* translators: %d: Contact points */
			'detail' => sprintf( __( 'Name (+15), Email (+20), Phone (+20) -> %d pts', 'dragwyb-click-to-chat' ), $contact_pts ),
		);
		$total      += $contact_pts;

		// 2. Budget Range Signals (Max 20)
		$budget_pts = 0;
		$budget     = strtolower( $params['budget'] ?? '' );
		if ( ! empty( $budget ) ) {
			if ( strpos( $budget, '20k' ) !== false || strpos( $budget, 'enterprise' ) !== false || strpos( $budget, '10,000' ) !== false || strpos( $budget, 'high' ) !== false ) {
				$budget_pts = 20;
			} elseif ( strpos( $budget, '5k' ) !== false || strpos( $budget, '1k' ) !== false || strpos( $budget, 'medium' ) !== false ) {
				$budget_pts = 15;
			} else {
				$budget_pts = 10;
			}
		}
		if ( $budget_pts > 0 ) {
			$breakdown[] = array(
				'factor' => __( 'Budget Specified', 'dragwyb-click-to-chat' ),
				'points' => $budget_pts,
				'max'    => 20,
				/* translators: 1: Budget range, 2: Budget points */
				'detail' => sprintf( __( 'Budget range "%1$s" -> +%2$d pts', 'dragwyb-click-to-chat' ), esc_html( $params['budget'] ), $budget_pts ),
			);
			$total      += $budget_pts;
		}

		// 3. Purchasing Timeline / Urgency (Max 15)
		$timeline_pts = 0;
		$timeline     = strtolower( $params['timeline'] ?? '' );
		if ( ! empty( $timeline ) ) {
			if ( strpos( $timeline, 'immediate' ) !== false || strpos( $timeline, 'asap' ) !== false || strpos( $timeline, 'urgent' ) !== false || strpos( $timeline, 'week' ) !== false ) {
				$timeline_pts = 15;
			} elseif ( strpos( $timeline, 'month' ) !== false || strpos( $timeline, 'quarter' ) !== false ) {
				$timeline_pts = 10;
			} else {
				$timeline_pts = 5;
			}
		}
		if ( $timeline_pts > 0 ) {
			$breakdown[] = array(
				'factor' => __( 'Purchase Timeline', 'dragwyb-click-to-chat' ),
				'points' => $timeline_pts,
				'max'    => 15,
				/* translators: 1: Timeline description, 2: Timeline points */
				'detail' => sprintf( __( 'Timeline "%1$s" -> +%2$d pts', 'dragwyb-click-to-chat' ), esc_html( $params['timeline'] ), $timeline_pts ),
			);
			$total      += $timeline_pts;
		}

		// 4. Product / Service Specificity & Company Profile (Max 20)
		$profile_pts = 0;
		if ( ! empty( $params['company'] ) ) {
			$profile_pts += 5;
		}
		if ( ! empty( $params['company_size'] ) ) {
			$profile_pts += 5;
		}
		if ( ! empty( $params['interest'] ) ) {
			$profile_pts += 10;
		}

		if ( $profile_pts > 0 ) {
			$breakdown[] = array(
				'factor' => __( 'Business & Interest Specificity', 'dragwyb-click-to-chat' ),
				'points' => min( 20, $profile_pts ),
				'max'    => 20,
				/* translators: %d: Specificity points */
				'detail' => sprintf( __( 'Company, size & product interest specified -> +%d pts', 'dragwyb-click-to-chat' ), min( 20, $profile_pts ) ),
			);
			$total      += min( 20, $profile_pts );
		}

		// 5. Requirement & AI Intent signals (Max 15)
		$intent_level     = 'medium';
		$requirement_text = strtolower( $params['requirement'] ?? '' );
		$intent_pts       = 0;

		$high_intent_words = array( 'quote', 'pricing', 'buy', 'purchase', 'cost', 'demo', 'hire', 'contract', 'order', 'call me', 'proposal', 'deal', 'urgent', 'asap' );
		$matched           = array();
		foreach ( $high_intent_words as $w ) {
			if ( strpos( $requirement_text, $w ) !== false ) {
				$matched[] = $w;
			}
		}

		if ( count( $matched ) >= 2 || strpos( $requirement_text, 'urgent' ) !== false || strpos( $requirement_text, 'asap' ) !== false ) {
			$intent_level = 'urgent';
			$intent_pts   = 15;
		} elseif ( count( $matched ) >= 1 ) {
			$intent_level = 'high';
			$intent_pts   = 10;
		} elseif ( strlen( $requirement_text ) >= 20 ) {
			$intent_level = 'medium';
			$intent_pts   = 5;
		} else {
			$intent_level = 'low';
		}

		if ( $intent_pts > 0 ) {
			$breakdown[] = array(
				'factor' => __( 'AI Buying Intent & Urgency', 'dragwyb-click-to-chat' ),
				'points' => $intent_pts,
				'max'    => 15,
				/* translators: 1: Intent level, 2: Matched signals, 3: Intent points */
				'detail' => sprintf( __( 'Intent level: %1$s (signals: %2$s) -> +%3$d pts', 'dragwyb-click-to-chat' ), ucfirst( $intent_level ), ! empty( $matched ) ? implode( ', ', array_slice( $matched, 0, 3 ) ) : __( 'detailed requirement', 'dragwyb-click-to-chat' ), $intent_pts ),
			);
			$total      += $intent_pts;
		}

		return array(
			'score'        => min( 100, $total ),
			'intent_level' => $intent_level,
			'breakdown'    => $breakdown,
		);
	}

	/**
	 * REST callback: Get all leads for admin dashboard.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_leads( $request ) {
		$limit  = max( 1, min( 200, intval( $request->get_param( 'limit' ) ?: 50 ) ) );
		$page   = max( 1, intval( $request->get_param( 'page' ) ?: 1 ) );
		$offset = ( $page - 1 ) * $limit;
		$status = sanitize_text_field( $request->get_param( 'status' ) ?: 'all' );
		$search = sanitize_text_field( $request->get_param( 'search' ) ?: '' );

		$leads = DCTC_AI_DB::get_leads( $limit, $offset, $status, $search );
		$total = DCTC_AI_DB::get_leads_count( $status, $search );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'leads'   => $leads,
				'total'   => $total,
				'page'    => $page,
				'limit'   => $limit,
				'pages'   => ceil( $total / $limit ),
			),
			200
		);
	}

	/**
	 * REST callback: Get single lead details with full conversation transcript.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_lead( $request ) {
		$id   = absint( $request->get_param( 'id' ) );
		$lead = DCTC_AI_DB::get_lead( $id );

		if ( ! $lead ) {
			return $this->error_response( __( 'Lead not found.', 'dragwyb-click-to-chat' ), 404 );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'lead'    => $lead,
			),
			200
		);
	}

	/**
	 * REST callback: Update status of a lead.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function update_status( $request ) {
		$id     = absint( $request->get_param( 'id' ) );
		$params = $request->get_json_params();
		$status = isset( $params['status'] ) ? sanitize_key( $params['status'] ) : 'new';

		$updated = DCTC_AI_DB::update_lead_status( $id, $status );
		if ( $updated ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Lead status updated.', 'dragwyb-click-to-chat' ),
				),
				200
			);
		}

		return $this->error_response( __( 'Failed to update lead status.', 'dragwyb-click-to-chat' ), 400 );
	}

	/**
	 * REST callback: Delete a lead.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function delete_lead( $request ) {
		$id = absint( $request->get_param( 'id' ) );
		if ( DCTC_AI_DB::delete_lead( $id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Lead deleted.', 'dragwyb-click-to-chat' ),
				),
				200
			);
		}
		return $this->error_response( __( 'Lead not found or could not be deleted.', 'dragwyb-click-to-chat' ), 404 );
	}

	/**
	 * REST callback: Export leads as CSV.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function export_csv( $request ) {
		$leads = DCTC_AI_DB::get_leads( 1000, 0, 'all', '' );

		$csv_rows   = array();
		$csv_rows[] = array( 'ID', 'Name', 'Email', 'Phone', 'Company', 'Company Size', 'Budget', 'Timeline', 'Interest', 'Requirement', 'Lead Score', 'Intent Level', 'Status', 'Session ID', 'Created At' );

		foreach ( $leads as $l ) {
			$csv_rows[] = array(
				$l['id'],
				$l['name'],
				$l['email'],
				$l['phone'],
				$l['company'],
				$l['company_size'] ?? '',
				$l['budget'] ?? '',
				$l['timeline'] ?? '',
				$l['interest'] ?? '',
				str_replace( array( "\r", "\n" ), ' ', $l['requirement'] ),
				$l['score'],
				$l['intent_level'] ?? 'medium',
				$l['status'],
				$l['session_id'],
				$l['created_at'],
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://temp', 'r+' );
		foreach ( $csv_rows as $row ) {
			fputcsv( $output, $row );
		}
		rewind( $output );
		$csv_content = stream_get_contents( $output );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'csv'      => $csv_content,
				'filename' => 'leads-export-' . gmdate( 'Y-m-d-His' ) . '.csv',
			),
			200
		);
	}

	/**
	 * Dispatch outbound Webhook payload (Zapier, Make, CRM).
	 *
	 * @param array $lead
	 * @return void
	 */
	public function maybe_dispatch_webhook( array $lead ) {
		$settings    = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$webhook_url = ! empty( $settings['chatbot']['lead_webhook_url'] ) ? esc_url_raw( $settings['chatbot']['lead_webhook_url'] ) : '';

		if ( empty( $webhook_url ) || ! wp_http_validate_url( $webhook_url ) ) {
			return;
		}

		// Security (SEC-012): Block loopback and private internal network addresses (SSRF prevention)
		$parsed_host = wp_parse_url( $webhook_url, PHP_URL_HOST );
		if ( ! empty( $parsed_host ) ) {
			$resolved_ip = filter_var( $parsed_host, FILTER_VALIDATE_IP ) ? $parsed_host : gethostbyname( $parsed_host );
			if ( $resolved_ip && ! filter_var( $resolved_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return;
			}
		}

		$payload = array(
			'event'     => 'ai_lead_captured',
			'site_url'  => home_url(),
			'site_name' => get_bloginfo( 'name' ),
			'timestamp' => current_time( 'c' ),
			'lead'      => $lead,
		);

		wp_remote_post(
			$webhook_url,
			array(
				'headers'   => array( 'Content-Type' => 'application/json' ),
				'body'      => wp_json_encode( $payload ),
				'timeout'   => 10,
				'blocking'  => false, // Non-blocking asynchronous dispatch
				// Security (SEC-006): Enforce SSL certificate verification for outbound webhooks
				'sslverify' => apply_filters( 'dctc_webhook_ssl_verify', true ),
			)
		);
	}
}
