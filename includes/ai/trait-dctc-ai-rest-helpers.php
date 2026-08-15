<?php
/**
 * DCTC AI REST Helpers
 *
 * Small helpers shared by the plugin's REST controllers (error responses,
 * conditional debug logging), so each controller doesn't reimplement them.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Trait DCTC_AI_REST_Helpers
 */
trait DCTC_AI_REST_Helpers
{

	/**
	 * Log a debugging message when WP_DEBUG is enabled.
	 *
	 * Static so it can be called from both instance and static methods
	 * via self:: or $this->.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	private static function log_debug($message)
	{
		if (function_exists('dctc_log_error')) {
			dctc_log_error('Handled Error', $message, ['context' => 'AI REST']);
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log($message); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Allowed under WP_DEBUG constraint.
		}
	}

	/**
	 * Build a standard {success: false, message} error response.
	 *
	 * @param string $message Human-readable error message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	private function error_response($message, $status = 500)
	{
		if (function_exists('dctc_log_error')) {
			dctc_log_error(
				'REST Error',
				$message,
				[
					'code' => $status,
					'context' => 'AI REST response',
				]
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'message' => $message,
			],
			$status
		);
	}
}
