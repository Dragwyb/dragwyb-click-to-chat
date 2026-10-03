<?php
/**
 * DCTC AI Provider: Anthropic Claude
 *
 * Adapter for Anthropic Claude Messages API.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-dctc-ai-provider-base.php';

class DCTC_AI_Provider_Anthropic extends DCTC_AI_Provider_Base
{
	public function get_id()
	{
		return 'anthropic';
	}

	public function get_name()
	{
		return 'Anthropic Claude';
	}

	public function chat($prompt, $system_message, $model, array $options = [])
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('Anthropic API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'claude-3-5-sonnet-20241022';
		$temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
		$max_tokens  = isset($options['max_tokens']) ? max(100, intval($options['max_tokens'])) : 1000;

		$payload = [
			'model'       => $model,
			'max_tokens'  => $max_tokens,
			'temperature' => $temperature,
			'messages'    => [
				[
					'role'    => 'user',
					'content' => trim($prompt),
				],
			],
		];

		if (!empty($system_message)) {
			$payload['system'] = $system_message;
		}

		$headers = [
			'x-api-key'         => $key,
			'anthropic-version' => '2023-06-01',
		];

		$response = $this->http_post('https://api.anthropic.com/v1/messages', $payload, $headers);

		if (isset($response['content']) && is_array($response['content'])) {
			$texts = [];
			foreach ($response['content'] as $block) {
				if (isset($block['type']) && 'text' === $block['type'] && isset($block['text'])) {
					$texts[] = $block['text'];
				}
			}
			if (!empty($texts)) {
				return trim(implode("\n", $texts));
			}
		}

		throw new \Exception(esc_html__('Anthropic Claude returned an empty response.', 'dragwyb-click-to-chat'));
	}

	public function embed(array $texts, $model = '')
	{
		// Anthropic does not provide native embeddings; throw clear error so fallback / OpenAI / Google is used for embeddings.
		throw new \Exception(esc_html__('Anthropic Claude does not support text embeddings. Please select OpenAI or Google Gemini for vector embeddings in Knowledge Base settings.', 'dragwyb-click-to-chat'));
	}

	public function get_models()
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			return $this->get_static_models();
		}

		$cache_key = 'dctc_ai_models_anthropic_' . md5($key);
		$cached = get_transient($cache_key);
		if (false !== $cached && is_array($cached)) {
			return $cached;
		}

		try {
			$headers = [
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
			];
			$response = $this->http_get('https://api.anthropic.com/v1/models', $headers, 15);

			if (isset($response['data']) && is_array($response['data'])) {
				$models = [];
				foreach ($response['data'] as $m) {
					if (isset($m['id'])) {
						$models[$m['id']] = isset($m['display_name']) ? $m['display_name'] : $m['id'];
					}
				}
				if (!empty($models)) {
					set_transient($cache_key, $models, HOUR_IN_SECONDS);
					return $models;
				}
			}
		} catch (\Throwable $e) {
			// Fallback to static list
		}

		$models = $this->get_static_models();
		set_transient($cache_key, $models, HOUR_IN_SECONDS);
		return $models;
	}

	public function validate_key($api_key)
	{
		if (empty($api_key)) {
			return new \WP_Error('empty_key', __('Anthropic API key cannot be empty.', 'dragwyb-click-to-chat'));
		}

		try {
			// Test using minimal ping or models endpoint
			$headers = [
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			];

			$response = $this->http_post('https://api.anthropic.com/v1/messages', [
				'model'      => 'claude-3-5-haiku-20241022',
				'max_tokens' => 5,
				'messages'   => [
					['role' => 'user', 'content' => 'Hi'],
				],
			], $headers, 15);

			if (isset($response['content'])) {
				return true;
			}

			return new \WP_Error('invalid_key', __('Invalid Anthropic API key.', 'dragwyb-click-to-chat'));
		} catch (\Exception $e) {
			return new \WP_Error('api_error', $e->getMessage());
		}
	}

	private function get_static_models()
	{
		return [
			'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Most Intelligent)',
			'claude-3-5-haiku-20241022'  => 'Claude 3.5 Haiku (Fast & Cost-Efficient)',
			'claude-3-opus-20240229'     => 'Claude 3 Opus (Complex Analysis)',
		];
	}
}
