<?php
/**
 * DCTC AI Provider: Groq
 *
 * Adapter for Groq ultra-low-latency LPU inference API.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-dctc-ai-provider-base.php';

class DCTC_AI_Provider_Groq extends DCTC_AI_Provider_Base
{
	public function get_id()
	{
		return 'groq';
	}

	public function get_name()
	{
		return 'Groq';
	}

	public function chat($prompt, $system_message, $model, array $options = [])
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('Groq API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'llama-3.3-70b-versatile';
		$temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
		$max_tokens  = isset($options['max_tokens']) ? intval($options['max_tokens']) : 500;

		$messages = [];
		if (!empty($system_message)) {
			$messages[] = ['role' => 'system', 'content' => $system_message];
		}
		$messages[] = ['role' => 'user', 'content' => trim($prompt)];

		$payload = [
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		];

		$response = $this->http_post(
			'https://api.groq.com/openai/v1/chat/completions',
			$payload,
			['Authorization' => 'Bearer ' . $key]
		);

		if (isset($response['choices'][0]['message']['content'])) {
			return trim($response['choices'][0]['message']['content']);
		}

		throw new \Exception(esc_html__('Groq returned an empty response.', 'dragwyb-click-to-chat'));
	}

	public function embed(array $texts, $model = '')
	{
		throw new \Exception(esc_html__('Groq does not support vector embeddings. Please use OpenAI or Google Gemini for vector embeddings.', 'dragwyb-click-to-chat'));
	}

	public function get_models()
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			return $this->get_static_models();
		}

		$cache_key = 'dctc_ai_models_groq_' . md5($key);
		$cached = get_transient($cache_key);
		if (false !== $cached && is_array($cached)) {
			return $cached;
		}

		try {
			$response = $this->http_get('https://api.groq.com/openai/v1/models', [
				'Authorization' => 'Bearer ' . $key,
			], 15);

			if (isset($response['data']) && is_array($response['data'])) {
				$models = [];
				foreach ($response['data'] as $m) {
					if (isset($m['id']) && (strpos($m['id'], 'whisper') === false)) {
						$models[$m['id']] = $m['id'];
					}
				}
				if (!empty($models)) {
					set_transient($cache_key, $models, HOUR_IN_SECONDS * 6);
					return $models;
				}
			}
		} catch (\Throwable $e) {
			// Fallback
		}

		$models = $this->get_static_models();
		set_transient($cache_key, $models, HOUR_IN_SECONDS);
		return $models;
	}

	public function validate_key($api_key)
	{
		if (empty($api_key)) {
			return new \WP_Error('empty_key', __('Groq API key cannot be empty.', 'dragwyb-click-to-chat'));
		}

		try {
			$response = $this->http_get('https://api.groq.com/openai/v1/models', [
				'Authorization' => 'Bearer ' . $api_key,
			], 15);

			if (isset($response['data']) && is_array($response['data'])) {
				return true;
			}

			return new \WP_Error('invalid_key', __('Invalid Groq API key.', 'dragwyb-click-to-chat'));
		} catch (\Exception $e) {
			return new \WP_Error('api_error', $e->getMessage());
		}
	}

	private function get_static_models()
	{
		return [
			'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile (Ultra Fast)',
			'llama-3.1-8b-instant'    => 'Llama 3.1 8B Instant (Lightning Speed)',
			'mixtral-8x7b-32768'       => 'Mixtral 8x7B (High Context)',
			'gemma2-9b-it'             => 'Gemma 2 9B IT (Google)',
		];
	}
}
