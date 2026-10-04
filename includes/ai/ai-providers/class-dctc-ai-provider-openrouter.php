<?php
/**
 * DCTC AI Provider: OpenRouter
 *
 * Adapter for OpenRouter multi-model aggregation API.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-dctc-ai-provider-base.php';

class DCTC_AI_Provider_OpenRouter extends DCTC_AI_Provider_Base
{
	public function get_id()
	{
		return 'openrouter';
	}

	public function get_name()
	{
		return 'OpenRouter';
	}

	public function chat($prompt, $system_message, $model, array $options = [])
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('OpenRouter API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'anthropic/claude-3.5-sonnet';
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

		$headers = [
			'Authorization' => 'Bearer ' . $key,
			'HTTP-Referer'  => home_url(),
			'X-Title'       => get_bloginfo('name'),
		];

		$response = $this->http_post(
			'https://openrouter.ai/api/v1/chat/completions',
			$payload,
			$headers
		);

		if (isset($response['choices'][0]['message']['content'])) {
			return trim($response['choices'][0]['message']['content']);
		}

		throw new \Exception(esc_html__('OpenRouter returned an empty response.', 'dragwyb-click-to-chat'));
	}

	public function embed(array $texts, $model = '')
	{
		throw new \Exception(esc_html__('Embeddings are not supported directly through OpenRouter. Please select OpenAI or Google Gemini for vector embeddings.', 'dragwyb-click-to-chat'));
	}

	public function get_models()
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			return $this->get_static_models();
		}

		$cache_key = 'dctc_ai_models_openrouter_' . md5($key);
		$cached = get_transient($cache_key);
		if (false !== $cached && is_array($cached)) {
			return $cached;
		}

		try {
			$headers = [
				'Authorization' => 'Bearer ' . $key,
				'HTTP-Referer'  => home_url(),
			];
			$response = $this->http_get('https://openrouter.ai/api/v1/models', $headers, 15);

			if (isset($response['data']) && is_array($response['data'])) {
				$models = [];
				foreach ($response['data'] as $m) {
					if (isset($m['id'])) {
						$name = isset($m['name']) ? $m['name'] : $m['id'];
						$models[$m['id']] = $name;
					}
				}
				if (!empty($models)) {
					// OpenRouter returns hundreds of models, prioritize top popular ones or sort nicely
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
			return new \WP_Error('empty_key', __('OpenRouter API key cannot be empty.', 'dragwyb-click-to-chat'));
		}

		try {
			$headers = [
				'Authorization' => 'Bearer ' . $api_key,
				'HTTP-Referer'  => home_url(),
			];
			$response = $this->http_get('https://openrouter.ai/api/v1/auth/key', $headers, 15);

			if (isset($response['data'])) {
				return true;
			}

			// Alternatively check models
			$models_res = $this->http_get('https://openrouter.ai/api/v1/models', $headers, 15);
			if (isset($models_res['data'])) {
				return true;
			}

			return new \WP_Error('invalid_key', __('Invalid OpenRouter API key.', 'dragwyb-click-to-chat'));
		} catch (\Exception $e) {
			return new \WP_Error('api_error', $e->getMessage());
		}
	}

	private function get_static_models()
	{
		return [
			'anthropic/claude-3.5-sonnet'        => 'Anthropic: Claude 3.5 Sonnet',
			'openai/gpt-4o'                       => 'OpenAI: GPT-4o',
			'openai/gpt-4o-mini'                  => 'OpenAI: GPT-4o Mini',
			'deepseek/deepseek-chat'             => 'DeepSeek: DeepSeek V3',
			'deepseek/deepseek-r1'               => 'DeepSeek: DeepSeek R1',
			'meta-llama/llama-3.3-70b-instruct'   => 'Meta: Llama 3.3 70B Instruct',
			'google/gemini-2.0-flash-lite-001'   => 'Google: Gemini 2.0 Flash Lite',
			'google/gemini-2.0-flash-001'        => 'Google: Gemini 2.0 Flash',
			'google/gemini-2.5-flash'            => 'Google: Gemini 2.5 Flash',
			'google/gemini-flash-1.5'            => 'Google: Gemini 1.5 Flash',
			'mistralai/mistral-large-2407'       => 'Mistral: Mistral Large',
		];
	}
}
