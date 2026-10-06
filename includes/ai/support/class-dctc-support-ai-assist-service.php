<?php
/**
 * DCTC Support AI Assist Service
 *
 * Provides AI-powered summarization and reply suggestion tools for support agents.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_AI_Assist_Service
 */
class DCTC_Support_AI_Assist_Service {

	/**
	 * Generate an AI summary for a support ticket.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return string|WP_Error
	 */
	public static function generate_summary( $ticket_id ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'dragwyb-click-to-chat' ) );
		}

		$messages = ! empty( $ticket['messages'] ) ? $ticket['messages'] : array();
		if ( empty( $messages ) ) {
			return new WP_Error( 'no_messages', __( 'No conversation history available to summarize.', 'dragwyb-click-to-chat' ) );
		}

		$conversation_text = '';
		foreach ( $messages as $msg ) {
			$role               = isset( $msg['role'] ) && 'assistant' === $msg['role'] ? 'Agent/AI' : 'Customer';
			$conversation_text .= $role . ': ' . ( $msg['content'] ?? '' ) . "\n";
		}

		$system_prompt = "You are an expert customer support analyst. Summarize the following customer support conversation concisely into 2-3 clear sentences. Highlight the core customer issue, what actions have been taken, and the pending question or resolution needed.\n\nConversation History:\n" . $conversation_text;

		$response = self::call_llm( $system_prompt, 'Generate a concise summary of this support ticket.' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$summary_clean = sanitize_textarea_field( trim( $response ) );

		// Update ticket meta and touch updated_at
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'dctc_support_tickets';
		if ( class_exists( 'DCTC_Support_Ticket_Service' ) ) {
			DCTC_Support_Ticket_Service::update_ticket_meta( $ticket_id, 'ai_summary', $summary_clean );
		}
		$wpdb->update(
			$table_tickets,
			array( 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => absint( $ticket_id ) )
		);

		DCTC_Support_Event_Service::log_event(
			$ticket_id,
			'ai_summary_generated',
			'ai',
			0,
			'AI Assistant',
			null,
			$summary_clean
		);

		return $summary_clean;
	}

	/**
	 * Suggest an agent draft reply based on conversation history and knowledge context.
	 *
	 * @param int $ticket_id Ticket ID.
	 * @return string|WP_Error
	 */
	public static function suggest_reply( $ticket_id ) {
		$ticket = DCTC_Support_Ticket_Service::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return new WP_Error( 'ticket_not_found', __( 'Ticket not found.', 'dragwyb-click-to-chat' ) );
		}

		$messages  = ! empty( $ticket['messages'] ) ? $ticket['messages'] : array();
		$site_name = get_bloginfo( 'name' );

		$conversation_text = '';
		foreach ( $messages as $msg ) {
			$role               = isset( $msg['role'] ) && 'assistant' === $msg['role'] ? 'Staff' : 'Customer';
			$conversation_text .= $role . ': ' . ( $msg['content'] ?? '' ) . "\n";
		}

		$system_prompt = sprintf(
			"You are an expert, empathetic, and professional support agent representing %s. Based on the conversation history below, draft a polite, helpful, and concise response to the customer's latest message. Do not include markdown email headers or placeholders. Speak naturally and offer clear solutions or next steps.\n\nConversation History:\n%s",
			esc_html( $site_name ),
			$conversation_text
		);

		$response = self::call_llm( $system_prompt, 'Draft a helpful support response.' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return trim( $response );
	}

	/**
	 * Call configured LLM provider.
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_prompt   User prompt.
	 * @return string|WP_Error
	 */
	private static function call_llm( $system_prompt, $user_prompt ) {
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot      = ! empty( $settings['chatbot'] ) ? $settings['chatbot'] : array();
		$models   = ! empty( $settings['models'] ) ? $settings['models'] : array();

		require_once DCTC_PLUGIN_DIR . 'includes/ai/ai-providers/class-dctc-ai-provider-manager.php';
		$manager = DCTC_AI_Provider_Manager::get_instance();

		$provider_name = ! empty( $bot['active_provider'] ) ? $bot['active_provider'] : 'google';
		$provider      = $manager->get_provider( $provider_name );

		if ( ! $provider ) {
			return new WP_Error( 'no_provider', __( 'No active AI provider configured.', 'dragwyb-click-to-chat' ) );
		}

		$model_id = ! empty( $models[ $provider_name ]['model'] ) ? $models[ $provider_name ]['model'] : '';

		try {
			$result = $provider->chat_completion( $user_prompt, $system_prompt, $model_id );
			if ( ! empty( $result['message'] ) ) {
				return $result['message'];
			}
			return new WP_Error( 'empty_response', __( 'AI returned an empty response.', 'dragwyb-click-to-chat' ) );
		} catch ( \Throwable $e ) {
			return new WP_Error( 'ai_error', $e->getMessage() );
		}
	}
}
