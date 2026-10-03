<?php
/**
 * DCTC AI Provider: DeepSeek
 *
 * Adapter for DeepSeek AI (V3 & R1 models).
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-dctc-ai-provider-base.php';

class DCTC_AI_Provider_DeepSeek extends DCTC_AI_Provider_Base
{
	public function get_id()
	{
		return 'deepseek';
	}

	public function get_name()
	{
		return 'DeepSeek';
	}

	public function chat($prompt, $system_message, $model, array $options = [])
	{
		$key = $this->get_api_key();
		if (empty($key)) {
			throw new \Exception(esc_html__('DeepSeek API key is missing.', 'dragwyb-click-to-chat'));
		}

		$model = !empty($model) ? $model : 'deepseek-chat';
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
			'https://api.deepseek.com/chat/completions',
			$payload,
			['Authorization' => 'Bearer ' . $key]
		);

		if (isset($response['choices'][0]['message']['content'])) {
			return trim($response['choices'][0]['message']['content']);
		}

		throw new \Exception(esc_html__('DeepSeek returned an empty response.', 'dragwyb-click-to-chat'));
	}

	public function embed(array $texts, $model = '')
	{
		throw new \Exception(esc_html__('DeepSeek does not support vector embeddings. Please use OpenAI or Google Gemini for vector embeddings.', 'dragwyb-click-to-chat'));
	}

	public function get_models()
	{
		return $this->get_static_models();
	}

	public function validate_key($api_key)
	{
		if (empty($api_key)) {
			return new \WP_Error('empty_key', __('DeepSeek API key cannot be empty.', 'dragwyb-click-to-chat'));
		}

		try {
			$response = $this->http_get('https://api.deepseek.com/models', [
				'Authorization' => 'Bearer ' . $api_key,
			], 15);

			if (isset($response['data']) && is_array($response['data'])) {
				return true;
			}

			return new \WP_Error('invalid_key', __('Invalid DeepSeek API key.', 'dragwyb-click-to-chat'));
		} catch (\Exception $e) {
			return new \WP_Error('api_error', $e->getMessage());
		}
	}

	private function get_static_models()
	{
		return [
			'deepseek-chat'     => 'DeepSeek-V3 (Smart & Cost-Efficient)',
			'deepseek-reasoner' => 'DeepSeek-R1 (Advanced Reasoning & CoT)',
		];
	}
}
