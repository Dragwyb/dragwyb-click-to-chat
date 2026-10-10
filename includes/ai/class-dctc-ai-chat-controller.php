<?php
/**
 * DCTC AI Chat Controller
 *
 * Owns the public chat endpoint: permission/rate-limit checks, building the
 * system prompt (chatbot config + RAG context + MCP context + memory), the
 * actual AI provider call, and conversation/session persistence.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_AI_Chat_Controller
 */
class DCTC_AI_Chat_Controller {

	use DCTC_AI_REST_Helpers;

	/**
	 * RAG Controller
	 *
	 * @var DCTC_AI_RAG_Controller
	 */
	private $rag_controller;

	/**
	 * MCP Controller
	 *
	 * @var DCTC_AI_MCP_Controller
	 */
	private $mcp_controller;

	/**
	 * Constructor
	 *
	 * @param DCTC_AI_RAG_Controller $rag_controller Used to retrieve RAG context for prompts.
	 * @param DCTC_AI_MCP_Controller $mcp_controller Used to retrieve MCP context for prompts.
	 */
	public function __construct( DCTC_AI_RAG_Controller $rag_controller, DCTC_AI_MCP_Controller $mcp_controller ) {
		$this->rag_controller = $rag_controller;
		$this->mcp_controller = $mcp_controller;
	}

	/**
	 * Public Chat Permission Check
	 *
	 * Allows access if the user is logged in (capability check) OR
	 * if the visitor provides a valid REST API nonce (proving they are using our frontend).
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool True if authorized.
	 */
	public function permission_check( $request ) {
		// 1. Logged in WordPress users with read capability
		if ( current_user_can( 'read' ) ) {
			return true;
		}

		// 2. Extract standard WordPress REST nonce from header or query param
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		// 3. Verify nonce using standard WordPress REST nonce validation
		if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		// Deny request if nonce verification fails
		return false;
	}

	/**
	 * REST callback: handle a chat message end-to-end.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Formatted answer response.
	 */
	public function handle( $request ) {
		if ( $this->is_rate_limited() ) {
			return $this->error_response(
				esc_html__( 'Too many requests. Please wait a moment and try again.', 'dragwyb-click-to-chat' ),
				429
			);
		}

		$params = $request->get_json_params();

		$prompt           = isset( $params['prompt'] ) ? sanitize_textarea_field( $params['prompt'] ) : '';
		$session_id       = isset( $params['session_id'] ) ? sanitize_text_field( $params['session_id'] ) : 'default';
		$email            = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
		$fallback_trigger = isset( $params['fallback_trigger'] ) && true === $params['fallback_trigger'];

		$attachments = isset( $params['attachments'] ) && is_array( $params['attachments'] ) ? array_values(
			array_filter(
				array_map(
					function ( $att ) {
						if ( ! is_array( $att ) ) {
							return null;
						}
						return array(
							'id'           => isset( $att['id'] ) ? sanitize_text_field( $att['id'] ) : uniqid( 'att_' ),
							'attachmentId' => isset( $att['attachmentId'] ) ? intval( $att['attachmentId'] ) : 0,
							'type'         => ( isset( $att['type'] ) && $att['type'] === 'image' ) ? 'image' : 'file',
							'name'         => isset( $att['name'] ) ? sanitize_file_name( $att['name'] ) : '',
							'size'         => isset( $att['size'] ) ? intval( $att['size'] ) : 0,
							'mime'         => isset( $att['mime'] ) ? sanitize_text_field( $att['mime'] ) : '',
							'url'          => isset( $att['url'] ) ? esc_url_raw( $att['url'] ) : '',
						);
					},
					$params['attachments']
				)
			)
		) : array();

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();

		if ( empty( $settings ) ) {
			return $this->error_response(
				esc_html__( 'Plugin settings not configured.', 'dragwyb-click-to-chat' ),
				500
			);
		}

		$bot    = isset( $settings['chatbot'] ) ? $settings['chatbot'] : array();
		$models = isset( $settings['models'] ) ? $settings['models'] : array();

		// Check AI Usage & Budget Limits
		if ( class_exists( 'DCTC_AI_Usage_Tracker' ) ) {
			$budget_check = DCTC_AI_Usage_Tracker::check_budget_and_limits( $session_id );
			if ( isset( $budget_check['allowed'] ) && ! $budget_check['allowed'] ) {
				return $this->save_and_respond(
					$budget_check['message'],
					$session_id,
					$bot,
					$email,
					$prompt,
					! empty( $budget_check['action_buttons'] ) ? $budget_check['action_buttons'] : array()
				);
			}
		}

		if ( empty( $session_id ) || ! preg_match( '/^[a-zA-Z0-9_\-]{3,100}$/', $session_id ) ) {
			$session_id = 'sess_' . wp_generate_password( 9, false );
		}

		if ( ! headers_sent() ) {
			setcookie( 'dctc_ai_session_id', $session_id, time() + 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE['dctc_ai_session_id'] = $session_id;
		}

		if ( empty( $prompt ) && empty( $attachments ) ) {
			return $this->error_response(
				esc_html__( 'Empty prompt provided.', 'dragwyb-click-to-chat' ),
				400
			);
		}

		if ( empty( $prompt ) && ! empty( $attachments ) ) {
			$prompt = esc_html__( 'Please analyze the attached file(s).', 'dragwyb-click-to-chat' );
		}

		// Hybrid Support Check: Block automatic AI generation if HUMAN_CONTROL is active
		if ( class_exists( 'DCTC_Support_AI_Handoff_Service' ) && DCTC_Support_AI_Handoff_Service::should_block_ai_response( $session_id ) && ! $fallback_trigger ) {
			$human_response = DCTC_Support_AI_Handoff_Service::handle_customer_message_in_human_mode( $session_id, $prompt, $email );
			return new \WP_REST_Response( $human_response, 200 );
		}

		// Intelligent WooCommerce Order Tracker Intent
		if ( ! empty( $prompt ) && class_exists( 'WooCommerce' ) && self::detect_order_tracking_intent( $prompt ) ) {
			$is_logged_in = is_user_logged_in() || ( ! empty( $page_context ) && ! empty( $page_context['is_logged_in'] ) );
			if ( $is_logged_in ) {
				// Check if user provided an order number directly
				$order_id = 0;
				if ( preg_match( '/(?:^|\s|#)(\d{2,8})(?:\s|$|\.)/', $prompt, $matches ) ) {
					$order_id = absint( $matches[1] );
				}

				if ( $order_id > 0 ) {
					$user_id       = get_current_user_id();
					$billing_email = $email ?: ( ! empty( $page_context['user_email'] ) ? $page_context['user_email'] : '' );
					$lookup        = class_exists( 'DCTC_AI_WooCommerce' ) ? DCTC_AI_WooCommerce::lookup_order_status( $order_id, $billing_email, $user_id ) : null;

					if ( $lookup && ! is_wp_error( $lookup ) && ! empty( $lookup['success'] ) ) {
						$msg = sprintf(
						/* translators: 1: Order number, 2: Status label, 3: Order Total, 4: Date */
							esc_html__( 'Here are the live details for Order #%1$s: Status is %2$s. Total: %3$s placed on %4$s.', 'dragwyb-click-to-chat' ),
							$lookup['order_number'],
							$lookup['status_label'],
							$lookup['formatted_total'],
							$lookup['date_created']
						);
						return new \WP_REST_Response(
							array(
								'success'            => true,
								'message'            => $msg,
								'show_order_tracker' => true,
								'order_lookup_data'  => $lookup,
								'session_id'         => $session_id,
							),
							200
						);
					}
				}

				$tracker_msg = ! empty( $bot['order_tracking_prompt_msg'] )
				? $bot['order_tracking_prompt_msg']
				: esc_html__( 'Please enter your Order ID and billing email below to view your real-time order and shipment tracking details.', 'dragwyb-click-to-chat' );

				return new \WP_REST_Response(
					array(
						'success'            => true,
						'message'            => $tracker_msg,
						'show_order_tracker' => true,
						'session_id'         => $session_id,
					),
					200
				);
			} else {
				$login_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
				if ( ! empty( $bot['order_tracking_login_msg'] ) ) {
					$not_logged_in_msg = str_replace( '{login_url}', esc_url( $login_url ), $bot['order_tracking_login_msg'] );
				} else {
					$not_logged_in_msg = sprintf(
					/* translators: %s: Login URL */
						esc_html__( 'To securely track your order status, please [log in to your account](%s) first.', 'dragwyb-click-to-chat' ),
						esc_url( $login_url )
					);
				}

				return new \WP_REST_Response(
					array(
						'success'            => true,
						'message'            => $not_logged_in_msg,
						'show_order_tracker' => false,
						'session_id'         => $session_id,
					),
					200
				);
			}
		}

		// Intelligent Support Escalation & Ticket Logging (Explicit Human Handoff ONLY)
		if ( ! empty( $prompt ) && ( ! isset( $bot['enable_support_escalation'] ) || (bool) $bot['enable_support_escalation'] ) && self::detect_explicit_human_handoff( $prompt ) ) {
			$classification = self::classify_user_intent( $prompt );
			// Auto-create/link support ticket if Support Center is enabled
			if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
				$support_settings = get_option( 'dctc_support_settings', array() );
				if ( ! empty( $support_settings['enabled'] ) ) {
					$existing_ticket = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
					if ( ! $existing_ticket ) {
						DCTC_Support_Ticket_Service::create_ticket(
							array(
								'subject'          => '[Support Handoff] ' . wp_trim_words( $prompt, 8, '...' ),
								'session_id'       => $session_id,
								'customer_email'   => $email,
								'origin_type'      => 'chatbot',
								'reply_surface'    => 'chatbot_widget',
								'interaction_type' => 'HYBRID_SUPPORT',
								'control_mode'     => 'ai',
							)
						);
					}
				}
			}

			$escalation_text = ! empty( $bot['support_ticket_msg'] )
			? $bot['support_ticket_msg']
			: esc_html__( 'I have connected your session with our live support team. A support specialist will assist you shortly.', 'dragwyb-click-to-chat' );

			return $this->save_and_respond(
				$escalation_text,
				$session_id,
				$bot,
				$email,
				$prompt,
				array(),
				true
			);
		}

		// Intelligent Follow-up Email Detection (When visitor replies with their email address)
		if ( ! empty( $prompt ) && preg_match( '/\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/', $prompt, $email_prov_matches ) ) {
			$provided_email          = sanitize_email( $email_prov_matches[0] );
			$is_short_email_response = ( mb_strlen( trim( $prompt ) ) < 90 ) || preg_match( '/^(?:my email is|here is my email|email:?|sure,? it\'s|it is)?\s*[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\.?$/i', trim( $prompt ) );

			if ( $is_short_email_response ) {
				$history              = $this->get_recent_conversation( $session_id, 5 );
				$is_sales_lead        = preg_match( '/\b(quote|pricing|demo|bulk|consultation|custom pricing|buy|purchase|interested|how to buy|order|cost|store)\b/i', $history );
				$is_support_connected = ! empty( $bot['enable_support_escalation'] ) || ( class_exists( 'DCTC_Support_Manager' ) );

				if ( $is_sales_lead && ! empty( $bot['enable_lead_capture'] ) && class_exists( 'DCTC_AI_DB' ) ) {
					DCTC_AI_DB::save_lead(
						array(
							'session_id'   => $session_id,
							'name'         => is_user_logged_in() ? wp_get_current_user()->display_name : 'Chat Visitor',
							'email'        => $provided_email,
							'requirement'  => ! empty( $history ) ? wp_trim_words( $history, 25, '...' ) : 'Sales Inquiry',
							'source_url'   => ! empty( $page_context['url'] ) ? esc_url_raw( $page_context['url'] ) : home_url(),
							'score'        => 90,
							'intent_level' => 'high',
							'status'       => 'new',
						)
					);
				}

				if ( class_exists( 'DCTC_Support_Ticket_Service' ) && $is_support_connected ) {
					$existing_ticket = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
					if ( ! $existing_ticket ) {
						DCTC_Support_Ticket_Service::create_ticket(
							array(
								'subject'             => ( $is_sales_lead ? '[Lead Inquiry] ' : '[Support Request] ' ) . ( ! empty( $history ) ? wp_trim_words( $history, 8, '...' ) : ( $is_sales_lead ? 'Customer Sales Inquiry' : 'Customer Technical Support' ) ),
								'session_id'          => $session_id,
								'customer_name'       => is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest Customer',
								'customer_email'      => $provided_email,
								'customer_wp_user_id' => get_current_user_id(),
								'origin_type'         => 'chatbot',
								'reply_surface'       => 'chatbot_widget',
								'interaction_type'    => $is_sales_lead ? 'LEAD_GENERATION' : 'HYBRID_SUPPORT',
								'control_mode'        => 'ai',
								'initial_message'     => $history ?: $prompt,
							)
						);
					} else {
						$ticket_obj = class_exists( 'DCTC_Support_Ticket' ) ? new DCTC_Support_Ticket( (int) $existing_ticket['id'] ) : null;
						if ( $ticket_obj && $ticket_obj->is_valid() ) {
							$ticket_obj->update_email( $provided_email );
							$ticket_obj->update_meta( 'detected_intent', ( $is_sales_lead ? 'lead_generation' : 'support_ticket' ), 'auto' );
						}
					}
				}

				$support_url = ! empty( $bot['support_url'] ) ? esc_url( $bot['support_url'] ) : home_url();

				if ( $is_sales_lead ) {
					$resp_msg = sprintf(
						/* translators: 1: Email, 2: Support URL */
						__( 'Thank you for providing your email! We have received your request and our team will follow up directly at %1$s. You can also reach out or check updates anytime via [Contact Support](%2$s).', 'dragwyb-click-to-chat' ),
						esc_html( $provided_email ),
						$support_url
					);
				} else {
					$resp_msg = sprintf(
						/* translators: 1: Email, 2: Support URL */
						__( 'Thank you! We have received your email (%1$s) and created a support ticket for your inquiry. Our technical team will follow up with you directly soon. You can also contact us or view updates anytime via [Contact Support](%2$s).', 'dragwyb-click-to-chat' ),
						esc_html( $provided_email ),
						$support_url
					);
				}

				return $this->save_and_respond(
					$resp_msg,
					$session_id,
					$bot,
					$provided_email,
					$prompt,
					array(),
					true
				);
			}
		}

		if ( empty( $bot ) || empty( $models ) ) {
			return $this->error_response(
				esc_html__( 'Chatbot settings incomplete.', 'dragwyb-click-to-chat' ),
				500
			);
		}

		try {
			$provider = $this->get_active_provider( $bot );
			$model_id = $this->get_model_id( $provider, $models );

			if ( empty( $model_id ) ) {
				return $this->error_response(
					esc_html__( 'No AI computational model selected for processing.', 'dragwyb-click-to-chat' ),
					400
				);
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI Chat Active Provider/Model Error: ' . $e->getMessage() );
			$is_admin = current_user_can( 'manage_options' );
			if ( $is_admin ) {
				$settings_url  = admin_url( 'admin.php?page=dragwyb-click-to-chat' );
				$error_message = sprintf(
				/* translators: %s: AI Assistant settings URL */
					__( 'AI Provider API key is not configured. Please [configure your AI Provider API key](%s) in settings.', 'dragwyb-click-to-chat' ),
					esc_url( $settings_url )
				);
			} else {
				$error_message = esc_html__( 'AI Assistant is currently offline for maintenance. Please check back later.', 'dragwyb-click-to-chat' );
			}
			return $this->error_response( $error_message, 400 );
		}
		$this->set_ticket_ai_responding( $session_id, true );

		try {
			$system_message = $this->build_system_prompt( $bot, $settings );
		} catch ( Exception $e ) {
			$this->set_ticket_ai_responding( $session_id, false );
			self::log_debug( 'Dragwyb AI AI Chat System Prompt Error: ' . $e->getMessage() );
			$error_message = current_user_can( 'manage_options' ) ? $e->getMessage() : esc_html__( 'An error occurred while processing your request.', 'dragwyb-click-to-chat' );
			return $this->error_response( $error_message, 500 );
		}
		try {
			$rag_data = $this->rag_controller->get_chat_context( $prompt, $session_id, $bot, $settings );

			if ( ! empty( $rag_data['require_data_missing'] ) ) {
				$is_logged_in   = is_user_logged_in() || ( ! empty( $page_context['is_logged_in'] ) );
				$logged_in_user = is_user_logged_in() ? wp_get_current_user() : null;
				$user_email     = $logged_in_user && ! empty( $logged_in_user->user_email ) ? $logged_in_user->user_email : ( ! empty( $email ) ? $email : '' );
				$user_name      = $logged_in_user && ! empty( $logged_in_user->display_name ) ? $logged_in_user->display_name : 'Guest Visitor';

				// Check if guest visitor just provided their email in this prompt
				if ( empty( $user_email ) && preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $prompt, $matches_email ) ) {
					$user_email = sanitize_email( $matches_email[0] );
					$email      = $user_email;
				}

				if ( ! empty( $user_email ) ) {
					// We have the user's email -> Automatically create a Support Ticket
					if ( class_exists( 'DCTC_Support_Ticket_Service' ) && ( ! empty( $bot['enable_support_escalation'] ) || class_exists( 'DCTC_Support_Manager' ) ) ) {
						$existing_ticket = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
						if ( ! $existing_ticket ) {
							DCTC_Support_Ticket_Service::create_ticket(
								array(
									'subject'             => '[Support Inquiry] ' . wp_trim_words( $prompt, 8, '...' ),
									'session_id'          => $session_id,
									'customer_name'       => $user_name,
									'customer_email'      => $user_email,
									'customer_wp_user_id' => $logged_in_user ? $logged_in_user->ID : 0,
									'origin_type'         => 'chatbot',
									'reply_surface'       => 'chatbot_widget',
									'interaction_type'    => 'HYBRID_SUPPORT',
									'control_mode'        => 'ai',
									'initial_message'     => $prompt,
								)
							);
						}
					}

					if ( $is_logged_in ) {
						$missing_msg = sprintf(
						/* translators: %s: User email */
							__( 'I don\'t have the exact details for this in our knowledge base right now, but I have created a support request for you. Our team will review your question and contact you at %s soon.', 'dragwyb-click-to-chat' ),
							esc_html( $user_email )
						);
					} else {
						$missing_msg = sprintf(
						/* translators: %s: User email */
							__( 'Thank you for providing your email! I don\'t have the exact details in our knowledge base, but I have logged your request with our team. Someone will review your inquiry and contact you at %s soon.', 'dragwyb-click-to-chat' ),
							esc_html( $user_email )
						);
					}
				} else {
					// Guest user with NO email provided -> Politely ask for their email
					$missing_msg = __( 'I don\'t have the exact details for your question in our knowledge base right now. Please share your email address with us so our team can look into it and contact you directly with the right details.', 'dragwyb-click-to-chat' );
				}

				return $this->save_and_respond(
					$missing_msg,
					$session_id,
					$bot,
					$email,
					$prompt,
					! empty( $rag_data['action_buttons'] ) ? $rag_data['action_buttons'] : array()
				);
			}

			if ( ! empty( $rag_data['context'] ) ) {
				$system_message .= "\n\nCRITICAL INSTRUCTION: Answer the user's question concisely based ONLY on the facts provided in the 'Knowledge Base Information' below. Do not hallucinate, over-explain, or add external general knowledge that is not explicitly stated in the context. If the user asks for customizations, code tweaks, or topics not covered in our data, guide them to contact our support team.\n\nKnowledge Base Information:\n" . $rag_data['context'];
			}

			$rag_links = $rag_data['links'];
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI RAG Link Fetch Error: ' . $e->getMessage() );
			$rag_links = array();
		}

		try {
			$mcp_context = $this->mcp_controller->get_chat_context( $prompt, $settings );
			if ( ! empty( $mcp_context ) ) {
				$system_message .= "\n\nCustom Data:\n" . $mcp_context;
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI MCP Context Fetch Error: ' . $e->getMessage() );
		}

		try {
			if ( class_exists( 'DCTC_AI_WooCommerce' ) ) {
				$wc_context = DCTC_AI_WooCommerce::build_llm_product_context( $prompt );
				if ( ! empty( $wc_context ) ) {
					$system_message .= $wc_context;
				}
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI WooCommerce Context Error: ' . $e->getMessage() );
		}

		// Feature 13: Page-Aware Context Injection
		try {
			$page_context = $request->get_param( 'page_context' );
			if ( ! empty( $page_context ) && is_array( $page_context ) && ! empty( $bot['enable_page_context'] ) ) {
				$context_str = "\n\nCURRENT VISITED PAGE CONTEXT:\n";
				if ( ! empty( $page_context['url'] ) ) {
					$context_str .= '- Page URL: ' . esc_url_raw( $page_context['url'] ) . "\n";
				}
				if ( ! empty( $page_context['title'] ) ) {
					$context_str .= '- Page Title: ' . sanitize_text_field( $page_context['title'] ) . "\n";
				}
				if ( ! empty( $page_context['post_type'] ) ) {
					$context_str .= '- Page Type: ' . sanitize_text_field( $page_context['post_type'] ) . "\n";
				}
				if ( ! empty( $page_context['product'] ) && is_array( $page_context['product'] ) ) {
					$p            = $page_context['product'];
					$currency     = sanitize_text_field( $p['currency'] ?? '$' );
					$price        = sanitize_text_field( $p['price'] ?? '' );
					$stock        = ! empty( $p['in_stock'] ) ? 'In Stock' : 'Out of Stock';
					$sku          = ! empty( $p['sku'] ) ? ', SKU: ' . sanitize_text_field( $p['sku'] ) : '';
					$context_str .= '- Product Currently Viewed: ' . sanitize_text_field( $p['name'] ?? '' ) . " (Price: {$currency}{$price}, Stock: {$stock}{$sku})\n";
				}
				$context_str    .= "When the user asks questions referring to 'this page', 'this product', 'how much is this', or 'what is this', use the above page context directly.\n";
				$system_message .= $context_str;
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI Page Context Error: ' . $e->getMessage() );
		}

		// Feature 14: Multilingual Prompt Handling
		try {
			if ( ! isset( $bot['enable_multilingual'] ) || (bool) $bot['enable_multilingual'] ) {
				$pref_lang    = ! empty( $bot['preferred_language'] ) ? $bot['preferred_language'] : 'auto';
				$visitor_lang = sanitize_text_field( $params['visitor_lang'] ?? '' );
				if ( $pref_lang === 'auto' ) {
					$system_message .= "\n\nMULTILINGUAL INSTRUCTION: Automatically detect the language of the user's message (e.g., English, Spanish, French, German, Italian, Portuguese, Hindi, Arabic, Chinese, Japanese, etc.) and reply fluently, naturally, and accurately in that EXACT same language, unless the user explicitly requests another language.";
					if ( ! empty( $visitor_lang ) ) {
						$system_message .= " The visitor's browser/locale language is detected as: {$visitor_lang}.";
					}
				} else {
					$system_message .= "\n\nMULTILINGUAL INSTRUCTION: The primary language configured is '{$pref_lang}'. Always respond in '{$pref_lang}' or the language chosen by the visitor.";
				}
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI Multilingual Prompt Error: ' . $e->getMessage() );
		}

		try {
			$memory         = $this->get_optimized_memory( $session_id, $prompt, $system_message );
			$system_message = $memory['system_message'];
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI Memory Optimization Error: ' . $e->getMessage() );
		}

		// Feature: Session-Aware Once-Only Polite Email Request
		try {
			$known_user_email = '';
			if ( is_user_logged_in() ) {
				$u = wp_get_current_user();
				if ( $u && ! empty( $u->user_email ) ) {
					$known_user_email = $u->user_email;
				}
			}
			if ( empty( $known_user_email ) && ! empty( $email ) ) {
				$known_user_email = $email;
			}

			$email_already_requested = false;
			if ( ! empty( $session_id ) ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$session_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT content, email FROM {$wpdb->prefix}dctc_ai_sessions WHERE session_id = %s ORDER BY id DESC LIMIT 1",
						$session_id
					)
				);
				if ( $session_row ) {
					if ( empty( $known_user_email ) && ! empty( $session_row->email ) ) {
						$known_user_email = $session_row->email;
					}
					if ( ! empty( $session_row->content ) ) {
						$msg_history = json_decode( $session_row->content, true );
						if ( is_array( $msg_history ) ) {
							foreach ( $msg_history as $m ) {
								$m_text = ! empty( $m['content'] ) ? $m['content'] : '';
								if ( empty( $known_user_email ) && preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $m_text, $m_matches ) ) {
									$known_user_email = $m_matches[0];
								}
								if ( ! empty( $m['role'] ) && $m['role'] === 'assistant' ) {
									if ( preg_match( '/\b(comfortable.*email|share.*email|provide.*email|your email address|follow up.*email)\b/i', $m_text ) ) {
										$email_already_requested = true;
									}
								}
							}
						}
					}
				}
			}

			$support_url_context = ! empty( $bot['support_url'] ) ? esc_url_raw( $bot['support_url'] ) : home_url();

			if ( ! empty( $known_user_email ) ) {
				$system_message .= "\n\nCRITICAL USER CONTEXT: The user is LOGGED IN or their EMAIL IS ALREADY KNOWN ({$known_user_email}).
- Do NOT ask for their email address under any circumstances.
- If the inquiry relates to product purchase, sales quotation, troubleshooting, or support: Let them know that we have received their request and our team will follow up directly at {$known_user_email}. You may also provide: [Contact Support]({$support_url_context}).\n";
			} elseif ( $email_already_requested ) {
				$system_message .= "\n\nCRITICAL USER CONTEXT: The user is a GUEST and you have ALREADY asked for their email in this session.
- Do NOT ask for their email again.
- Direct them to [Contact Support]({$support_url_context}) if they need human assistance.\n";
			} else {
				$system_message .= "\n\nCRITICAL USER CONTEXT: The user is a GUEST (NON-LOGGED IN) and their EMAIL IS UNKNOWN.
- NEVER claim that 'our team will follow up at your account email' because the user is NOT logged in!
- HYBRID SUPPORT & LEAD RULE: When the user asks a product purchase/buying inquiry, quotation/pricing, or technical support/bug/troubleshooting issue, ALWAYS provide helpful answers and direct product links, and conclude by offering BOTH options:
  'Please feel free to share your email address here so our team can directly contact you and assist you, or you can reach out to us directly through [Contact Support]({$support_url_context}).'
- Never end with generic robotic filler like 'How can I help you today?'.\n";
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI Session-Aware Email Check Error: ' . $e->getMessage() );
		}

		/**
		 * Filters the final compiled system prompt before sending to AI provider.
		 *
		 * @param string $system_message
		 * @param string $session_id
		 * @param array  $bot
		 */
		$system_message = apply_filters( 'dctc_ai_chat_system_prompt', $system_message, $session_id, $bot );

		// Security: Redact sensitive credentials, password hashes, payment PANs, and secret keys before sending to AI
		if ( class_exists( 'DCTC_AI_Data_Sanitizer' ) ) {
			$system_message = DCTC_AI_Data_Sanitizer::redact_sensitive_data( $system_message );
			$clean_prompt   = DCTC_AI_Data_Sanitizer::redact_sensitive_data( $prompt );
		} else {
			$clean_prompt = $prompt;
		}

		try {
			$ai_result = $this->call_ai_api(
				$clean_prompt,
				$system_message,
				$provider,
				$model_id,
				$bot,
				$models,
				$attachments
			);

			$ai_message    = isset( $ai_result['message'] ) ? $ai_result['message'] : '';
			$used_provider = isset( $ai_result['provider'] ) ? $ai_result['provider'] : $provider;
			$used_model    = isset( $ai_result['model'] ) ? $ai_result['model'] : $model_id;

			// Security: Sanitize AI response against internal errors, credentials, and leaked system traces
			if ( class_exists( 'DCTC_AI_Data_Sanitizer' ) ) {
				$ai_message = DCTC_AI_Data_Sanitizer::sanitize_ai_response( $ai_message );
			}

			/**
			 * Filters the raw AI assistant response text.
			 *
			 * @param string $ai_message
			 * @param string $prompt
			 * @param string $session_id
			 */
			$ai_message = apply_filters( 'dctc_ai_chat_response', $ai_message, $prompt, $session_id );

			// Dynamic AI Intent & Conversational Lead/Email Extraction
			$intent_data     = self::parse_dynamic_ai_intent( $ai_message, $prompt );
			$ai_message      = $intent_data['clean_message'];
			$detected_intent = $intent_data['intent'];
			$detected_email  = $intent_data['email'];
			$detected_phone  = $intent_data['phone'];

			if ( ! empty( $detected_email ) ) {
				$email = $detected_email;
			}

			// Real-Time URL Verification & Broken Link Sanitization
			if ( ! empty( $ai_message ) ) {
				$ai_message = self::validate_and_sanitize_urls_in_content( $ai_message, $bot );
			}

			if ( empty( $ai_message ) ) {
				if ( class_exists( 'DCTC_Error_Logger' ) ) {
					DCTC_Error_Logger::log_ai_error(
						$provider,
						$model_id,
						$prompt,
						__( 'AI connection returned an empty response.', 'dragwyb-click-to-chat' ),
						array(
							'type'    => 'Empty Response',
							'context' => 'Chat API Response',
						)
					);
				}
				return $this->error_response(
					esc_html__( 'AI connection returned an empty response.', 'dragwyb-click-to-chat' ),
					500
				);
			}

			// Dynamic Integration: Auto-connect with Lead System only if explicitly requested or organically suggested
			$should_show_lead_form = false;
			$is_support_connected  = ! empty( $bot['enable_support_escalation'] ) || ( class_exists( 'DCTC_Support_Manager' ) );

			$rule_class  = self::classify_user_intent( $prompt );
			$is_greeting = ( ! empty( $rule_class['category'] ) && 'greeting' === $rule_class['category'] );

			// Strictly disallow greetings, product discovery, and support/order tracking from forcing lead forms
			$is_active_lead_intent = ! $is_greeting
			&& ( 'lead_generation' === $detected_intent || self::detect_lead_intent( $prompt ) )
			&& 'support_ticket' !== $detected_intent
			&& 'order_tracking' !== $detected_intent
			&& 'human_handoff' !== $detected_intent;

			// Show lead form only if user explicitly requested sales contact/quote OR AI naturally suggested the form
			if ( ! empty( $bot['enable_lead_capture'] ) ) {
				if ( is_string( $ai_message ) && ( false !== stripos( $ai_message, 'form below' ) || false !== stripos( $ai_message, 'inquiry form' ) || false !== stripos( $ai_message, 'fill out the form' ) ) ) {
					$should_show_lead_form = true;
				} elseif ( $is_active_lead_intent ) {
					$should_show_lead_form = true;
				}
			}

			$contains_support_referral = (
				false !== stripos( $ai_message, 'contact support' ) ||
				( ! empty( $bot['support_url'] ) && false !== stripos( $ai_message, untrailingslashit( $bot['support_url'] ) ) ) ||
				false !== stripos( $ai_message, 'support team' ) ||
				false !== stripos( $ai_message, 'technical team' ) ||
				( false !== stripos( $ai_message, 'our team' ) && ( false !== stripos( $ai_message, 'reach out' ) || false !== stripos( $ai_message, 'contact' ) ) )
			);

			// Check if AI response has high confidence for Support Request / Lead Generation OR contains a Contact Support referral
			$is_actionable_connect = ! $is_greeting && (
				in_array( $detected_intent, array( 'lead_generation', 'support_ticket', 'human_handoff' ), true ) ||
				$contains_support_referral
			);

			$is_logged_in    = is_user_logged_in() || ( ! empty( $page_context['is_logged_in'] ) );
			$logged_in_user  = is_user_logged_in() ? wp_get_current_user() : null;
			$effective_email = $logged_in_user && ! empty( $logged_in_user->user_email ) ? $logged_in_user->user_email : ( ! empty( $email ) ? $email : ( ! empty( $detected_email ) ? $detected_email : '' ) );
			$effective_name  = $logged_in_user && ! empty( $logged_in_user->display_name ) ? $logged_in_user->display_name : ( ! empty( $intent_data['name'] ) ? $intent_data['name'] : 'Guest Visitor' );

			if ( $is_actionable_connect ) {
				$effective_ticket_type    = ( 'lead_generation' === $detected_intent || self::detect_lead_intent( $prompt ) ) ? 'LEAD_GENERATION' : 'HYBRID_SUPPORT';
				$effective_subject_prefix = ( 'LEAD_GENERATION' === $effective_ticket_type ) ? '[Lead Inquiry] ' : '[Support] ';

				$existing_ticket = ( class_exists( 'DCTC_Support_Ticket_Service' ) && $is_support_connected ) ? DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id ) : null;

				if ( ! empty( $effective_email ) ) {
					// 1. Lead Capture handling
					if ( 'LEAD_GENERATION' === $effective_ticket_type && ! empty( $bot['enable_lead_capture'] ) ) {
						if ( class_exists( 'DCTC_AI_DB' ) ) {
							$score = 75;
							if ( ! empty( $effective_email ) ) {
								$score += 15;
							}
							if ( ! empty( $detected_phone ) ) {
								$score += 10;
							}

							$lead_id = DCTC_AI_DB::save_lead(
								array(
									'session_id'   => $session_id,
									'name'         => $effective_name,
									'email'        => $effective_email,
									'phone'        => $detected_phone,
									'requirement'  => $prompt,
									'source_url'   => ! empty( $page_context['url'] ) ? esc_url_raw( $page_context['url'] ) : home_url(),
									'score'        => min( 100, $score ),
									'intent_level' => 'high',
									'status'       => 'new',
								)
							);

							if ( $lead_id && class_exists( 'DCTC_AI_Leads_Controller' ) ) {
								$leads_controller = new DCTC_AI_Leads_Controller();
								$leads_controller->maybe_send_lead_email(
									array(
										'id'          => $lead_id,
										'name'        => $effective_name,
										'email'       => $effective_email,
										'phone'       => $detected_phone,
										'requirement' => $prompt,
										'score'       => min( 100, $score ),
										'status'      => 'new',
									)
								);
							}
						}
					}

					// 2. Support Ticket creation / sync for logged-in user or known email
					if ( class_exists( 'DCTC_Support_Ticket_Service' ) && $is_support_connected ) {
						if ( ! $existing_ticket ) {
							DCTC_Support_Ticket_Service::create_ticket(
								array(
									'subject'             => $effective_subject_prefix . wp_trim_words( $prompt, 8, '...' ),
									'session_id'          => $session_id,
									'customer_name'       => $effective_name,
									'customer_email'      => $effective_email,
									'customer_wp_user_id' => $logged_in_user ? $logged_in_user->ID : 0,
									'origin_type'         => 'chatbot',
									'reply_surface'       => 'chatbot_widget',
									'interaction_type'    => $effective_ticket_type,
									'control_mode'        => 'ai',
									'initial_message'     => $prompt,
								)
							);
						} else {
							$ticket_id  = (int) $existing_ticket['id'];
							$ticket_obj = class_exists( 'DCTC_Support_Ticket' ) ? new DCTC_Support_Ticket( $ticket_id ) : null;
							if ( $ticket_obj && $ticket_obj->is_valid() ) {
								$ticket_obj->update_email( $effective_email );
								if ( ! empty( $detected_phone ) ) {
									$ticket_obj->update_phone( $detected_phone );
								}
								$ticket_obj->update_meta( 'detected_intent', ( 'LEAD_GENERATION' === $effective_ticket_type ? 'lead_generation' : 'support_ticket' ), 'auto' );
							}
						}
					}
				} else {
					// Non logged-in guest user without known email:
					$support_url_escaped = ! empty( $bot['support_url'] ) ? esc_url( $bot['support_url'] ) : home_url();

					// Prevent hallucinated 'account email' mentions for non-logged in guest users
					$ai_message = preg_replace( '/\bat your account email\b/i', 'directly', $ai_message );
					$ai_message = preg_replace( '/\bat your registered email\b/i', 'directly', $ai_message );

					if ( ( $contains_support_referral || $is_actionable_connect ) && ! $existing_ticket ) {
						$has_email_prompt = ( false !== stripos( $ai_message, 'email address' ) || false !== stripos( $ai_message, 'share your email' ) || false !== stripos( $ai_message, 'provide your email' ) );
						$has_support_link = ( false !== stripos( $ai_message, 'contact support' ) || false !== stripos( $ai_message, untrailingslashit( $support_url_escaped ) ) );

						if ( ! $has_email_prompt && ! $has_support_link ) {
							if ( 'LEAD_GENERATION' === $effective_ticket_type ) {
								$ai_message .= "\n\n" . sprintf(
									/* translators: %s: Support URL */
									__( 'Please feel free to share your email address here so our team can directly contact you and assist with your purchase, or you can reach out via [Contact Support](%s).', 'dragwyb-click-to-chat' ),
									$support_url_escaped
								);
							} else {
								$ai_message .= "\n\n" . sprintf(
									/* translators: %s: Support URL */
									__( 'Please feel free to share your email address here so our technical team can directly follow up and assist you, or submit a request directly on our [Contact Support](%s) page.', 'dragwyb-click-to-chat' ),
									$support_url_escaped
								);
							}
						} elseif ( ! $has_email_prompt ) {
							$ai_message .= "\n\n" . esc_html__( 'Please feel free to share your email address here so our team can directly contact you and assist you further.', 'dragwyb-click-to-chat' );
						} elseif ( ! $has_support_link ) {
							$ai_message .= "\n\n" . sprintf(
								/* translators: %s: Support URL */
								__( 'You can also reach out to our specialists directly on our [Contact Support](%s) page.', 'dragwyb-click-to-chat' ),
								$support_url_escaped
							);
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			$this->set_ticket_ai_responding( $session_id, false );
			if ( class_exists( 'DCTC_Error_Logger' ) ) {
				DCTC_Error_Logger::log_ai_error(
					$provider,
					$model_id,
					$prompt,
					$e->getMessage(),
					array(
						'type'    => 'Model Error',
						'code'    => (string) $e->getCode(),
						'context' => 'Chat Completion API',
					)
				);
			}
			self::log_debug( 'Dragwyb AI AI Chat API/Processing Error: ' . $e->getMessage() );
			$error_message = current_user_can( 'manage_options' ) ? $e->getMessage() : esc_html__( 'An error occurred while processing your request.', 'dragwyb-click-to-chat' );
			return $this->error_response( $error_message, 500 );
		}

		// Race Condition Guard: If an agent took control while LLM API was running, discard the AI output
		if ( class_exists( 'DCTC_Support_AI_Handoff_Service' ) && DCTC_Support_AI_Handoff_Service::should_block_ai_response( $session_id ) && ! $fallback_trigger ) {
			$this->set_ticket_ai_responding( $session_id, false );
			$human_response = DCTC_Support_AI_Handoff_Service::handle_customer_message_in_human_mode( $session_id, $prompt, $email );
			return new \WP_REST_Response( $human_response, 200 );
		}

		$show_sources = ! isset( $bot['show_sources'] ) || (bool) $bot['show_sources'];
		$sources      = ( $show_sources && ! empty( $rag_links ) ) ? array_slice( $rag_links, 0, 3 ) : array();

		$extra = array(
			'show_form' => ! empty( $should_show_lead_form ) ? array(
				'form_type'   => 'lead_generate',
				'show'        => true,
				'form_filled' => false,
			) : null,
		);

		try {
			$this->save_conversation( $prompt, $ai_message, $session_id, $used_provider, $used_model, $bot, $email, $sources, $extra );
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI Save Conversation Error: ' . $e->getMessage() );
		}

		try {
			if ( class_exists( 'DCTC_AI_Usage_Tracker' ) ) {
				$est_tokens = max( 10, intval( ( strlen( $prompt ) + strlen( $ai_message ) ) / 4 ) );
				DCTC_AI_Usage_Tracker::record_usage( $session_id, $used_provider, $used_model, $est_tokens );
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI Record Usage Error: ' . $e->getMessage() );
		}

		try {
			$formatted_messages = $this->get_formatted_messages( $session_id );
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI Get Formatted Messages Error: ' . $e->getMessage() );
			$formatted_messages = array();
		}

		$wc_products                  = array();
		$is_support_or_tracking_query = in_array( $detected_intent, array( 'support_ticket', 'human_handoff', 'order_tracking' ), true )
		|| preg_match( '/\b(issue|problem|bug|glitch|error|broken|not working|conflict|defect|trouble|refund|damage|support|ticket|help me|how to fix|facing|face a)\b/i', $prompt );

		if ( ! $is_support_or_tracking_query && class_exists( 'DCTC_AI_WooCommerce' ) && DCTC_AI_WooCommerce::is_active() ) {
			$is_explicit_catalog_query = preg_match( '/\b(popular products|best sellers|recommended products|recommend products|show products|store products|browse products|catalog)\b/i', $prompt );
			$is_shopping_query         = preg_match( '/\b(buy|purchase|pricing|price of|how much is|shop|shoes|shirt|item|items|recommend)\b/i', $prompt );

			if ( $is_explicit_catalog_query ) {
				$wc_products = DCTC_AI_WooCommerce::get_recommendations( 'popular', 3 );
			} elseif ( $is_shopping_query ) {
				$clean_search = preg_replace( '/\b(buy|purchase|how much is|pricing of|price of|can i get|show me|want to|looking for|recommend)\b/i', '', $prompt );
				$clean_search = trim( $clean_search );
				if ( ! empty( $clean_search ) && strlen( $clean_search ) >= 3 ) {
					$wc_products = DCTC_AI_WooCommerce::search_products( $clean_search, 3 );
				}
			}
		}

		// Extract structured lead fields for autofill
		$detected_name     = ! empty( $intent_data['name'] ) ? $intent_data['name'] : ( is_user_logged_in() ? wp_get_current_user()->display_name : '' );
		$detected_email    = ! empty( $intent_data['email'] ) ? $intent_data['email'] : ( ! empty( $email ) ? $email : ( is_user_logged_in() ? wp_get_current_user()->user_email : '' ) );
		$detected_phone    = ! empty( $intent_data['phone'] ) ? $intent_data['phone'] : '';
		$detected_interest = ! empty( $intent_data['interest'] ) ? $intent_data['interest'] : ( ! empty( $page_context['product']['name'] ) ? $page_context['product']['name'] : '' );
		$detected_company  = ! empty( $intent_data['company'] ) ? $intent_data['company'] : '';

		$this->set_ticket_ai_responding( $session_id, false );
		$ticket_info  = $this->get_session_ticket_info( $session_id );
		$has_ticket   = ! empty( $ticket_info );
		$control_mode = ! empty( $ticket_info['control_mode'] ) ? $ticket_info['control_mode'] : 'ai';

		return new \WP_REST_Response(
			array(
				'success'         => true,
				'message'         => $ai_message,
				'session_id'      => $session_id,
				'messages'        => $formatted_messages,
				'sources'         => $sources,
				'reference_links' => $sources,
				'products'        => $wc_products,
				'lead_data'       => array(
					'name'        => $detected_name,
					'email'       => $detected_email,
					'phone'       => $detected_phone,
					'interest'    => $detected_interest,
					'company'     => $detected_company,
					'requirement' => $prompt,
				),
				'has_ticket'      => $has_ticket,
				'ticket'          => $ticket_info,
				'control_mode'    => $control_mode,
			),
			200
		);
	}

	/**
	 * REST callback: return all chat sessions for the admin dashboard.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function get_sessions( $request ) {
		$user_id = get_current_user_id();

		// Handle user-specific loading limit.
		$limit = $request->get_param( 'limit' );
		if ( ! is_null( $limit ) ) {
			$limit = sanitize_text_field( $limit );
			update_user_meta( $user_id, 'dctc_ai_sessions_load_limit', $limit );
		} else {
			$limit = get_user_meta( $user_id, 'dctc_ai_sessions_load_limit', true );
			if ( empty( $limit ) ) {
				$limit = '100'; // Default limit.
			}
		}

		// Handle user-specific sorting order.
		$order = $request->get_param( 'order' );
		if ( ! is_null( $order ) ) {
			$order = sanitize_text_field( $order );
			update_user_meta( $user_id, 'dctc_ai_sessions_sort_order', $order );
		} else {
			$order = get_user_meta( $user_id, 'dctc_ai_sessions_sort_order', true );
			if ( empty( $order ) ) {
				$order = 'desc'; // Default order.
			}
		}

		$sessions = DCTC_AI_DB::dctc_ai_get_all_sessions( $limit, $order );

		return new \WP_REST_Response(
			array(
				'sessions'   => $sessions,
				'total'      => count( $sessions ),
				'load_limit' => $limit,
				'sort_order' => $order,
			),
			200
		);
	}

	/**
	 * REST callback: delete a chat session from the database.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_session( $request ) {
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );

		if ( empty( $session_id ) ) {
			return new \WP_Error(
				'dctc_ai_invalid_session',
				__( 'Session ID is required.', 'dragwyb-click-to-chat' ),
				array( 'status' => 400 )
			);
		}

		if ( ! DCTC_AI_DB::dctc_ai_delete_session( $session_id ) ) {
			return new \WP_Error(
				'dctc_ai_delete_failed',
				__( 'Session could not be deleted.', 'dragwyb-click-to-chat' ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * REST callback: clear session and reset cookies.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function clear_session( $request ) {
		$new_session_id = 'sess_' . wp_generate_password( 9, false );

		setcookie( 'dctc_ai_session_id', $new_session_id, time() + 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		setcookie( 'dctc_ai_clear_allowed', 'true', time() + 1800, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );

		return new \WP_REST_Response(
			array(
				'success'    => true,
				'session_id' => $new_session_id,
			),
			200
		);
	}

	/**
	 * REST callback: Generate or retrieve an AI executive summary for a session.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function summarize_session( $request ) {
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		if ( empty( $session_id ) ) {
			return $this->error_response( __( 'Session ID is required.', 'dragwyb-click-to-chat' ), 400 );
		}

		global $wpdb;
		$table   = $wpdb->prefix . 'dctc_ai_sessions';
		$session = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session_id = %s", $session_id ),
			ARRAY_A
		);

		if ( ! $session ) {
			return $this->error_response( __( 'Session not found.', 'dragwyb-click-to-chat' ), 404 );
		}

		$messages_raw = json_decode( $session['content'] ?? '[]', true );
		if ( ! is_array( $messages_raw ) || empty( $messages_raw ) ) {
			return $this->error_response( __( 'Conversation has no messages to summarize.', 'dragwyb-click-to-chat' ), 400 );
		}

		// Prepare conversation transcript for AI analysis
		$transcript_lines = array();
		foreach ( $messages_raw as $m ) {
			$role = ( $m['role'] ?? 'user' ) === 'user' ? 'Visitor' : 'Assistant';
			$text = wp_strip_all_tags( $m['content'] ?? '' );
			if ( ! empty( $text ) ) {
				$transcript_lines[] = "{$role}: {$text}";
			}
		}
		$transcript = implode( "\n", array_slice( $transcript_lines, -30 ) );

		$system_prompt = 'You are an executive conversation analyst. Analyze the provided customer chat transcript and output ONLY a valid JSON object without markdown formatting or backticks:
{
  "goal": "1-2 sentence summary of what the customer wanted",
  "questions": ["key question 1", "key question 2"],
  "topics": ["topic or product 1", "topic 2"],
  "sentiment": "positive" or "neutral" or "frustrated",
  "intent_tag": "inquiry" or "support" or "purchase" or "feedback",
  "next_action": "recommended followup or next step"
}';

		$user_prompt = "Transcript:\n" . $transcript;

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot      = isset( $settings['chatbot'] ) ? $settings['chatbot'] : array();
		$models   = isset( $settings['models'] ) ? $settings['models'] : array();
		$provider = ! empty( $bot['default_provider'] ) ? $bot['default_provider'] : 'openai';
		$model_id = ! empty( $models[ $provider ] ) ? $models[ $provider ] : '';

		$summary_data = null;

		try {
			$ai_res   = $this->call_ai_api( $user_prompt, $system_prompt, $provider, $model_id, $bot, $models );
			$raw_text = trim( $ai_res['message'] ?? '' );
			$raw_text = preg_replace( '/^```(?:json)?\s*/i', '', $raw_text );
			$raw_text = preg_replace( '/\s*```$/', '', $raw_text );
			$parsed   = json_decode( $raw_text, true );

			if ( is_array( $parsed ) && ! empty( $parsed['goal'] ) ) {
				$summary_data = array(
					'goal'        => sanitize_text_field( $parsed['goal'] ),
					'questions'   => array_map( 'sanitize_text_field', (array) ( $parsed['questions'] ?? array() ) ),
					'topics'      => array_map( 'sanitize_text_field', (array) ( $parsed['topics'] ?? array() ) ),
					'sentiment'   => in_array( $parsed['sentiment'] ?? '', array( 'positive', 'neutral', 'frustrated' ), true ) ? $parsed['sentiment'] : 'neutral',
					'intent_tag'  => in_array( $parsed['intent_tag'] ?? '', array( 'inquiry', 'support', 'purchase', 'feedback' ), true ) ? $parsed['intent_tag'] : 'general',
					'next_action' => sanitize_text_field( $parsed['next_action'] ?? '' ),
				);
			}
		} catch ( \Throwable $e ) {
			self::log_debug( 'Session summarize AI error: ' . $e->getMessage() );
		}

		// Fallback heuristic if AI call fails or is unavailable
		if ( ! $summary_data ) {
			$first_user_msg = '';
			foreach ( $messages_raw as $m ) {
				if ( ( $m['role'] ?? '' ) === 'user' ) {
					$first_user_msg = wp_strip_all_tags( $m['content'] ?? '' );
					break;
				}
			}
			$summary_data = array(
				'goal'        => ! empty( $first_user_msg ) ? sprintf( __( 'Customer inquired about: %s', 'dragwyb-click-to-chat' ), substr( $first_user_msg, 0, 100 ) ) : __( 'General conversation with AI Assistant', 'dragwyb-click-to-chat' ),
				'questions'   => array( ! empty( $first_user_msg ) ? substr( $first_user_msg, 0, 100 ) : __( 'General inquiry', 'dragwyb-click-to-chat' ) ),
				'topics'      => array( __( 'General', 'dragwyb-click-to-chat' ) ),
				'sentiment'   => 'neutral',
				'intent_tag'  => 'inquiry',
				'next_action' => __( 'Review full conversation log', 'dragwyb-click-to-chat' ),
			);
		}

		// Save in database
		DCTC_AI_DB::update_session_summary(
			$session_id,
			wp_json_encode( $summary_data ),
			$summary_data['sentiment'],
			$summary_data['intent_tag']
		);

		return new \WP_REST_Response(
			array(
				'success'    => true,
				'session_id' => $session_id,
				'summary'    => $summary_data,
			),
			200
		);
	}

	/**
	 * REST callback: Return conversation analytics.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_analytics( $request ) {
		$analytics = DCTC_AI_DB::get_conversation_analytics();
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'analytics' => $analytics,
			),
			200
		);
	}

	/**
	 * Get Client IP
	 *
	 * Best-effort caller IP for rate limiting. Only REMOTE_ADDR is trusted;
	 * headers like X-Forwarded-For are attacker-controlled unless a proxy
	 * is explicitly configured to set them, so they're not used here.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '';
	}

	/**
	 * Is Chat Rate Limited
	 *
	 * permission_check() accepts any valid "wp_rest" nonce, which
	 * WordPress issues identically to every anonymous visitor and is
	 * readable in every page's HTML source — it does not identify a
	 * caller. Throttle by IP instead, so one client can't run up the
	 * site owner's AI provider costs by hammering the endpoint.
	 *
	 * @return bool True if the current caller has exceeded the limit.
	 */
	private function is_rate_limited() {
		$ip = $this->get_client_ip();

		// Fail closed: without a usable IP we cannot throttle fairly.
		if ( empty( $ip ) ) {
			return true;
		}

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$limit    = isset( $settings['chatbot']['rate_limit_per_minute'] )
		? intval( $settings['chatbot']['rate_limit_per_minute'] )
		: 20;

		if ( $limit <= 0 ) {
			return false;
		}

		$key   = 'dctc_ai_chat_rl_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Get active AI provider
	 */
	private function get_active_provider( $bot ) {
		$available_providers = array();
		$supported           = DCTC_AI_Key_Store::get_supported_providers();

		foreach ( $supported as $provider ) {
			if ( ! empty( DCTC_AI_Key_Store::get_provider_key( $provider ) ) ) {
				$available_providers[] = $provider;
			}
		}

		if ( count( $available_providers ) === 0 ) {
			throw new Exception( esc_html__( 'No AI provider API keys configured.', 'dragwyb-click-to-chat' ) );
		}

		if ( count( $available_providers ) === 1 ) {
			return $available_providers[0];
		}

		// Multiple providers available - use default or first available
		$default_provider = ! empty( $bot['default_provider'] ) ? $bot['default_provider'] : 'openai';

		return in_array( $default_provider, $available_providers, true ) ? $default_provider : $available_providers[0];
	}

	/**
	 * Get model ID for provider
	 */
	private function get_model_id( $provider, $models ) {
		$model_id = isset( $models[ $provider ] ) ? $models[ $provider ] : '';

		if ( ! empty( $model_id ) ) {
			return $model_id;
		}

		// Fallback to default models
		$defaults = array(
			'openai'     => 'gpt-4o-mini',
			'google'     => 'gemini-3.5-flash-lite',
			'anthropic'  => 'claude-3-5-sonnet-20241022',
			'openrouter' => 'anthropic/claude-3.5-sonnet',
			'groq'       => 'llama-3.3-70b-versatile',
			'deepseek'   => 'deepseek-chat',
		);

		return isset( $defaults[ $provider ] ) ? $defaults[ $provider ] : '';
	}

	/**
	 * Build comprehensive system prompt with strict domain grounding and custom support referral.
	 */
	private function build_system_prompt( $bot, $settings ) {
		$site_name   = get_bloginfo( 'name' );
		$support_url = ! empty( $bot['support_url'] ) ? esc_url_raw( $bot['support_url'] ) : home_url();
		$bot_name    = ! empty( $bot['bot_name'] ) ? sanitize_text_field( $bot['bot_name'] ) : 'AI Assistant';

		$system_message = '';

		// Admin custom prompt if set
		if ( ! empty( $bot['system_prompt'] ) ) {
			$system_message .= trim( wp_kses_post( $bot['system_prompt'] ) ) . "\n\n";
		}

		// Manual knowledge base if set
		if ( ! empty( $bot['knowledge_text'] ) ) {
			$system_message .= "KNOWLEDGE BASE:\n" . wp_kses_post( $bot['knowledge_text'] ) . "\n\n";
		}

		$system_message .= "
CORE IDENTITY & DOMAIN RESTRICTIONS:
- You are the official, specialized AI Assistant ({$bot_name}) representing {$site_name}.
- Your primary purpose is to assist visitors with information regarding {$site_name}, including our products, services, store catalog, order tracking, policies, documentation, and customer support.
- You are NOT a generic open-ended AI (like raw ChatGPT or Gemini). You must NEVER generate generic programming tutorials, general code solutions (e.g. how to style unrelated HTML/CSS, generic JavaScript, generic Python), homework answers, or off-topic general knowledge.

OFF-TOPIC, UNRELATED, OR CUSTOMIZATION REQUESTS:
- If a user asks a question that is outside the scope of {$site_name}'s official products, documentation, and knowledge base (such as generic CSS/design modifications, custom coding, external tutorials, or unrelated topics):
  1. Do NOT generate generic web tutorials or open-ended external code.
  2. Politely and professionally inform the user that you are the dedicated assistant for {$site_name} and specialize in our official products, features, and documentation.
  3. If they need custom development, specialized CSS styling, or custom assistance, provide a helpful and warm response encouraging them to reach out directly to our human support team: [Contact Support]({$support_url}) or submit a request on our Support page so our specialists can assist them with custom requirements.

STRICT LINK & URL INTEGRITY RULES:
- NEVER invent, fabricate, or guess URLs (such as /features, /pricing, /tickets, /support-desk, /contact-us, /docs, /help, /refund, example.com, yoursite.com, etc.).
- ONLY generate markdown links if the exact URL is explicitly given in the retrieved context or if it is the official support link: [Contact Support]({$support_url}).
- If you refer to a site page, section, or feature whose exact URL is not provided in context, mention its name in plain text WITHOUT markdown link syntax (e.g., write \"check our Returns & Refunds policy\" instead of \"[Returns & Refunds](/returns)\").
- If the user asks how to get help or submit a ticket, direct them to [Contact Support]({$support_url}).

ACCURACY & KNOWLEDGE BASE GROUNDING:
- Answer based strictly on the provided Knowledge Base, Products, and Page context.
- Never invent facts, prices, policies, or technical claims.
- If information is not in our data, acknowledge it honestly and direct the user to our support team.
- Never say robotic phrases like 'Based on the context provided' or 'According to the knowledge base'—speak naturally as {$site_name}'s representative.

LANGUAGE & TONE:
- Always respond in the same language used by the user.
- Keep responses professional, warm, concise, and beautifully formatted with clear headings or bullet points when appropriate.

PRODUCT INQUIRIES, PURCHASING & SALES LEADS:
- When a user asks about products, expresses interest in buying or purchasing (e.g., 'I want to buy this', 'how to buy', 'interested in purchasing', 'looking for product details'):
  1. Provide the complete product details, features, price, and direct link on how to view or buy the product.
  2. If the user is a guest (email NOT known), ALWAYS conclude your response using the HYBRID APPROACH: 'Please feel free to share your email address here so our team can directly contact you and assist with your purchase, or you can reach out via [Contact Support]({$support_url}).'
  3. If the user is already logged in or email is known: 'We have received your inquiry and our team will follow up directly at your registered email, or you can reach us via [Contact Support]({$support_url}).'
  4. Set the intent tag to `lead_generation`. Never end with robotic filler like 'How can I help you today?'.

SUPPORT, BUG, TECHNICAL & TROUBLESHOOTING INQUIRIES:
- If the user asks about a bug, technical problem, error, configuration issue, translation issue, or needs support:
  1. Provide helpful troubleshooting steps or direct solutions based on available documentation.
  2. If the user is a guest (email NOT known), ALWAYS conclude your response using the HYBRID APPROACH: 'If you would like our technical support team to investigate this directly, please share your email address here, or submit a request directly on our [Contact Support]({$support_url}) page.'
  3. If the user is already logged in or email is known: 'A support request has been logged and our technical team will follow up with you directly at your registered email, or you can visit [Contact Support]({$support_url}) anytime.'
  4. Set the intent tag to `support_ticket`.

CONTACT SUPPORT & TEAM ESCALATION (HYBRID CONNECT):
- Whenever you provide a [Contact Support]({$support_url}) link or suggest contacting support / reaching out to our team:
  1. If the user is a guest (email NOT known): ALWAYS offer BOTH options—ask them to share their email address here so our team can directly follow up, and provide the [Contact Support]({$support_url}) link.
  2. If the user is already logged in or email is known: Let them know that we have received their request and our team will follow up directly at their registered email, and provide the [Contact Support]({$support_url}) link.
  3. Tag the intent accurately as `lead_generation` (for purchase/product/quote interest) or `support_ticket` (for technical help/issues/custom requests).

CONVERSATIONAL SALES & CUSTOM QUOTES:
- When a user explicitly asks for a custom quote, bulk enterprise pricing, demo booking, or asks our sales team to contact them directly:
  1. Warmly and helpfully provide product/pricing information from the knowledge base.
  2. Politely offer to connect them with our sales team or invite them to fill out the inquiry form.
  3. Set the intent tag to `lead_generation`.

WOOCOMMERCE ORDER TRACKING:
- When a user asks to track their order status, package delivery, or mentions an order number (#1234):
  1. Assist them with tracking and checking order status.
  2. Set the intent tag to `order_tracking`.

HIDDEN INTENT METADATA TAG:
- At the very end of your response, always append a hidden intent metadata tag in this exact format:
<!--INTENT:{\"intent\":\"lead_generation|support_ticket|human_handoff|order_tracking|general_qa\",\"name\":\"extracted_name_or_empty\",\"email\":\"extracted_email_or_empty\",\"phone\":\"extracted_phone_or_empty\",\"interest\":\"extracted_plugin_or_product_name_or_empty\",\"company\":\"extracted_company_or_empty\"}-->

CONVERSATION MEMORY:
- Use conversation history to resolve pronouns and follow-up requests ('more details', 'tell me more', 'why', 'how', 'continue') seamlessly.
";

		return trim( $system_message );
	}

	/**
	 * Verify all Markdown links and raw URLs in the AI's generated response against real WordPress database entities.
	 * Replaces non-existent 404 links with valid endpoints or strips broken link formatting.
	 *
	 * @param string $content Raw AI response text.
	 * @param array  $bot Bot settings.
	 * @return string Sanitized response text with verified links.
	 */
	public static function validate_and_sanitize_urls_in_content( $content, $bot = array() ) {
		if ( empty( $content ) || ! is_string( $content ) ) {
			return $content;
		}

		$home_url    = untrailingslashit( home_url() );
		$site_host   = wp_parse_url( $home_url, PHP_URL_HOST );
		$support_url = ! empty( $bot['support_url'] ) ? esc_url_raw( $bot['support_url'] ) : home_url();

		// Common placeholder hosts that LLMs hallucinate
		$dummy_hosts = array(
			'example.com',
			'example.org',
			'example.net',
			'yourdomain.com',
			'yoursite.com',
			'website.com',
			'yourwebsite.com',
			'mysite.com',
			'test.com',
			'placeholder.com',
			'company.com',
			'localhost',
			'127.0.0.1',
		);

		// Step 1: Process and validate all Markdown links: [Anchor Text](URL)
		$content = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)]+)\)/i',
			function ( $matches ) use ( $home_url, $site_host, $support_url, $dummy_hosts ) {
				$anchor = trim( $matches[1] );
				$url    = trim( $matches[2] );

				// Skip email, telephone, or hash anchor
				if ( preg_match( '/^(mailto:|tel:|#)/i', $url ) ) {
					return $matches[0];
				}

				$parsed   = wp_parse_url( $url );
				$url_host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';

				$is_dummy_host = ! empty( $url_host ) && in_array( $url_host, $dummy_hosts, true );
				$is_site_host  = empty( $url_host ) || ( ! empty( $site_host ) && strcasecmp( $url_host, $site_host ) === 0 );

				// If it is a genuine external URL on a non-dummy host (e.g. wa.me, google.com, github.com), keep it
				if ( ! empty( $url_host ) && ! $is_site_host && ! $is_dummy_host ) {
					return $matches[0];
				}

				// Normalize relative or dummy paths to full local site URLs
				$path      = isset( $parsed['path'] ) ? $parsed['path'] : '';
				$query     = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';
				$full_url  = home_url( '/' . ltrim( $path, '/' ) . $query );
				$clean_url = untrailingslashit( strtok( $full_url, '?#' ) );

				// 1. Check if it's the home URL
				if ( $clean_url === $home_url || $url === '/' || $url === '' || $path === '/' || $path === '' ) {
					return "[{$anchor}](" . home_url( '/' ) . ')';
				}

				// 2. Check if it matches configured support URL
				if ( untrailingslashit( $full_url ) === untrailingslashit( $support_url ) || untrailingslashit( $clean_url ) === untrailingslashit( $support_url ) ) {
					return "[{$anchor}]({$support_url})";
				}

				// 3. Check WooCommerce endpoints
				if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_permalink' ) ) {
					$wc_pages = array(
						'shop'      => wc_get_page_permalink( 'shop' ),
						'cart'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
						'checkout'  => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
						'myaccount' => wc_get_page_permalink( 'myaccount' ),
					);
					foreach ( $wc_pages as $wc_key => $wc_url ) {
						if ( ! empty( $wc_url ) && untrailingslashit( $clean_url ) === untrailingslashit( $wc_url ) ) {
							return "[{$anchor}](" . esc_url( $wc_url ) . ')';
						}
					}
				}

				// 4. Check if post/page/product ID exists for this URL
				$post_id = url_to_postid( $full_url );
				if ( $post_id > 0 ) {
					$status = get_post_status( $post_id );
					if ( $status === 'publish' ) {
						return "[{$anchor}](" . esc_url( get_permalink( $post_id ) ) . ')';
					}
				}

				// 5. Check by slug / path
				$clean_path = trim( (string) $path, '/' );
				if ( ! empty( $clean_path ) ) {
					$page_obj = get_page_by_path( $clean_path, OBJECT, array( 'page', 'post', 'product' ) );
					if ( $page_obj && $page_obj->post_status === 'publish' ) {
						return "[{$anchor}](" . esc_url( get_permalink( $page_obj->ID ) ) . ')';
					}

					// Check by exact slug match
					$slug = basename( $clean_path );
					if ( ! empty( $slug ) ) {
						$slug_posts = get_posts(
							array(
								'name'           => sanitize_title( $slug ),
								'post_type'      => array( 'page', 'post', 'product' ),
								'post_status'    => 'publish',
								'posts_per_page' => 1,
							)
						);
						if ( ! empty( $slug_posts ) && $slug_posts[0] instanceof \WP_Post ) {
							return "[{$anchor}](" . esc_url( get_permalink( $slug_posts[0]->ID ) ) . ')';
						}
					}
				}

				// 6. URL DOES NOT EXIST on this website (Hallucinated / Broken URL)
				// If user/bot was referring to support or contact, link to official support URL
				if ( preg_match( '/\b(support|contact|help|helpdesk|ticket|agent|specialist|reach out|get in touch)\b/i', $anchor . ' ' . $clean_path ) ) {
					return "[{$anchor}]({$support_url})";
				}

				// Fallback: Strip the broken/hallucinated markdown link so the visitor is never given a 404 or wrong URL
				return $anchor;
			},
			$content
		);

		// Step 2: Also sanitize standalone raw internal or dummy URLs that are not part of markdown links
		$dummy_regex  = implode( '|', array_map( 'preg_quote', $dummy_hosts ) );
		$site_regex   = preg_quote( $site_host ?: '', '/' );
		$host_pattern = ! empty( $site_regex ) ? "({$site_regex}|{$dummy_regex})" : "({$dummy_regex})";

		$content = preg_replace_callback(
			'/(?<!\(|\[)(https?:\/\/' . $host_pattern . '[^\s<>"\'\)]+)/i',
			function ( $raw_matches ) use ( $home_url, $support_url ) {
				$raw_url   = trim( $raw_matches[1], '.,;!?' );
				$clean_raw = untrailingslashit( strtok( $raw_url, '?#' ) );

				if ( $clean_raw === $home_url || untrailingslashit( $raw_url ) === untrailingslashit( $support_url ) ) {
					return $raw_url;
				}

				$pid = url_to_postid( $raw_url );
				if ( $pid > 0 && get_post_status( $pid ) === 'publish' ) {
					return esc_url( get_permalink( $pid ) );
				}

				$path = trim( (string) wp_parse_url( $raw_url, PHP_URL_PATH ), '/' );
				if ( ! empty( $path ) ) {
					$page_obj = get_page_by_path( $path, OBJECT, array( 'page', 'post', 'product' ) );
					if ( $page_obj && $page_obj->post_status === 'publish' ) {
						return esc_url( get_permalink( $page_obj->ID ) );
					}
				}

				// If the raw URL does not exist on the site, point support-related to support_url or strip
				if ( preg_match( '/\b(support|contact|help|ticket)\b/i', $path ) ) {
					return $support_url;
				}

				return '';
			},
			$content
		);

		return $content;
	}

	/**
	 * Extract and parse dynamic AI intent metadata from the LLM output.
	 *
	 * @param string $content Raw AI output.
	 * @param string $prompt User message.
	 * @return array{clean_message: string, intent: string, email: string, phone: string, confidence: float}
	 */
	public static function parse_dynamic_ai_intent( $content, $prompt = '' ) {
		$intent             = 'general_qa';
		$extracted_name     = '';
		$extracted_email    = '';
		$extracted_phone    = '';
		$extracted_interest = '';
		$extracted_company  = '';
		$confidence         = 0.8;

		// 1. Check for structured <!--INTENT:{...}--> tag in AI output
		if ( preg_match( '/<!--INTENT:\s*({.*?})\s*-->/s', $content, $matches ) ) {
			$json_str = trim( $matches[1] );
			$parsed   = json_decode( $json_str, true );
			if ( is_array( $parsed ) ) {
				if ( ! empty( $parsed['intent'] ) ) {
					$intent = sanitize_key( $parsed['intent'] );
				}
				if ( ! empty( $parsed['name'] ) && 'extracted_name_or_empty' !== $parsed['name'] ) {
					$extracted_name = sanitize_text_field( $parsed['name'] );
				}
				if ( ! empty( $parsed['email'] ) && is_email( $parsed['email'] ) ) {
					$extracted_email = sanitize_email( $parsed['email'] );
				}
				if ( ! empty( $parsed['phone'] ) && 'extracted_phone_or_empty' !== $parsed['phone'] ) {
					$extracted_phone = sanitize_text_field( $parsed['phone'] );
				}
				if ( ! empty( $parsed['interest'] ) && 'extracted_plugin_or_product_name_or_empty' !== $parsed['interest'] ) {
					$extracted_interest = sanitize_text_field( $parsed['interest'] );
				}
				if ( ! empty( $parsed['company'] ) && 'extracted_company_or_empty' !== $parsed['company'] ) {
					$extracted_company = sanitize_text_field( $parsed['company'] );
				}
				if ( isset( $parsed['confidence'] ) ) {
					$confidence = floatval( $parsed['confidence'] );
				}
			}
			// Strip the hidden tag so visitor never sees it in chat
			$content = str_replace( $matches[0], '', $content );
		}

		// 2. Extract email from user prompt if provided
		if ( empty( $extracted_email ) && preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $prompt, $email_matches ) ) {
			$extracted_email = sanitize_email( $email_matches[0] );
		}

		// 3. Extract phone from user prompt if provided
		if ( empty( $extracted_phone ) && preg_match( '/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $prompt, $phone_matches ) ) {
			$extracted_phone = sanitize_text_field( $phone_matches[0] );
		}

		// 4. Extract name from user prompt if provided (e.g. "My name is John Doe", "I am Jane Doe", "Name: Alex")
		if ( empty( $extracted_name ) && preg_match( '/\b(?:my name is|i am|this is|i\'m|name\s*:)\s+([A-Za-z\s]{2,30})(?:[,\.\n]|$)/i', $prompt, $name_matches ) ) {
			$candidate_name = trim( $name_matches[1] );
			if ( ! preg_match( '/\b(interested|looking|asking|wondering|here|ready|writing|calling|having|facing|using)\b/i', $candidate_name ) ) {
				$extracted_name = sanitize_text_field( $candidate_name );
			}
		}

		// 5. Extract plugin or product name from prompt if mentioned
		if ( empty( $extracted_interest ) && preg_match( '/\b(?:plugin|theme|product|for|about|with)\s+([A-Za-z0-9\s\-]{3,35})(?:[,\.\n]|$)/i', $prompt, $prod_matches ) ) {
			$candidate_prod = trim( $prod_matches[1] );
			if ( ! preg_match( '/\b(help|support|error|issue|problem|bug|question|details|pricing)\b/i', $candidate_prod ) ) {
				$extracted_interest = sanitize_text_field( $candidate_prod );
			}
		}

		// 6. Fallback or override heuristic intent classifier
		$rule_class = self::classify_user_intent( $prompt );
		if ( ! empty( $rule_class['category'] ) && 'greeting' === $rule_class['category'] ) {
			// Greetings like "hi" or "hello" are strictly general_qa (never lead_generation)
			$intent     = 'general_qa';
			$confidence = 0.99;
		} elseif ( 'general_qa' === $intent ) {
			if ( 'general_qa' !== $rule_class['intent'] ) {
				$intent     = $rule_class['intent'];
				$confidence = $rule_class['confidence'];
			}
		} elseif ( 'lead_generation' === $intent ) {
			// Override only if prompt is clearly support, bug, or order tracking
			if ( in_array( $rule_class['intent'], array( 'support_ticket', 'order_tracking', 'human_handoff' ), true ) ) {
				$intent     = $rule_class['intent'];
				$confidence = $rule_class['confidence'];
			}
		}

		return array(
			'clean_message' => trim( $content ),
			'intent'        => $intent,
			'name'          => $extracted_name,
			'email'         => $extracted_email,
			'phone'         => $extracted_phone,
			'interest'      => $extracted_interest,
			'company'       => $extracted_company,
			'confidence'    => $confidence,
		);
	}

	/**
	 * Get optimized memory and conversation history
	 */
	private function get_optimized_memory( $session_id, $prompt, $system_message ) {
		try {

			$rag_context = '';

			if ( ! class_exists( 'DCTC_AI_Memory_Optimizer' ) ) {
				require_once DCTC_PLUGIN_DIR . 'includes/ai/class-dctc-ai-memory-optimizer.php';
			}

			$memory_optimizer = DCTC_AI_Memory_Optimizer::get_instance();

			$optimized_memory = $memory_optimizer->build_optimized_context(
				$session_id,
				$prompt,
				$rag_context
			);

			$memory_text = $memory_optimizer->format_for_prompt(
				$optimized_memory
			);

			if ( ! empty( $memory_text ) ) {
				$system_message .= "\n\nMEMORY CONTEXT:\n";
				$system_message .= $memory_text;
			}

			/*
			 * Add recent conversation
			 */
			$conversation_history = $this->get_recent_conversation(
				$session_id,
				10
			);

			if ( ! empty( $conversation_history ) ) {
				$classification    = self::classify_user_intent( $prompt );
				$is_fresh_greeting = ( ! empty( $classification['category'] ) && 'greeting' === $classification['category'] );

				$system_message .= "\n\nCONVERSATION HISTORY:\n";
				$system_message .= $conversation_history;

				if ( $is_fresh_greeting ) {
					$system_message .= "\n\nCURRENT USER INTENT: The user just sent a greeting ('" . esc_html( $prompt ) . "'). Greet them pleasantly and ask how you can help them today. Do NOT assume they are continuing a previous purchase, quotation, or support ticket inquiry.\n";
				} else {
					$system_message .= "\n\nFOLLOW-UP RULES:
- If user asks 'why', 'how', 'who', 'when', 'where', 'which', 'what about', 'tell me more', 'continue', 'can you explain', assume they are referring to the previous topic.
- Resolve pronouns such as 'it', 'that', 'this', 'they' using the conversation history.
- Never ignore previous messages in the same session.
";
				}
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI Memory Build Context Error: ' . $e->getMessage() );
		}

		return array(
			'system_message' => $system_message,
		);
	}

	/**
	 * Call AI API with automatic failover support
	 */
	private function call_ai_api( $prompt, $system_message, $provider, $model_id, $bot, $models = array(), $attachments = array() ) {
		$options = array(
			'temperature' => isset( $bot['temperature'] ) ? (float) $bot['temperature'] : 0.7,
			'max_tokens'  => isset( $bot['max_tokens'] ) ? (int) $bot['max_tokens'] : 500,
			'attachments' => $attachments,
		);

		$enable_failover   = ! isset( $bot['enable_failover'] ) || (bool) $bot['enable_failover'];
		$fallback_provider = '';
		$fallback_model    = '';

		if ( $enable_failover ) {
			$fallback_provider = ! empty( $bot['fallback_provider'] ) ? $bot['fallback_provider'] : '';
			$fallback_model    = ! empty( $bot['fallback_model'] ) ? $bot['fallback_model'] : ( isset( $models[ $fallback_provider ] ) ? $models[ $fallback_provider ] : '' );
		}

		return DCTC_AI_Provider_Manager::get_instance()->chat_with_fallback(
			$prompt,
			$system_message,
			$provider,
			$model_id,
			$options,
			$fallback_provider,
			$fallback_model
		);
	}

	/**
	 * Save conversation to database
	 */
	private function save_conversation( $prompt, $ai_message, $session_id, $provider, $model_id, $bot, $email, $sources = array(), $extra = array() ) {
		// Save to database if enabled or if ticket is attached
		$save_enabled = ! isset( $bot['save_chat'] ) || (bool) $bot['save_chat'];
		if ( $save_enabled || class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			if ( class_exists( 'DCTC_AI_DB' ) ) {
				$db = new DCTC_AI_DB();
				$db->dctc_ai_save_message( $prompt, $ai_message, $session_id, $provider, $model_id, $email, $sources, $extra );
			}
		}

		// Keep connected Support Ticket updated in real-time
		if ( class_exists( 'DCTC_Support_Ticket_Service' ) && ! empty( $session_id ) ) {
			$ticket = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
			if ( $ticket && ! empty( $ticket['id'] ) ) {
				global $wpdb;
				$table_tickets  = $wpdb->prefix . 'dctc_support_tickets';
				$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

				$ticket_updates = array(
					'customer_last_seen_at' => current_time( 'mysql' ),
					'updated_at'            => current_time( 'mysql' ),
				);
				if ( ! empty( $email ) && empty( $ticket['customer_email'] ) ) {
					$ticket_updates['customer_email'] = sanitize_email( $email );
				}
				$wpdb->update(
					$table_tickets,
					$ticket_updates,
					array( 'id' => (int) $ticket['id'] )
				);

				// Sync ticket messages meta from session table
				$session_content = $wpdb->get_var(
					$wpdb->prepare( "SELECT content FROM `$table_sessions` WHERE session_id = %s", $session_id )
				);
				if ( ! empty( $session_content ) ) {
					$all_msgs = json_decode( $session_content, true );
					if ( is_array( $all_msgs ) ) {
						DCTC_Support_Ticket_Service::update_ticket_meta( $ticket['id'], '_dctc_ticket_messages', $all_msgs );
					}
				}
			}
		}
	}

	/**
	 * Get formatted messages from database
	 */
	private function get_formatted_messages( $session_id ) {
		$formatted_messages = array();

		if ( ! class_exists( 'DCTC_AI_DB' ) ) {
			return $formatted_messages;
		}

		try {
			global $wpdb;

			// Use prepared statement properly
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe table prefix, direct query required.
			$existing_messages = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT content FROM {$wpdb->prefix}dctc_ai_sessions WHERE session_id = %s",
					$session_id
				)
			);

			if ( $existing_messages ) {
				$complete_messages = json_decode( $existing_messages, true );

				if ( is_array( $complete_messages ) ) {
					foreach ( $complete_messages as $msg ) {
						if ( isset( $msg['role'] ) && isset( $msg['content'] ) ) {
							$show_form = null;
							if ( isset( $msg['show_form'] ) && is_array( $msg['show_form'] ) ) {
								$show_form = array(
									'form_type'   => ! empty( $msg['show_form']['form_type'] ) ? sanitize_text_field( $msg['show_form']['form_type'] ) : 'lead_generate',
									'show'        => ! empty( $msg['show_form']['show'] ),
									'form_filled' => ! empty( $msg['show_form']['form_filled'] ),
								);
							}
							$formatted_messages[] = array(
								'role'        => ( $msg['role'] === 'assistant' ) ? 'bot' : 'user',
								'sender_type' => isset( $msg['sender_type'] ) ? $msg['sender_type'] : ( $msg['role'] === 'assistant' ? 'ai_agent' : 'customer' ),
								'sender_name' => isset( $msg['sender_name'] ) ? $msg['sender_name'] : ( $msg['role'] === 'assistant' ? 'AI Assistant' : 'Customer' ),
								'created_at'  => isset( $msg['created_at'] ) ? $msg['created_at'] : '',
								'content'     => $msg['content'],
								'sources'     => isset( $msg['sources'] ) && is_array( $msg['sources'] ) ? $msg['sources'] : array(),
								'show_form'   => $show_form,
							);
						}
					}
				}
			}
		} catch ( Exception $e ) {
			self::log_debug( 'Dragwyb AI AI DB Messages Retrieval Error: ' . $e->getMessage() );
		}

		return $formatted_messages;
	}

	/**
	 * Detect if visitor prompt expresses intent to speak with a human agent.
	/**
	 * Classify user intent: 'human_handoff', 'support_ticket', 'lead_generation', or 'general_qa'.
	 *
	 * @param string $prompt User message.
	 * @return array<string, mixed>
	 */
	public static function classify_user_intent( $prompt ) {
		if ( empty( $prompt ) ) {
			return array(
				'intent'     => 'general_qa',
				'category'   => 'general',
				'confidence' => 0.0,
			);
		}

		// 0. High-confidence patterns for Greetings and casual conversation (strictly general_qa, never lead generation)
		if ( preg_match( '/^(hi+|hello+|hey+|good\s*(?:morning|afternoon|evening|day)|howdy|hola|greetings|what\'s\s*up|sup)\b/i', trim( $prompt ) ) ) {
			return array(
				'intent'     => 'general_qa',
				'category'   => 'greeting',
				'confidence' => 0.99,
			);
		}

		// 1. High-confidence patterns for WooCommerce Order Tracking
		$patterns_order = array(
			'/\b(track\s*(?:my\s*)?order|track\s*order|order\s*tracking|track\s*(?:my\s*)?package|track\s*(?:my\s*)?shipment|order\s*status|where\s*is\s*my\s*order|check\s*my\s*order|find\s*my\s*order|tracking\s*number|delivery\s*status)\b/i',
			'/\b(track my order status|where is my order package|check my order status|find my order delivery)\b/i',
			'/\b(track my shipment status|check package delivery status|tracking number for my order|status of my order)\b/i',
			'/^\s*#?\d{2,8}\s*$/',
			'/\b(?:order|tracking)\s*(?:id|no|number|#)?\s*#?\d{2,8}\b/i',
		);

		foreach ( $patterns_order as $pattern ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return array(
					'intent'     => 'order_tracking',
					'category'   => 'order',
					'confidence' => 0.95,
				);
			}
		}

		// 2. High-confidence patterns for Human Handoff requests
		$patterns_handoff = array(
			'/\b(talk to a human|talk to a real person|talk to a human agent|talk to a live agent|talk to an agent|talk to customer support)\b/i',
			'/\b(speak with a human|speak to a human|speak with a live agent|speak to an agent|speak to a support representative)\b/i',
			'/\b(connect with a human|connect me with a live agent|connect to a human agent|connect with customer support)\b/i',
			'/\b(transfer me to a human|transfer me to a live agent|transfer to an agent|escalate to a support manager)\b/i',
			'/\b(live chat with human|live chat with an agent|chat with a human specialist|switch to a human agent)\b/i',
			'/\b(call human support|phone support team|chat on whatsapp with support|connect to whatsapp support)\b/i',
			'/\b(agent please|human please|connect with real agent|speak to human|talk to human)\b/i',
		);

		foreach ( $patterns_handoff as $pattern ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return array(
					'intent'     => 'human_handoff',
					'category'   => 'support',
					'confidence' => 0.95,
				);
			}
		}

		// 3. High-confidence patterns for Support Ticket & Technical Issues (Bugs, Features, Conflicts, etc.)
		$patterns_support = array(
			'/\b(open a support ticket|create a support ticket|submit a support ticket|file a support ticket|raise a support ticket|create ticket|open ticket|support ticket|ticket)\b/i',
			'/\b(need technical support|troubleshoot this problem|having a technical issue|system is not working|not working properly|broken feature|bug in the plugin|bug|glitch|defect)\b/i',
			'/\b(item arrived damaged|received a broken item|product is defective|claim warranty for my item|damaged product|broken product|product issue|product conflict|plugin conflict|theme conflict)\b/i',
			'/\b(request a refund|want a refund|return my ordered item|cancel my placed order|cancel order|get a refund|refund policy|refund)\b/i',
			'/\b(billing charge issue|failed payment|incorrect invoice amount|payment deduction error|payment failed|charged twice)\b/i',
			'/\b(need help with error|error in|facing an issue|facing problem|having problem with|cannot log in|login problem|account issue|feature request)\b/i',
			'/\b(i need support|contact support|customer support|support team|help desk|ticket assistance|help me with|how to fix|fix this)\b/i',
		);

		foreach ( $patterns_support as $pattern ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return array(
					'intent'     => 'support_ticket',
					'category'   => 'troubleshooting',
					'confidence' => 0.90,
				);
			}
		}

		// 4. High-confidence patterns for Product Purchase, Buying, Sales Quotes & Custom Inquiries
		$patterns_lead = array(
			'/\b(want to buy|like to buy|ready to buy|wish to buy|looking to buy|interested in buying|interested to buy|plan to buy)\b/i',
			'/\b(want to purchase|like to purchase|ready to purchase|looking to purchase|interested in purchasing|interested to purchase|plan to purchase)\b/i',
			'/\b(how to buy|where to buy|how can i buy|can i buy|how do i purchase|can i purchase|buy this product|purchase this product|order this product)\b/i',
			'/\b(request a custom quote|get a price estimate|inquire about bulk pricing|enterprise plan inquiry|custom quote|get a quote|need a quote)\b/i',
			'/\b(schedule a demo call|book a consultation call|contact your sales team|hire your team for project|schedule a demo|book a demo|request a demo)\b/i',
			'/\b(contact me for purchase|reach out to me to buy|sales consultation|have sales contact me|have a representative call me)\b/i',
		);

		foreach ( $patterns_lead as $pattern ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return array(
					'intent'     => 'lead_generation',
					'category'   => 'sales',
					'confidence' => 0.88,
				);
			}
		}

		return array(
			'intent'     => 'general_qa',
			'category'   => 'general',
			'confidence' => 0.5,
		);
	}

	/**
	 * Detect if the message is requesting WooCommerce order tracking or status lookup.
	 *
	 * @param string $prompt
	 * @return bool
	 */
	public static function detect_order_tracking_intent( $prompt ) {
		$classification = self::classify_user_intent( $prompt );
		return 'order_tracking' === $classification['intent'];
	}

	/**
	 * Detect if the message indicates an explicit lead capture / quote inquiry intent.
	 *
	 * @param string $prompt
	 * @return bool
	 */
	public static function detect_lead_intent( $prompt ) {
		$classification = self::classify_user_intent( $prompt );
		return 'lead_generation' === $classification['intent'];
	}

	/**
	 * Detect if the message indicates an explicit human handoff request.
	 *
	 * @param string $prompt
	 * @return bool
	 */
	public static function detect_explicit_human_handoff( $prompt ) {
		$classification = self::classify_user_intent( $prompt );
		return 'human_handoff' === $classification['intent'];
	}

	/**
	 * Detect if the message indicates a human handoff / escalation request.
	 *
	 * @param string $prompt
	 * @return bool
	 */
	public static function detect_human_handoff_intent( $prompt ) {
		$classification = self::classify_user_intent( $prompt );
		return in_array( $classification['intent'], array( 'human_handoff', 'support_ticket' ), true );
	}

	/**
	 * Check if current time falls within configured business hours.
	 *
	 * @param array $bot_settings
	 * @return bool
	 */
	public static function is_within_business_hours( $bot_settings ) {
		if ( empty( $bot_settings['enable_business_hours'] ) ) {
			return true;
		}

		try {
			$tz_str = ! empty( $bot_settings['business_hours_timezone'] ) ? $bot_settings['business_hours_timezone'] : ( function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : 'UTC' );
			$tz     = new DateTimeZone( $tz_str ?: 'UTC' );
			$now    = new DateTime( 'now', $tz );

			$day_map          = array(
				1 => 'mon',
				2 => 'tue',
				3 => 'wed',
				4 => 'thu',
				5 => 'fri',
				6 => 'sat',
				7 => 'sun',
			);
			$current_day_num  = (int) $now->format( 'N' );
			$current_day_slug = $day_map[ $current_day_num ] ?? 'mon';

			$allowed_days = ! empty( $bot_settings['business_hours_days'] ) && is_array( $bot_settings['business_hours_days'] )
				? $bot_settings['business_hours_days']
				: array( 'mon', 'tue', 'wed', 'thu', 'fri' );

			if ( ! in_array( $current_day_slug, $allowed_days, true ) ) {
				return false;
			}

			$start_str = ! empty( $bot_settings['business_hours_start'] ) ? $bot_settings['business_hours_start'] : '09:00';
			$end_str   = ! empty( $bot_settings['business_hours_end'] ) ? $bot_settings['business_hours_end'] : '18:00';

			$current_time = $now->format( 'H:i' );
			return ( $current_time >= $start_str && $current_time <= $end_str );
		} catch ( \Throwable $e ) {
			return true;
		}
	}

	/**
	 * Build human handoff action buttons with contextual WhatsApp link.
	 *
	 * @param array  $bot
	 * @param string $prompt
	 * @param bool   $is_online
	 * @return array
	 */
	public static function build_handoff_action_buttons( $bot, $prompt, $is_online = true ) {
		$action_buttons  = array();
		$parent_settings = get_option( 'dctc_settings', array() );

		$wa_num     = ! empty( $bot['handoff_whatsapp_number'] ) ? $bot['handoff_whatsapp_number'] : ( ! empty( $parent_settings['whatsapp_value'] ) ? $parent_settings['whatsapp_value'] : '' );
		$phone_num  = ! empty( $bot['handoff_phone_number'] ) ? $bot['handoff_phone_number'] : ( ! empty( $parent_settings['phone_value'] ) ? $parent_settings['phone_value'] : '' );
		$email_addr = ! empty( $bot['handoff_email_address'] ) ? $bot['handoff_email_address'] : ( ! empty( $parent_settings['email_value'] ) ? $parent_settings['email_value'] : get_option( 'admin_email' ) );

		$safe_prompt = substr( wp_strip_all_tags( $prompt ), 0, 150 );

		if ( ! empty( $wa_num ) ) {
			$clean_phone = preg_replace( '/[^0-9]/', '', $wa_num );
			$template    = ! empty( $bot['handoff_template'] )
				? $bot['handoff_template']
				: 'Hi! I was chatting with your AI assistant on {page_url} regarding: "{summary}". My question: "{question}".';

			$wa_msg = str_replace(
				array( '{question}', '{summary}', '{page_url}', '{visitor_name}' ),
				array( $safe_prompt, $safe_prompt, home_url(), 'Visitor' ),
				$template
			);

			$action_buttons[] = array(
				'id'     => 'btn_wa_handoff',
				'label'  => __( '💬 Chat on WhatsApp', 'dragwyb-click-to-chat' ),
				'url'    => 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $wa_msg ),
				'target' => '_blank',
				'type'   => 'whatsapp',
			);
		}

		if ( ! empty( $phone_num ) && $is_online ) {
			$action_buttons[] = array(
				'id'     => 'btn_phone_handoff',
				'label'  => __( '📞 Call Human Agent', 'dragwyb-click-to-chat' ),
				'url'    => 'tel:' . preg_replace( '/[^0-9+]/', '', $phone_num ),
				'target' => '_self',
				'type'   => 'phone',
			);
		}

		if ( ! empty( $email_addr ) ) {
			$action_buttons[] = array(
				'id'     => 'btn_email_handoff',
				'label'  => __( '✉️ Email Support Team', 'dragwyb-click-to-chat' ),
				'url'    => 'mailto:' . antispambot( $email_addr ) . '?subject=' . rawurlencode( __( 'Customer Inquiry from AI Chat', 'dragwyb-click-to-chat' ) ) . '&body=' . rawurlencode( $safe_prompt ),
				'target' => '_blank',
				'type'   => 'email',
			);
		}

		return $action_buttons;
	}

	/**
	 * Save response and return
	 */
	private function save_and_respond( $message, $session_id, $bot, $email, $prompt, $action_buttons = array(), $is_handoff = false ) {
		if ( isset( $bot['save_chat'] ) && (bool) $bot['save_chat'] ) {
			if ( class_exists( 'DCTC_AI_DB' ) ) {
				$db          = new DCTC_AI_DB();
				$source_type = $is_handoff ? 'human-handoff' : 'knowledge-base';
				$model_used  = $is_handoff ? 'handoff' : 'no-data';
				$db->dctc_ai_save_message( $prompt, $message, $session_id, $source_type, $model_used, $email );
			}
		}

		$ticket_info  = $this->get_session_ticket_info( $session_id );
		$has_ticket   = ! empty( $ticket_info );
		$control_mode = ! empty( $ticket_info['control_mode'] ) ? $ticket_info['control_mode'] : 'ai';

		return new \WP_REST_Response(
			array(
				'success'        => true,
				'message'        => $message,
				'session_id'     => $session_id,
				'from_kb'        => false,
				'is_handoff'     => $is_handoff,
				'action_buttons' => $action_buttons,
				'has_ticket'     => $has_ticket,
				'ticket'         => $ticket_info,
				'control_mode'   => $control_mode,
			),
			200
		);
	}

	/**
	 * Get recent conversation history.
	 *
	 * @param string $session_id Session ID.
	 * @param int    $limit Number of messages.
	 * @return string
	 */
	private function get_recent_conversation( $session_id, $limit = 10 ) {
		global $wpdb;

		if ( empty( $session_id ) ) {
			return '';
		}

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		if ( isset( $settings['chatbot']['memory_window_size'] ) && intval( $settings['chatbot']['memory_window_size'] ) > 0 ) {
			$limit = min( 50, max( 2, intval( $settings['chatbot']['memory_window_size'] ) ) );
		}

		$table = esc_sql( $wpdb->prefix . 'dctc_ai_sessions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct database query on custom table.
		$messages_json = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT content FROM {$table} WHERE session_id = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is dynamic but safe.
				$session_id
			)
		);

		if ( empty( $messages_json ) ) {
			return '';
		}

		$messages = json_decode( $messages_json, true );

		if ( ! is_array( $messages ) ) {
			return '';
		}

		$messages = array_slice(
			$messages,
			-$limit
		);

		$history = '';

		foreach ( $messages as $message ) {

			if ( empty( $message['content'] ) ) {
				continue;
			}

			$role = (
				isset( $message['role'] ) &&
				$message['role'] === 'assistant'
			)
				? 'Assistant'
				: 'User';

			$history .= sprintf(
				"%s: %s\n",
				$role,
				trim( $message['content'] )
			);
		}

		return trim( $history );
	}

	/**
	 * Sync session state and messages for real-time live support.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	/**
	 * Update ticket ai_response meta indicator.
	 *
	 * @param string $session_id
	 * @param bool   $is_responding
	 * @return void
	 */
	public function set_ticket_ai_responding( $session_id, $is_responding ) {
		if ( empty( $session_id ) || ! class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			return;
		}

		$ticket = DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id );
		if ( $ticket && ! empty( $ticket['id'] ) ) {
			$val = $is_responding ? 1 : 0;
			DCTC_Support_Ticket_Service::update_ticket_meta_batch(
				(int) $ticket['id'],
				array(
					'ai_response'         => $val,
					'ai_response_waiting' => $val,
				)
			);
		}
	}

	/**
	 * Get ticket info for a session.
	 *
	 * @param string $session_id Session ID.
	 * @return array|null Ticket array or null
	 */
	public function get_session_ticket_info( $session_id ) {
		if ( empty( $session_id ) ) {
			return null;
		}

		$ticket = class_exists( 'DCTC_Support_Ticket_Service' )
			? DCTC_Support_Ticket_Service::get_ticket_by_session_id( $session_id )
			: null;

		if ( ! $ticket ) {
			return null;
		}

		$agent_name = ( ! empty( $ticket['agent_name'] ) && __( 'Unassigned', 'dragwyb-click-to-chat' ) !== $ticket['agent_name'] )
			? $ticket['agent_name']
			: '';

		$ai_waiting = ! empty( $ticket['ai_response_waiting'] ) || ! empty( $ticket['ai_response'] );

		return array(
			'id'                  => (int) $ticket['id'],
			'ticket_number'       => (int) $ticket['ticket_number'],
			'status'              => $ticket['status'],
			'control_mode'        => ! empty( $ticket['control_mode'] ) ? $ticket['control_mode'] : 'ai',
			'agent_name'          => $agent_name,
			'ai_response_waiting' => $ai_waiting,
			'ai_response'         => $ai_waiting,
		);
	}

	/**
	 * Sync session state and messages for real-time live support.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public function sync_session( $request ) {
		global $wpdb;
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		if ( empty( $session_id ) ) {
			return new \WP_REST_Response(
				array(
					'success'    => false,
					'message'    => 'session_id required',
					'has_ticket' => false,
				),
				400
			);
		}

		$table_sessions = $wpdb->prefix . 'dctc_ai_sessions';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$session = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table_sessions` WHERE session_id = %s", $session_id ), ARRAY_A );

		$ticket_info = $this->get_session_ticket_info( $session_id );
		$has_ticket  = ! empty( $ticket_info );
		$ai_waiting  = ! empty( $ticket_info['ai_response_waiting'] );

		if ( $has_ticket && ! empty( $ticket_info['id'] ) ) {
			$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
			$wpdb->update(
				$table_tickets,
				array( 'customer_last_seen_at' => current_time( 'mysql' ) ),
				array( 'id' => (int) $ticket_info['id'] )
			);
		}

		if ( ! $session ) {
			return new \WP_REST_Response(
				array(
					'success'             => true,
					'session_id'          => $session_id,
					'control_mode'        => ! empty( $ticket_info['control_mode'] ) ? $ticket_info['control_mode'] : 'ai',
					'messages'            => array(),
					'has_ticket'          => $has_ticket,
					'ticket'              => $ticket_info,
					'ai_response_waiting' => $ai_waiting,
					'ai_response'         => $ai_waiting,
				),
				200
			);
		}

		$messages = ! empty( $session['content'] ) ? json_decode( $session['content'], true ) : array();
		$messages = is_array( $messages ) ? $messages : array();

		// Clean internal system notices from client chat stream
		$client_messages = array();
		foreach ( $messages as $msg ) {
			if ( ! is_array( $msg ) ) {
				continue;
			}
			if ( ( isset( $msg['role'] ) && 'system' === $msg['role'] ) || ( isset( $msg['sender_type'] ) && 'system' === $msg['sender_type'] ) ) {
				continue;
			}
			$client_messages[] = $msg;
		}

		// Check if the latest message was an assistant lead form prompt
		$has_lead_prompt = false;
		if ( ! empty( $client_messages ) ) {
			$last_msg = end( $client_messages );
			if ( is_array( $last_msg ) && ! empty( $last_msg['content'] ) ) {
				$is_bot = ( isset( $last_msg['role'] ) && in_array( $last_msg['role'], array( 'assistant', 'bot' ), true ) )
					|| ( isset( $last_msg['sender_type'] ) && in_array( $last_msg['sender_type'], array( 'ai_agent', 'bot' ), true ) );
				if ( $is_bot && false !== stripos( (string) $last_msg['content'], 'form below' ) ) {
					$has_lead_prompt = true;
				}
			}
		}

		$control_mode = ! empty( $ticket_info['control_mode'] ) ? $ticket_info['control_mode'] : ( ! empty( $session['control_mode'] ) ? $session['control_mode'] : 'ai' );

		return new \WP_REST_Response(
			array(
				'success'             => true,
				'session_id'          => $session_id,
				'control_mode'        => $control_mode,
				'messages'            => $client_messages,
				'has_ticket'          => $has_ticket,
				'ticket'              => $ticket_info,
				'ai_response_waiting' => $ai_waiting,
				'ai_response'         => $ai_waiting,
				'updated_at'          => $session['updated_at'] ?? current_time( 'mysql' ),
			),
			200
		);
	}
}
