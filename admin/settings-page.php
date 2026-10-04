<?php
/**
 * Click to Chat - Dedicated Settings Page
 *
 * Header contains only 2 tabs:
 * 1. General (Feature enable/disable for Channels, AI Assistant, Support Center with inside drawers)
 * 2. Import / Export (Settings & Live Application Data backup/restore)
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dctc_settings = get_option( 'dctc_settings', array() );

// 1. Channels status
$dctc_channels_enabled = ! isset( $dctc_settings['channels_enabled'] ) || '1' === $dctc_settings['channels_enabled'];

// 2. AI Assistant status
$dctc_ai_settings = get_option( 'dctc_ai_chat_assistant_settings', array() );
$dctc_ai_enabled  = ! empty( $dctc_ai_settings['display']['entire_site'] );

// 3. Support Center status
$dctc_support_settings = get_option( 'dctc_support_settings', array() );
$dctc_support_enabled  = ! empty( $dctc_support_settings['enabled'] );

// Default active tab (from URL or default to 'general')
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$dctc_active_tab = isset( $_GET['tab'] ) && 'import-export' === $_GET['tab'] ? 'import-export' : 'general';
?>

<div class="dctc-admin-wrap dctc-settings-wrap">

	<!-- Success / Notice Toast -->
	<div class="dctc-success-message"></div>

	<!-- Header with ONLY Two Tabs -->
	<div class="dctc-header dctc-settings-header">
		<div class="dctc-settings-tabs-wrapper">
			<ul class="dctc-tabs dctc-settings-two-tabs">
				<li>
					<a href="#general"
						class="dctc-tab dctc-settings-tab-btn <?php echo 'general' === $dctc_active_tab ? 'active' : ''; ?>"
						data-tab="general">
						<span>⚙️ <?php esc_html_e( '1. General', 'dragwyb-click-to-chat' ); ?></span>
					</a>
				</li>
				<li>
					<a href="#import-export"
						class="dctc-tab dctc-settings-tab-btn <?php echo 'import-export' === $dctc_active_tab ? 'active' : ''; ?>"
						data-tab="import-export">
						<span>📦 <?php esc_html_e( '2. Import / Export', 'dragwyb-click-to-chat' ); ?></span>
					</a>
				</li>
			</ul>
		</div>
	</div>

	<!-- Main Container -->
	<div class="dctc-settings-main-container">

		<!-- ========================================== -->
		<!-- TAB 1: GENERAL (Module Activation & Drawers) -->
		<!-- ========================================== -->
		<div id="dctc-tab-general" class="dctc-settings-tab-content <?php echo 'general' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'general' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<div class="dctc-settings-card-box">
				<div class="dctc-settings-card-header">
					<div>
						<h2 class="dctc-settings-card-title"><?php esc_html_e( 'Feature & Module Management', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-settings-card-sub"><?php esc_html_e( 'Enable or disable the core plugin features independently. When enabled, direct configuration tools and shortcodes appear inside each module.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
					<button type="button" id="dctc-general-save-btn" class="dctc-btn dctc-btn-primary">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
							<path d="M15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333V4.16667C2.5 3.72464 2.67559 3.30072 2.98816 2.98816C3.30072 2.67559 3.72464 2.5 4.16667 2.5H13.3333L17.5 6.66667V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5Z" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M14.1666 17.5V10.8334H5.83331V17.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M5.83331 2.5V6.66667H12.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<?php esc_html_e( 'Save Changes', 'dragwyb-click-to-chat' ); ?>
					</button>
				</div>

				<!-- Feature 1: Social Channels Widget -->
				<div class="dctc-modern-module-card <?php echo $dctc_channels_enabled ? 'is-enabled' : 'is-disabled'; ?>" id="dctc-card-channels">
					<div class="dctc-module-header">
						<div class="dctc-module-header-left">
							<div class="dctc-module-icon-wrap" style="background: #fdf2f8; color: #db2777;">
								📱
							</div>
							<div>
								<div class="dctc-module-title-row">
									<strong class="dctc-module-title"><?php esc_html_e( 'Social Channels Widget', 'dragwyb-click-to-chat' ); ?></strong>
									<span id="dctc-badge-channels" class="dctc-status-pill <?php echo $dctc_channels_enabled ? 'is-active' : 'is-inactive'; ?>">
										<?php echo $dctc_channels_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
									</span>
								</div>
								<p class="dctc-module-desc">
									<?php esc_html_e( 'Display the floating multi-channel action buttons (WhatsApp, Facebook, Phone, Email, Telegram, Instagram, and more) across your website pages.', 'dragwyb-click-to-chat' ); ?>
								</p>
							</div>
						</div>
						<div class="dctc-module-header-right">
							<label class="dctc-ios-switch" for="dctc_gen_channels_enabled">
								<input type="checkbox" id="dctc_gen_channels_enabled" name="channels_enabled" value="1" <?php checked( $dctc_channels_enabled, true ); ?> />
								<span class="dctc-ios-slider"></span>
							</label>
						</div>
					</div>

					<!-- Inside Drawer (Revealed when enabled) -->
					<div class="dctc-module-drawer" id="dctc-drawer-channels" style="<?php echo $dctc_channels_enabled ? 'display: block;' : 'display: none;'; ?>">
						<div class="dctc-drawer-inner">
							<div class="dctc-drawer-row">
								<div class="dctc-drawer-info">
									<span class="dctc-drawer-label">📋 <?php esc_html_e( 'Shortcode Placement:', 'dragwyb-click-to-chat' ); ?></span>
									<span class="dctc-drawer-hint"><?php esc_html_e( 'Place widget inside any page, template, or block editor:', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-shortcode-copy-box">
									<code>[dctc-widget]</code>
									<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc-widget]">
										<span class="dctc-copy-text"><?php esc_html_e( 'Copy', 'dragwyb-click-to-chat' ); ?></span>
									</button>
								</div>
							</div>
							<div class="dctc-drawer-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-channels' ) ); ?>" class="dctc-btn-action">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
									<?php esc_html_e( 'Configure Channels & Layout &rarr;', 'dragwyb-click-to-chat' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

				<!-- Feature 2: AI Assistant & Autonomous Chatbot -->
				<div class="dctc-modern-module-card <?php echo $dctc_ai_enabled ? 'is-enabled' : 'is-disabled'; ?>" id="dctc-card-ai">
					<div class="dctc-module-header">
						<div class="dctc-module-header-left">
							<div class="dctc-module-icon-wrap" style="background: #eef2ff; color: #4f46e5;">
								🤖
							</div>
							<div>
								<div class="dctc-module-title-row">
									<strong class="dctc-module-title"><?php esc_html_e( 'AI Assistant & Autonomous Chatbot', 'dragwyb-click-to-chat' ); ?></strong>
									<span id="dctc-badge-ai" class="dctc-status-pill <?php echo $dctc_ai_enabled ? 'is-active' : 'is-inactive'; ?>">
										<?php echo $dctc_ai_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
									</span>
								</div>
								<p class="dctc-module-desc">
									<?php esc_html_e( 'Autonomous customer-facing AI assistant powered by OpenAI/Gemini/Anthropic with website knowledge base RAG retrieval, lead capture scoring, and live human agent handoff.', 'dragwyb-click-to-chat' ); ?>
								</p>
							</div>
						</div>
						<div class="dctc-module-header-right">
							<label class="dctc-ios-switch" for="dctc_gen_ai_enabled">
								<input type="checkbox" id="dctc_gen_ai_enabled" name="ai_assistant_enabled" value="1" <?php checked( $dctc_ai_enabled, true ); ?> />
								<span class="dctc-ios-slider"></span>
							</label>
						</div>
					</div>

					<!-- Inside Drawer (Revealed when enabled) -->
					<div class="dctc-module-drawer" id="dctc-drawer-ai" style="<?php echo $dctc_ai_enabled ? 'display: block;' : 'display: none;'; ?>">
						<div class="dctc-drawer-inner">
							<div class="dctc-drawer-row">
								<div class="dctc-drawer-info">
									<span class="dctc-drawer-label">🤖 <?php esc_html_e( 'Chatbot Embed Shortcode:', 'dragwyb-click-to-chat' ); ?></span>
									<span class="dctc-drawer-hint"><?php esc_html_e( 'Embed the interactive AI chat window directly on specific pages or landing sites:', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-shortcode-copy-box">
									<code>[dctc_ai]</code>
									<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc_ai]">
										<span class="dctc-copy-text"><?php esc_html_e( 'Copy', 'dragwyb-click-to-chat' ); ?></span>
									</button>
								</div>
							</div>
							<div class="dctc-drawer-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ); ?>" class="dctc-btn-action">
									<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
									<?php esc_html_e( 'Open AI Assistant Dashboard &rarr;', 'dragwyb-click-to-chat' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

				<!-- Feature 3: Help Support Desk -->
				<div class="dctc-modern-module-card <?php echo $dctc_support_enabled ? 'is-enabled' : 'is-disabled'; ?>" id="dctc-card-support">
					<div class="dctc-module-header">
						<div class="dctc-module-header-left">
							<div class="dctc-module-icon-wrap" style="background: #f0fdf4; color: #16a34a;">
								🛡️
							</div>
							<div>
								<div class="dctc-module-title-row">
									<strong class="dctc-module-title"><?php esc_html_e( 'Support Center & Helpdesk Ticketing', 'dragwyb-click-to-chat' ); ?></strong>
									<span id="dctc-badge-support" class="dctc-status-pill <?php echo $dctc_support_enabled ? 'is-active' : 'is-inactive'; ?>">
										<?php echo $dctc_support_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
									</span>
								</div>
								<p class="dctc-module-desc">
									<?php esc_html_e( 'Dedicated standalone support workspace with ticket queues, staff workload management, WooCommerce customer profiles, category routing, and human-in-the-loop controls.', 'dragwyb-click-to-chat' ); ?>
								</p>
							</div>
						</div>
						<div class="dctc-module-header-right">
							<label class="dctc-ios-switch" for="dctc_gen_support_enabled">
								<input type="checkbox" id="dctc_gen_support_enabled" name="support_center_enabled" value="1" <?php checked( $dctc_support_enabled, true ); ?> />
								<span class="dctc-ios-slider"></span>
							</label>
						</div>
					</div>

					<!-- Inside Drawer (Revealed when enabled) -->
					<div class="dctc-module-drawer" id="dctc-drawer-support" style="<?php echo $dctc_support_enabled ? 'display: block;' : 'display: none;'; ?>">
						<div class="dctc-drawer-inner">
							<div class="dctc-drawer-row">
								<div class="dctc-drawer-info">
									<span class="dctc-drawer-label">🎫 <?php esc_html_e( 'Helpdesk Workspace:', 'dragwyb-click-to-chat' ); ?></span>
									<span class="dctc-drawer-hint"><?php esc_html_e( 'Access staff ticketing, agent roster, response templates, and live queues:', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-drawer-actions" style="margin: 0;">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-support-center' ) ); ?>" class="dctc-btn-action">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
										<?php esc_html_e( 'Open Support Center Workspace &rarr;', 'dragwyb-click-to-chat' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>

		</div>

		<!-- ========================================== -->
		<!-- TAB 2: IMPORT / EXPORT (Settings & Live Data) -->
		<!-- ========================================== -->
		<div id="dctc-tab-import-export" class="dctc-settings-tab-content <?php echo 'import-export' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'import-export' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<!-- PART 1: Configuration Settings Import / Export -->
			<div class="dctc-settings-card-box" style="margin-bottom: 30px;">
				<div class="dctc-settings-card-header" style="border-bottom: 1px solid #f3f4f6; padding-bottom: 14px; margin-bottom: 24px;">
					<div>
						<div style="display: flex; align-items: center; gap: 8px;">
							<span style="font-size: 20px;">⚙️</span>
							<h2 class="dctc-settings-card-title"><?php esc_html_e( 'Configuration Settings Import / Export', 'dragwyb-click-to-chat' ); ?></h2>
						</div>
						<p class="dctc-settings-card-sub"><?php esc_html_e( 'Back up or migrate module configurations across your sites. Sensitive API keys and credentials are safe and omitted.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
				</div>

				<div class="dctc-ie-grid">

					<!-- Export Settings Box -->
					<div class="dctc-ie-card">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="dctc_export_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 class="dctc-ie-card-heading">
								<span>📤</span> <?php esc_html_e( 'Export Settings', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 16px;">
								<label class="dctc-ie-label">
									<?php esc_html_e( 'Include Settings Modules:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div class="dctc-checkbox-group">
									<label class="dctc-checkbox-item">
										<input name="dctc_modules[]" type="checkbox" value="channels" checked="checked" />
										<span>📱 <strong><?php esc_html_e( 'Channels Settings', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>
									<label class="dctc-checkbox-item">
										<input name="dctc_modules[]" type="checkbox" value="ai" checked="checked" />
										<span>🤖 <strong><?php esc_html_e( 'AI Assistant Settings', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>
									<label class="dctc-checkbox-item">
										<input name="dctc_modules[]" type="checkbox" value="support" checked="checked" />
										<span>🛡️ <strong><?php esc_html_e( 'Support Center Settings', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>
								</div>
							</div>

							<div style="margin-bottom: 22px;">
								<label class="dctc-ie-label">
									<?php esc_html_e( 'Format:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div class="dctc-radio-group">
									<label class="dctc-radio-item">
										<input name="dctc_format" type="radio" value="json" checked="checked" />
										<span><strong>JSON</strong> <small style="color:#8e44ad;">(Recommended)</small></span>
									</label>
									<label class="dctc-radio-item">
										<input name="dctc_format" type="radio" value="csv" />
										<span>CSV</span>
									</label>
									<label class="dctc-radio-item">
										<input name="dctc_format" type="radio" value="sql" />
										<span>SQL</span>
									</label>
								</div>
							</div>

							<button type="submit" class="dctc-btn dctc-btn-primary" style="width: 100%; justify-content: center; padding: 10px 16px;">
								<?php esc_html_e( 'Download Settings Backup', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

					<!-- Import Settings Box -->
					<div class="dctc-ie-card">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<input type="hidden" name="action" value="dctc_import_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 class="dctc-ie-card-heading">
								<span>📥</span> <?php esc_html_e( 'Import Settings', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 16px;">
								<label class="dctc-ie-label">
									<?php esc_html_e( 'Apply to Modules:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div class="dctc-checkbox-group">
									<label class="dctc-checkbox-item">
										<input name="dctc_import_modules[]" type="checkbox" value="channels" checked="checked" />
										<span>📱 <?php esc_html_e( 'Channels', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<label class="dctc-checkbox-item">
										<input name="dctc_import_modules[]" type="checkbox" value="ai" checked="checked" />
										<span>🤖 <?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<label class="dctc-checkbox-item">
										<input name="dctc_import_modules[]" type="checkbox" value="support" checked="checked" />
										<span>🛡️ <?php esc_html_e( 'Support Center', 'dragwyb-click-to-chat' ); ?></span>
									</label>
								</div>
							</div>

							<div style="margin-bottom: 22px;">
								<label for="dctc_settings_file" class="dctc-ie-label">
									<?php esc_html_e( 'Select Backup File (.json, .csv, .sql):', 'dragwyb-click-to-chat' ); ?>
								</label>
								<input type="file" name="dctc_import_file" id="dctc_settings_file" accept=".json,.csv,.sql,application/json,text/csv,application/sql" required class="dctc-file-input" />
							</div>

							<button type="submit" class="dctc-btn dctc-btn-secondary" style="width: 100%; justify-content: center; padding: 10px 16px;">
								<?php esc_html_e( 'Restore Settings', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

				</div>
			</div>

			<!-- PART 2: Live Application Data Import / Export -->
			<div class="dctc-settings-card-box">
				<div class="dctc-settings-card-header" style="border-bottom: 1px solid #f3f4f6; padding-bottom: 14px; margin-bottom: 24px;">
					<div>
						<div style="display: flex; align-items: center; gap: 8px;">
							<span style="font-size: 20px;">📊</span>
							<h2 class="dctc-settings-card-title"><?php esc_html_e( 'Application Data Import / Export', 'dragwyb-click-to-chat' ); ?></h2>
						</div>
						<p class="dctc-settings-card-sub"><?php esc_html_e( 'Export or import live customer conversations, captured sales leads, and support ticketing records.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
				</div>

				<div class="dctc-ie-grid">

					<!-- Export Data Box -->
					<div class="dctc-ie-card">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="dctc_export_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 class="dctc-ie-card-heading">
								<span>📤</span> <?php esc_html_e( 'Export Live Data', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 22px;">
								<label class="dctc-ie-label">
									<?php esc_html_e( 'Select Datasets to Export:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div class="dctc-checkbox-group">
									<label class="dctc-checkbox-item">
										<input name="dctc_modules[]" type="checkbox" value="ai_data" checked="checked" />
										<span>💬 <strong><?php esc_html_e( 'AI Chatbot Conversations & Leads', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>

									<?php if ( $dctc_support_enabled ) : ?>
										<label class="dctc-checkbox-item">
											<input name="dctc_modules[]" type="checkbox" value="support_data" checked="checked" />
											<span>🎫 <strong><?php esc_html_e( 'Support Help Desk Tickets & Threads', 'dragwyb-click-to-chat' ); ?></strong></span>
										</label>
									<?php else : ?>
										<div style="font-size: 12px; color: #9ca3af; padding: 4px 0;">
											<em><?php esc_html_e( '(Enable Support Center module in General tab to export tickets)', 'dragwyb-click-to-chat' ); ?></em>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<input type="hidden" name="dctc_format" value="json" />

							<button type="submit" class="dctc-btn dctc-btn-primary" style="width: 100%; justify-content: center; padding: 10px 16px;">
								<?php esc_html_e( 'Download Live Data Backup (.JSON)', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

					<!-- Import Data Box -->
					<div class="dctc-ie-card">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<input type="hidden" name="action" value="dctc_import_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 class="dctc-ie-card-heading">
								<span>📥</span> <?php esc_html_e( 'Import Live Data', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 16px;">
								<label class="dctc-ie-label">
									<?php esc_html_e( 'Restore Datasets:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div class="dctc-checkbox-group">
									<label class="dctc-checkbox-item">
										<input name="dctc_import_modules[]" type="checkbox" value="ai_data" checked="checked" />
										<span>💬 <?php esc_html_e( 'AI Chat Sessions & Leads', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<?php if ( $dctc_support_enabled ) : ?>
										<label class="dctc-checkbox-item">
											<input name="dctc_import_modules[]" type="checkbox" value="support_data" checked="checked" />
											<span>🎫 <?php esc_html_e( 'Support Desk Tickets & Messages', 'dragwyb-click-to-chat' ); ?></span>
										</label>
									<?php endif; ?>
								</div>
							</div>

							<div style="margin-bottom: 22px;">
								<label for="dctc_data_file" class="dctc-ie-label">
									<?php esc_html_e( 'Select JSON Data File:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<input type="file" name="dctc_import_file" id="dctc_data_file" accept=".json,application/json" required class="dctc-file-input" />
							</div>

							<button type="submit" class="dctc-btn dctc-btn-secondary" style="width: 100%; justify-content: center; padding: 10px 16px;">
								<?php esc_html_e( 'Restore Live Data', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

				</div>
			</div>

		</div>

	</div>

</div>
