<?php
/**
 * DCTC AI Admin Copilot
 *
 * Dedicated AI assistant for WordPress administrators to query site/store
 * analytics, discover unanswered visitor questions, assess lead conversion
 * performance, and uncover Knowledge Base content gaps.
 *
 * @package Dragwyb_Click_To_Chat
 */

if (!defined('ABSPATH')) {
	exit;
}

class DCTC_AI_Admin_Copilot
{
	/**
	 * Get aggregated live analytics summary across chat sessions, leads, and KB documents.
	 *
	 * @return array
	 */
	public static function get_analytics_summary()
	{
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'dctc_ai_sessions';
		$leads_table    = $wpdb->prefix . 'dctc_ai_leads';
		$kb_table       = $wpdb->prefix . 'dctc_ai_rag_documents';

		$summary = [
			'total_sessions'       => 0,
			'total_leads'          => 0,
			'total_kb_docs'        => 0,
			'recent_questions'     => [],
			'unanswered_questions' => [],
			'top_lead_sources'     => [],
			'intent_distribution'  => [],
			'sentiment_summary'    => [
				'positive' => 0,
				'neutral'  => 0,
				'negative' => 0,
			],
		];

		// Check sessions
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $sessions_table));
		if ($table_exists === $sessions_table) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$summary['total_sessions'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$sessions_table}`");

			// Recent session questions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$recent_sessions = $wpdb->get_results("SELECT content, summary, sentiment, intent_tag FROM `{$sessions_table}` ORDER BY id DESC LIMIT 25", ARRAY_A);
			if (!empty($recent_sessions)) {
				foreach ($recent_sessions as $row) {
					$sent = !empty($row['sentiment']) ? strtolower($row['sentiment']) : 'neutral';
					if (isset($summary['sentiment_summary'][$sent])) {
						$summary['sentiment_summary'][$sent]++;
					}

					$intent = !empty($row['intent_tag']) ? $row['intent_tag'] : 'general';
					$summary['intent_distribution'][$intent] = ($summary['intent_distribution'][$intent] ?? 0) + 1;

					// Extract first user question from JSON content
					$raw_content = json_decode($row['content'] ?? '', true);
					if (is_array($raw_content)) {
						foreach ($raw_content as $msg) {
							if (isset($msg['role']) && $msg['role'] === 'user' && !empty($msg['content'])) {
								$q_text = wp_strip_all_tags($msg['content']);
								if (strlen($q_text) > 3 && !in_array($q_text, $summary['recent_questions'], true)) {
									$summary['recent_questions'][] = mb_substr($q_text, 0, 150);
								}
								break;
							}
						}
					}
				}
			}

			// Unanswered / negative sentiment / fallback questions
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$unanswered_rows = $wpdb->get_results("SELECT content, summary FROM `{$sessions_table}` WHERE sentiment = 'negative' OR intent_tag IN ('fallback', 'unanswered', 'unknown', 'help') ORDER BY id DESC LIMIT 15", ARRAY_A);
			if (!empty($unanswered_rows)) {
				foreach ($unanswered_rows as $row) {
					$raw_content = json_decode($row['content'] ?? '', true);
					if (is_array($raw_content)) {
						foreach ($raw_content as $msg) {
							if (isset($msg['role']) && $msg['role'] === 'user' && !empty($msg['content'])) {
								$q_text = wp_strip_all_tags($msg['content']);
								if (!in_array($q_text, $summary['unanswered_questions'], true)) {
									$summary['unanswered_questions'][] = mb_substr($q_text, 0, 150);
								}
								break;
							}
						}
					}
				}
			}
		}

		// Check leads
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$leads_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $leads_table));
		if ($leads_table_exists === $leads_table) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$summary['total_leads'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$leads_table}`");

			// Top lead source URLs
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sources = $wpdb->get_results("SELECT source_url, COUNT(*) as count FROM `{$leads_table}` WHERE source_url != '' GROUP BY source_url ORDER BY count DESC LIMIT 8", ARRAY_A);
			if (!empty($sources)) {
				$summary['top_lead_sources'] = $sources;
			}
		}

		// Check KB documents
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$kb_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $kb_table));
		if ($kb_exists === $kb_table) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$summary['total_kb_docs'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$kb_table}`");
		}

		// Limit list arrays
		$summary['recent_questions'] = array_slice($summary['recent_questions'], 0, 10);
		$summary['unanswered_questions'] = array_slice($summary['unanswered_questions'], 0, 8);

		return $summary;
	}

	/**
	 * Process a query from the admin through the AI Copilot.
	 *
	 * @param string $admin_prompt Prompt/question from the administrator.
	 * @return array
	 * @throws \Exception
	 */
	public static function execute_copilot_chat($admin_prompt)
	{
		$settings = DCTC_AI_Settings_Handler::dctc_ai_get_all_settings();
		$bot      = isset($settings['chatbot']) ? $settings['chatbot'] : [];
		$models   = isset($settings['models']) ? $settings['models'] : [];

		$provider_id = !empty($bot['default_provider']) ? $bot['default_provider'] : 'openai';
		$model_id    = !empty($models[$provider_id]) ? $models[$provider_id] : 'gpt-4o-mini';

		// Gather live site intelligence
		$stats = self::get_analytics_summary();

		$site_name = get_bloginfo('name');
		$site_url  = home_url();

		$analytics_context = "LIVE SITE & CHATBOT INTELLIGENCE FOR: {$site_name} ({$site_url})\n";
		$analytics_context .= "- Total Chat Sessions Recorded: {$stats['total_sessions']}\n";
		$analytics_context .= "- Total Captured AI Leads: {$stats['total_leads']}\n";
		$analytics_context .= "- Indexed Knowledge Base Documents: {$stats['total_kb_docs']}\n";
		$analytics_context .= "- Visitor Sentiments: Positive: {$stats['sentiment_summary']['positive']}, Neutral: {$stats['sentiment_summary']['neutral']}, Negative/Frustrated: {$stats['sentiment_summary']['negative']}\n";

		if (!empty($stats['intent_distribution'])) {
			$analytics_context .= "- Visitor Intent Breakdown:\n";
			foreach ($stats['intent_distribution'] as $tag => $count) {
				$analytics_context .= "  * {$tag}: {$count} sessions\n";
			}
		}

		if (!empty($stats['top_lead_sources'])) {
			$analytics_context .= "- Top Converting Pages (Lead Generation):\n";
			foreach ($stats['top_lead_sources'] as $src) {
				$analytics_context .= "  * {$src['source_url']} ({$src['count']} leads)\n";
			}
		}

		if (!empty($stats['recent_questions'])) {
			$analytics_context .= "- Recent Frequent Visitor Questions:\n";
			foreach ($stats['recent_questions'] as $q) {
				$analytics_context .= "  * \"{$q}\"\n";
			}
		}

		if (!empty($stats['unanswered_questions'])) {
			$analytics_context .= "- Flagged Unanswered / Fallback Inquiries (Content Gaps):\n";
			foreach ($stats['unanswered_questions'] as $uq) {
				$analytics_context .= "  * \"{$uq}\"\n";
			}
		}

		$system_prompt = "You are the Dragwyb AI Admin Copilot, an expert e-commerce and WordPress optimization strategist. " .
			"You assist the website administrator by providing data-driven insights, actionable conversion optimization advice, content suggestions for their Knowledge Base, and answering analytical questions about what their site visitors are seeking.\n\n" .
			"CRITICAL INSTRUCTIONS:\n" .
			"1. Base your insights and recommendations directly on the provided Live Site & Chatbot Intelligence.\n" .
			"2. Format your response cleanly using GitHub-style Markdown with clear headings, bullet points, and actionable steps.\n" .
			"3. If the admin asks what content/KB to add, provide exact, concrete FAQ and article titles and bullet point outlines.\n" .
			"4. Be concise, professional, insightful, and strategic.\n\n" .
			"Current System Intelligence:\n" . $analytics_context;

		require_once DCTC_PLUGIN_DIR . 'includes/ai/ai-providers/class-dctc-ai-provider-manager.php';
		$manager = DCTC_AI_Provider_Manager::get_instance();

		$response = $manager->chat_with_fallback(
			$admin_prompt,
			$system_prompt,
			$provider_id,
			$model_id,
			[
				'temperature' => 0.5,
				'max_tokens'  => 1200,
			]
		);

		return [
			'message'  => $response['message'],
			'provider' => $response['provider'],
			'model'    => $response['model'],
			'stats'    => $stats,
		];
	}
}
