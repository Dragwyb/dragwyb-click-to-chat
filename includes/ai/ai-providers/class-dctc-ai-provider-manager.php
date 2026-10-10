<?php
/**
 * DCTC AI Provider Manager
 *
 * Central registry for AI providers in Dragwyb Free with extension hooks
 * for Dragwyb Pro add-on.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/interface-dctc-ai-provider.php';
require_once __DIR__ . '/class-dctc-ai-provider-base.php';
require_once __DIR__ . '/class-dctc-ai-provider-openai.php';
require_once __DIR__ . '/class-dctc-ai-provider-google.php';

class DCTC_AI_Provider_Manager {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Map of registered providers: provider_id => DCTC_AI_Provider_Interface
	 *
	 * @var array<string, DCTC_AI_Provider_Interface>
	 */
	private $providers = array();

	/**
	 * Supported provider IDs in Free.
	 *
	 * @var string[]
	 */
	public static $supported_providers = array(
		'openai',
		'google',
	);

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Register core Free AI providers
		$this->register_provider( new DCTC_AI_Provider_OpenAI() );
		$this->register_provider( new DCTC_AI_Provider_Google() );

		// Allow Dragwyb Pro Add-on to register additional premium providers (Claude, Groq, DeepSeek, OpenRouter, Ollama)
		do_action( 'dctc_ai_register_providers', $this );
	}

	/**
	 * Register an AI provider adapter.
	 *
	 * @param DCTC_AI_Provider_Interface $provider
	 * @return void
	 */
	public function register_provider( DCTC_AI_Provider_Interface $provider ) {
		$this->providers[ $provider->get_id() ] = $provider;
		if ( ! in_array( $provider->get_id(), self::$supported_providers, true ) ) {
			self::$supported_providers[] = $provider->get_id();
		}
	}

	/**
	 * Get a provider adapter by ID.
	 *
	 * @param string $id
	 * @return DCTC_AI_Provider_Interface|null
	 */
	public function get_provider( $id ) {
		$id = sanitize_key( $id );
		return isset( $this->providers[ $id ] ) ? $this->providers[ $id ] : null;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return array<string, DCTC_AI_Provider_Interface>
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Get list of provider IDs that have a configured key.
	 *
	 * @return string[]
	 */
	public function get_configured_provider_ids() {
		$configured = array();
		foreach ( $this->providers as $id => $provider ) {
			$key = DCTC_AI_Key_Store::get_provider_key( $id );
			if ( ! empty( $key ) ) {
				$configured[] = $id;
			}
		}
		return $configured;
	}

	/**
	 * Execute chat with automatic failover fallback.
	 *
	 * @param string $prompt User prompt.
	 * @param string $system_message System instructions.
	 * @param string $primary_provider Primary provider ID.
	 * @param string $primary_model Primary model ID.
	 * @param array  $options Generation options (temperature, max_tokens).
	 * @param string $fallback_provider Fallback provider ID (optional).
	 * @param string $fallback_model Fallback model ID (optional).
	 * @return array ['message' => string, 'provider' => string, 'model' => string, 'failed_over' => bool]
	 * @throws \Exception When all attempts fail.
	 */
	public function chat_with_fallback(
		$prompt,
		$system_message,
		$primary_provider,
		$primary_model,
		array $options = array(),
		$fallback_provider = '',
		$fallback_model = ''
	) {
		$primary_adapter = $this->get_provider( $primary_provider );

		if ( ! $primary_adapter ) {
			// Fallback to any registered provider (e.g. OpenAI or Google)
			if ( ! empty( $this->providers ) ) {
				$primary_adapter = reset( $this->providers );
				$primary_provider = $primary_adapter->get_id();
			} else {
				throw new \Exception(
					sprintf(
						/* translators: %s: Provider ID */
						esc_html__( 'No valid AI provider configured. Please check your API keys.', 'dragwyb-click-to-chat' )
					)
				);
			}
		}

		$primary_error = null;

		// 1. Try Primary Provider
		try {
			$result_text = $primary_adapter->chat( $prompt, $system_message, $primary_model, $options );
			if ( ! empty( $result_text ) ) {
				return array(
					'message'     => $result_text,
					'provider'    => $primary_provider,
					'model'       => $primary_model,
					'failed_over' => false,
				);
			}
			throw new \Exception( esc_html__( 'Primary provider returned an empty response.', 'dragwyb-click-to-chat' ) );
		} catch ( \Throwable $e ) {
			$primary_error = $e;

			if ( class_exists( 'DCTC_Error_Logger' ) ) {
				DCTC_Error_Logger::log_ai_error(
					$primary_provider,
					$primary_model,
					$prompt,
					$e->getMessage(),
					array(
						'type'    => 'Primary Provider Failure',
						'code'    => (string) $e->getCode(),
						'context' => 'Chat Completion Handler',
					)
				);
			}
		}

		// Re-throw primary exception if fallback is not available
		throw new \Exception( $primary_error ? $primary_error->getMessage() : esc_html__( 'AI service is currently unavailable.', 'dragwyb-click-to-chat' ) );
	}
}
