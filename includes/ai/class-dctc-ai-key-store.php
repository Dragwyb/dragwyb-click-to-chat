<?php
/**
 * DCTC AI Key Store
 *
 * Owns AI provider API keys and secrets: storing/validating provider keys,
 * listing a provider's available models, and symmetric encryption used
 * for other stored secrets (e.g. MCP server API keys).
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/ai-providers/class-dctc-ai-provider-manager.php';

/**
 * Class DCTC_AI_Key_Store
 */
class DCTC_AI_Key_Store
{
	use DCTC_AI_REST_Helpers;

	/**
	 * Supported providers list.
	 *
	 * @return array
	 */
	public static function get_supported_providers()
	{
		return DCTC_AI_Provider_Manager::$supported_providers;
	}

	/**
	 * Check if at least one AI provider API key is configured.
	 *
	 * @return bool True if any supported provider key is present.
	 */
	public static function has_configured_provider()
	{
		foreach (self::get_supported_providers() as $provider) {
			$key = self::get_provider_key($provider);
			if (!empty($key)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get a stored AI provider API key.
	 *
	 * @param string $provider Identifier for the AI provider.
	 * @return string The raw API key string if available.
	 */
	public static function get_provider_key($provider)
	{
		$provider = sanitize_key($provider);
		$is_wp_ai_client_70 = function_exists('wp_ai_client_prompt');

		if ($is_wp_ai_client_70) {
			$key = get_option('connectors_ai_' . $provider . '_api_key', '');
			if (!empty($key)) {
				return $key;
			}
		}

		$creds = get_option('wp_ai_client_provider_credentials', []);
		if (isset($creds[$provider]) && !empty($creds[$provider])) {
			return $creds[$provider];
		}

		return get_option('connectors_ai_' . $provider . '_api_key', '');
	}

	/**
	 * Get the list of available models for an AI provider.
	 *
	 * @param string $provider Provider identifier (e.g. 'openai', 'google', 'anthropic', etc.).
	 * @return array Map of model ID => model display name.
	 */
	public static function get_models($provider)
	{
		$provider = sanitize_key($provider);
		$manager = DCTC_AI_Provider_Manager::get_instance();
		$adapter = $manager->get_provider($provider);

		if ($adapter) {
			return $adapter->get_models();
		}

		return [];
	}

	/**
	 * Verify an API key by delegating to provider adapter.
	 *
	 * @param string $provider Provider identifier.
	 * @param string $key API key to validate.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_api_key($provider, $key)
	{
		$provider = sanitize_key($provider);
		$manager = DCTC_AI_Provider_Manager::get_instance();
		$adapter = $manager->get_provider($provider);

		if ($adapter) {
			return $adapter->validate_key($key);
		}

		return new \WP_Error('unsupported_provider', sprintf(
			/* translators: %s: Provider ID */
			esc_html__('Unsupported AI provider: %s', 'dragwyb-click-to-chat'),
			esc_html($provider)
		));
	}

	/**
	 * REST callback: validate and save each submitted provider API key, and
	 * any selected fallback models.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Standard API response.
	 */
	public function save_provider_keys($request)
	{
		$params = $request->get_json_params();
		$models = isset($params['models']) ? (array) $params['models'] : [];
		$default_provider  = isset($params['default_provider']) ? sanitize_text_field($params['default_provider']) : '';
		$fallback_provider = isset($params['fallback_provider']) ? sanitize_text_field($params['fallback_provider']) : '';
		$fallback_model    = isset($params['fallback_model']) ? sanitize_text_field($params['fallback_model']) : '';
		$enable_failover   = isset($params['enable_failover']) ? (bool) $params['enable_failover'] : true;

		$errors = [];
		$supported = self::get_supported_providers();

		// Check and save keys for all supported providers
		foreach ($supported as $p_id) {
			$key_param = $p_id . '_key';
			if (!empty($params[$key_param])) {
				$raw_key = sanitize_text_field($params[$key_param]);
				$valid = $this->validate_api_key($p_id, $raw_key);
				if (is_wp_error($valid)) {
					$errors[$p_id] = $valid->get_error_message();
				} else {
					$this->persist_key($p_id, $raw_key);
				}
			}
		}

		// Save selected fallback models.
		if (!empty($models)) {
			foreach ($models as $provider => $model) {
				if (!empty($model)) {
					$this->persist_model_selection(sanitize_key($provider), sanitize_text_field($model));
				}
			}
		}

		// Save chatbot provider routing settings.
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		if (isset($settings['chatbot'])) {
			if ($default_provider) {
				$settings['chatbot']['default_provider'] = $default_provider;
			}
			$settings['chatbot']['fallback_provider'] = $fallback_provider;
			$settings['chatbot']['fallback_model']    = $fallback_model;
			$settings['chatbot']['enable_failover']   = $enable_failover;
			DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);
		}

		if (!empty($errors)) {
			return new \WP_REST_Response(['success' => false, 'errors' => $errors], 400);
		}

		$api_keys = [];
		$models_list = [];
		foreach ($supported as $id) {
			$key = self::get_provider_key($id);
			if (!empty($key)) {
				if (strlen($key) < 8) {
					$api_keys[$id] = '********';
				} else {
					$api_keys[$id] = substr($key, 0, 4) . '...' . substr($key, -4);
				}
			}
			$models_list[$id] = self::get_models($id);
		}

		$updated_settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$chatbot_data = isset($updated_settings['chatbot']) ? $updated_settings['chatbot'] : [];

		return new \WP_REST_Response(
			[
				'success'     => true,
				'message'     => esc_html__('Settings saved successfully!', 'dragwyb-click-to-chat'),
				'api_keys'    => $api_keys,
				'models_list' => $models_list,
				'chatbot'     => $chatbot_data,
				'models'      => isset($updated_settings['models']) ? $updated_settings['models'] : [],
			],
			200
		);
	}

	/**
	 * REST callback: clear a provider's stored key and selected model.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response Standard API response.
	 */
	public function reset_key($request)
	{
		$provider = sanitize_key($request->get_param('provider'));
		$supported = self::get_supported_providers();

		if (!in_array($provider, $supported, true)) {
			return new \WP_REST_Response(
				['success' => false, 'message' => esc_html__('Unknown provider.', 'dragwyb-click-to-chat')],
				400
			);
		}

		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		if (isset($settings['models'][$provider])) {
			$settings['models'][$provider] = '';
		}
		if (isset($settings['api_keys'][$provider])) {
			unset($settings['api_keys'][$provider]);
		}

		// If the reset provider was default_provider or fallback_provider, update them.
		if (isset($settings['chatbot']['default_provider']) && $settings['chatbot']['default_provider'] === $provider) {
			$remaining = [];
			foreach ($supported as $p) {
				if ($p !== $provider && !empty(self::get_provider_key($p))) {
					$remaining[] = $p;
				}
			}
			$settings['chatbot']['default_provider'] = !empty($remaining) ? $remaining[0] : '';
		}

		if (isset($settings['chatbot']['fallback_provider']) && $settings['chatbot']['fallback_provider'] === $provider) {
			$settings['chatbot']['fallback_provider'] = '';
			$settings['chatbot']['fallback_model']    = '';
		}

		DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);

		delete_option('connectors_ai_' . $provider . '_api_key');
		$creds = get_option('wp_ai_client_provider_credentials', []);
		if (isset($creds[$provider])) {
			unset($creds[$provider]);
			update_option('wp_ai_client_provider_credentials', $creds);
		}

		return new \WP_REST_Response([
			'success' => true,
			'chatbot' => isset($settings['chatbot']) ? $settings['chatbot'] : [],
		], 200);
	}

	/**
	 * Encrypt Secret
	 *
	 * Authenticated symmetric encryption (AES-256-GCM) for secrets (e.g.
	 * MCP server API keys) stored in the settings option.
	 *
	 * @param string $plaintext Value to encrypt.
	 * @return string "v2:" + base64-encoded IV + tag + ciphertext, or '' on empty input/failure.
	 */
	public function encrypt_secret($plaintext)
	{
		if ('' === $plaintext) {
			return '';
		}

		$key = hash('sha256', wp_salt('auth'), true);
		$iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
		$tag = '';

		$ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

		if (false === $ciphertext) {
			return '';
		}

		return 'v2:' . base64_encode($iv . $tag . $ciphertext);
	}

	/**
	 * Decrypt Secret
	 *
	 * Reads both authenticated (AES-256-GCM) and legacy AES-256-CBC.
	 *
	 * @param string $encoded Value produced by encrypt_secret().
	 * @return string Decrypted plaintext, or '' on empty input/failure.
	 */
	public function decrypt_secret($encoded)
	{
		if (empty($encoded)) {
			return '';
		}

		$key = hash('sha256', wp_salt('auth'), true);

		if (0 === strpos($encoded, 'v2:')) {
			$raw = base64_decode(substr($encoded, 3), true);

			if (false === $raw) {
				return '';
			}

			$iv_length = openssl_cipher_iv_length('aes-256-gcm');
			$tag_length = 16;
			$iv = substr($raw, 0, $iv_length);
			$tag = substr($raw, $iv_length, $tag_length);
			$ciphertext = substr($raw, $iv_length + $tag_length);

			$plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

			return false === $plaintext ? '' : $plaintext;
		}

		$raw = base64_decode($encoded, true);

		if (false === $raw) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length('aes-256-cbc');
		$iv = substr($raw, 0, $iv_length);
		$ciphertext = substr($raw, $iv_length);

		$plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

		return false === $plaintext ? '' : $plaintext;
	}

	/**
	 * Store an AI provider's API key.
	 *
	 * @param string $provider Provider identifier.
	 * @param string $value API key value.
	 * @return void
	 */
	private function persist_key($provider, $value)
	{
		$provider = sanitize_key($provider);
		$clean_val = sanitize_text_field($value);

		update_option('connectors_ai_' . $provider . '_api_key', $clean_val);

		$creds = get_option('wp_ai_client_provider_credentials', []);
		$creds[$provider] = $clean_val;
		update_option('wp_ai_client_provider_credentials', $creds);
	}

	/**
	 * Store a provider's selected fallback model.
	 *
	 * @param string $provider Provider identifier.
	 * @param string $model    Selected model ID.
	 * @return void
	 */
	private function persist_model_selection($provider, $model)
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		if (!isset($settings['models'])) {
			$settings['models'] = [];
		}
		$settings['models'][$provider] = $model;
		DCTC_AI_Settings_Handler::dctc_ai_persist_settings($settings);
	}
}
