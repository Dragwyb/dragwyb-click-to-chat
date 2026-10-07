<?php
/**
 * DCTC AI Provider Manager
 *
 * Central registry for AI providers and failover execution logic.
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
require_once __DIR__ . '/class-dctc-ai-provider-anthropic.php';
require_once __DIR__ . '/class-dctc-ai-provider-openrouter.php';
require_once __DIR__ . '/class-dctc-ai-provider-groq.php';
require_once __DIR__ . '/class-dctc-ai-provider-deepseek.php';

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
	 * Supported provider IDs.
	 *
	 * @var string[]
	 */
	public static $supported_providers = array(
		'openai',
		'google',
		'anthropic',
		'openrouter',
		'groq',
		'deepseek',
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
		$this->register_provider( new DCTC_AI_Provider_OpenAI() );
		$this->register_provider( new DCTC_AI_Provider_Google() );
		$this->register_provider( new DCTC_AI_Provider_Anthropic() );
		$this->register_provider( new DCTC_AI_Provider_OpenRouter() );
		$this->register_provider( new DCTC_AI_Provider_Groq() );
		$this->register_provider( new DCTC_AI_Provider_DeepSeek() );
	}

	/**
	 * Register an AI provider adapter.
	 *
	 * @param DCTC_AI_Provider_Interface $provider
	 * @return void
	 */
	public function register_provider( DCTC_AI_Provider_Interface $provider ) {
		$this->providers[ $provider->get_id() ] = $provider;
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
			throw new \Exception(
				sprintf(
				/* translators: %s: Provider ID */
					esc_html__( 'Unsupported primary AI provider: %s', 'dragwyb-click-to-chat' ),
					esc_html( $primary_provider )
				)
			);
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
						'context' => 'Chat Completion with Failover',
					)
				);
			}
		}

		// 2. Check if a valid fallback provider is available
		$can_fallback = false;
		if ( ! empty( $fallback_provider ) ) {
			$fallback_key = DCTC_AI_Key_Store::get_provider_key( $fallback_provider );
			if ( ! empty( $fallback_key ) ) {
				$can_fallback = true;
			}
		}

		// Auto-discover another configured provider if none explicitly set as fallback
		if ( ! $can_fallback ) {
			$configured = $this->get_configured_provider_ids();
			foreach ( $configured as $c_id ) {
				if ( $c_id !== $primary_provider ) {
					$fallback_provider = $c_id;
					$can_fallback      = true;
					break;
				}
			}
		}

		if ( $can_fallback && ! empty( $fallback_provider ) ) {
			$fallback_adapter = $this->get_provider( $fallback_provider );

			if ( $fallback_adapter ) {
				// Resolve fallback model default if empty
				if ( empty( $fallback_model ) ) {
					$models         = DCTC_AI_Key_Store::get_models( $fallback_provider );
					$fallback_model = ! empty( $models ) ? array_key_first( $models ) : '';
				}

				try {
					$fallback_text = $fallback_adapter->chat( $prompt, $system_message, $fallback_model, $options );

					if ( ! empty( $fallback_text ) ) {
						if ( class_exists( 'DCTC_Error_Logger' ) ) {
							DCTC_Error_Logger::log_ai_error(
								$primary_provider,
								$primary_model,
								$prompt,
								sprintf(
									'Primary provider (%1$s - %2$s) failed with error: "%3$s". Successfully failed over to secondary backup provider: %4$s (%5$s).',
									$primary_provider,
									$primary_model,
									$primary_error ? $primary_error->getMessage() : 'Unknown error',
									$fallback_provider,
									$fallback_model
								),
								array(
									'type'    => 'Failover Successful',
									'context' => 'Automatic Failover Handler',
								)
							);
						}

						return array(
							'message'     => $fallback_text,
							'provider'    => $fallback_provider,
							'model'       => $fallback_model,
							'failed_over' => true,
						);
					}
				} catch ( \Throwable $fe ) {
					if ( class_exists( 'DCTC_Error_Logger' ) ) {
						DCTC_Error_Logger::log_ai_error(
							$fallback_provider,
							$fallback_model,
							$prompt,
							$fe->getMessage(),
							array(
								'type'    => 'Fallback Provider Failure',
								'code'    => (string) $fe->getCode(),
								'context' => 'Failover Fallback Attempt',
							)
						);
					}
				}
			}
		}

		// Re-throw primary exception if fallback was unavailable or also failed
		throw new \Exception( $primary_error ? $primary_error->getMessage() : esc_html__( 'AI service is currently unavailable.', 'dragwyb-click-to-chat' ) );
	}
}
