<?php
/**
 * DCTC AI Usage Tracker & Cost Controls
 *
 * Tracks request counts and token usage across sessions, enforces daily/monthly
 * cost ceilings, and dispatches threshold alert emails to site administrators.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

class DCTC_AI_Usage_Tracker
{
	/**
	 * Check if current request complies with visitor daily limits and site budget ceilings.
	 *
	 * @param string $session_id Visitor session ID.
	 * @return array ['allowed' => bool, 'reason' => string, 'message' => string, 'action_buttons' => array]
	 */
	public static function check_budget_and_limits($session_id = 'default')
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot = isset($settings['chatbot']) ? $settings['chatbot'] : [];

		$enabled = isset($bot['enable_usage_limits']) ? (bool) $bot['enable_usage_limits'] : true;
		if (!$enabled) {
			return ['allowed' => true];
		}

		$fallback_msg = !empty($bot['budget_limit_message'])
			? $bot['budget_limit_message']
			: __('You have reached the daily chat limit. Please connect with our team directly via WhatsApp or Support.', 'dragwyb-click-to-chat');

		$action_buttons = [];
		if (!empty($bot['support_url'])) {
			$action_buttons[] = [
				'id'     => 'budget_support',
				'label'  => __('Contact Support', 'dragwyb-click-to-chat'),
				'url'    => esc_url_raw($bot['support_url']),
				'target' => '_blank',
			];
		}

		// 1. Check Visitor Daily Limit
		$visitor_daily_limit = isset($bot['visitor_daily_message_limit']) ? intval($bot['visitor_daily_message_limit']) : 50;
		if ($visitor_daily_limit > 0) {
			$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
			$visitor_key = 'dctc_ai_uv_' . md5($session_id . '_' . $ip . '_' . date('Ymd'));
			$visitor_count = (int) get_transient($visitor_key);

			if ($visitor_count >= $visitor_daily_limit) {
				return [
					'allowed'        => false,
					'reason'         => 'visitor_daily_limit',
					'message'        => $fallback_msg,
					'action_buttons' => $action_buttons,
				];
			}
		}

		// 2. Check Monthly Site Request Budget Ceiling
		$monthly_budget = isset($bot['monthly_request_budget']) ? intval($bot['monthly_request_budget']) : 5000;
		if ($monthly_budget > 0) {
			$month_key = 'dctc_ai_site_reqs_' . date('Ym');
			$month_count = (int) get_option($month_key, 0);

			if ($month_count >= $monthly_budget) {
				self::maybe_send_alert(100, $month_count, $monthly_budget, $bot);
				return [
					'allowed'        => false,
					'reason'         => 'monthly_budget_limit',
					'message'        => $fallback_msg,
					'action_buttons' => $action_buttons,
				];
			}

			// Check 80% threshold warning
			if ($month_count >= ($monthly_budget * 0.8)) {
				self::maybe_send_alert(80, $month_count, $monthly_budget, $bot);
			}
		}

		return ['allowed' => true];
	}

	/**
	 * Record a successful AI request and update metrics.
	 *
	 * @param string $session_id Session ID.
	 * @param string $provider Provider used.
	 * @param string $model Model used.
	 * @param int    $est_tokens Estimated tokens used.
	 * @return void
	 */
	public static function record_usage($session_id = 'default', $provider = '', $model = '', $est_tokens = 0)
	{
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
		
		// 1. Increment visitor daily counter (expires in 24h)
		$visitor_key = 'dctc_ai_uv_' . md5($session_id . '_' . $ip . '_' . date('Ymd'));
		$visitor_count = (int) get_transient($visitor_key);
		set_transient($visitor_key, $visitor_count + 1, DAY_IN_SECONDS);

		// 2. Increment site daily and monthly request counters
		$day_key = 'dctc_ai_site_reqs_' . date('Ymd');
		$day_count = (int) get_option($day_key, 0);
		update_option($day_key, $day_count + 1, false);

		$month_key = 'dctc_ai_site_reqs_' . date('Ym');
		$month_count = (int) get_option($month_key, 0);
		update_option($month_key, $month_count + 1, false);

		// 3. Record estimated tokens
		if ($est_tokens > 0) {
			$month_tokens_key = 'dctc_ai_site_tokens_' . date('Ym');
			$month_tokens = (int) get_option($month_tokens_key, 0);
			update_option($month_tokens_key, $month_tokens + $est_tokens, false);
		}
	}

	/**
	 * Get usage statistics for the admin dashboard.
	 *
	 * @return array
	 */
	public static function get_usage_stats()
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot = isset($settings['chatbot']) ? $settings['chatbot'] : [];

		$today_reqs   = (int) get_option('dctc_ai_site_reqs_' . date('Ymd'), 0);
		$month_reqs   = (int) get_option('dctc_ai_site_reqs_' . date('Ym'), 0);
		$month_tokens = (int) get_option('dctc_ai_site_tokens_' . date('Ym'), 0);
		$monthly_budget = isset($bot['monthly_request_budget']) ? intval($bot['monthly_request_budget']) : 5000;

		$percent = ($monthly_budget > 0) ? min(100, round(($month_reqs / $monthly_budget) * 100, 1)) : 0;

		return [
			'today_requests'         => $today_reqs,
			'month_requests'         => $month_reqs,
			'month_tokens'           => $month_tokens,
			'monthly_budget'         => $monthly_budget,
			'budget_percent'         => $percent,
			'visitor_daily_limit'    => isset($bot['visitor_daily_message_limit']) ? intval($bot['visitor_daily_message_limit']) : 50,
			'enable_usage_limits'    => isset($bot['enable_usage_limits']) ? (bool) $bot['enable_usage_limits'] : true,
		];
	}

	/**
	 * Dispatch email alert when budget crosses threshold.
	 *
	 * @param int   $threshold Threshold percentage (80 or 100).
	 * @param int   $current Current request count.
	 * @param int   $budget Monthly budget.
	 * @param array $bot Chatbot settings.
	 * @return void
	 */
	private static function maybe_send_alert($threshold, $current, $budget, $bot)
	{
		$alerts_enabled = isset($bot['enable_budget_email_alerts']) ? (bool) $bot['enable_budget_email_alerts'] : true;
		if (!$alerts_enabled) {
			return;
		}

		$flag_key = 'dctc_ai_budget_alert_' . date('Ym') . '_' . $threshold;
		if (get_transient($flag_key)) {
			return; // Already sent alert this month for this threshold
		}

		$to = !empty($bot['alert_email']) ? sanitize_email($bot['alert_email']) : get_option('admin_email');
		if (empty($to) || !is_email($to)) {
			return;
		}

		$site_name = get_bloginfo('name');
		$subject   = sprintf(
			/* translators: 1: Site name, 2: Percentage */
			__('[%1$s] AI Chatbot Budget Alert: %2$d%% Consumed', 'dragwyb-click-to-chat'),
			$site_name,
			$threshold
		);

		$message = sprintf(
			/* translators: 1: Site name, 2: Percentage, 3: Current requests, 4: Budget limit, 5: Settings URL */
			__("Hello Admin,\n\nYour AI Chatbot on %1$s has reached %2$d%% of its monthly request budget.\n\n- Current Usage: %3$d requests\n- Monthly Budget Limit: %4$d requests\n\nYou can review or adjust your budget limits at:\n%5$s\n\nBest regards,\nDragwyb AI Assistant", 'dragwyb-click-to-chat'),
			$site_name,
			$threshold,
			$current,
			$budget,
			admin_url('admin.php?page=dragwyb-click-to-chat-ai')
		);

		wp_mail($to, $subject, $message);
		set_transient($flag_key, 1, MONTH_IN_SECONDS);
	}
}
