<?php
/**
 * Click to Chat - Dedicated Settings Page
 *
 * Header contains only 2 tabs:
 * 1. General (Feature enable/disable for Channels, AI Assistant, Support Center)
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
	<div class="dctc-header">
		<ul class="dctc-tabs dctc-settings-two-tabs" style="max-width: 600px;">
			<li>
				<a href="#general"
					class="dctc-settings-tab-btn <?php echo 'general' === $dctc_active_tab ? 'active' : ''; ?>"
					data-tab="general">
					<span>⚙️ <?php esc_html_e( '1. General', 'dragwyb-click-to-chat' ); ?></span>
				</a>
			</li>
			<li>
				<a href="#import-export"
					class="dctc-settings-tab-btn <?php echo 'import-export' === $dctc_active_tab ? 'active' : ''; ?>"
					data-tab="import-export">
					<span>📦 <?php esc_html_e( '2. Import / Export', 'dragwyb-click-to-chat' ); ?></span>
				</a>
			</li>
		</ul>
	</div>

	<!-- Main Container -->
	<div class="dctc-settings-main-container" style="max-width: 1050px; margin: 30px auto; padding: 0 20px;">

		<!-- ========================================== -->
		<!-- TAB 1: GENERAL (Module Activation & Master Toggles) -->
		<!-- ========================================== -->
		<div id="dctc-tab-general" class="dctc-settings-tab-content <?php echo 'general' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'general' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 25px;">
				<div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 16px; margin-bottom: 24px;">
					<div>
						<h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px;"><?php esc_html_e( 'Feature & Module Management', 'dragwyb-click-to-chat' ); ?></h2>
						<p style="color: #6b7280; font-size: 13.5px; margin: 0;"><?php esc_html_e( 'Enable or disable the core plugin features independently.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
					<button type="button" id="dctc-general-save-btn" class="dctc-btn dctc-btn-primary" style="padding: 9px 20px;">
						<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 6px;">
							<path d="M15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333V4.16667C2.5 3.72464 2.67559 3.30072 2.98816 2.98816C3.30072 2.67559 3.72464 2.5 4.16667 2.5H13.3333L17.5 6.66667V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5Z" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M14.1666 17.5V10.8334H5.83331V17.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M5.83331 2.5V6.66667H12.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<?php esc_html_e( 'Save Changes', 'dragwyb-click-to-chat' ); ?>
					</button>
				</div>

				<!-- Feature 1: Social Channels -->
				<div style="background: #fdfdfd; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 22px; margin-bottom: 16px; transition: all 0.2s;">
					<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
						<div style="flex: 1;">
							<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
								<span style="font-size: 24px;">📱</span>
								<strong style="font-size: 16px; color: #111827;"><?php esc_html_e( 'Social Channels Widget', 'dragwyb-click-to-chat' ); ?></strong>
								<span id="dctc-badge-channels" style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 12px; <?php echo $dctc_channels_enabled ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
									<?php echo $dctc_channels_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
								</span>
							</div>
							<p style="color: #6b7280; font-size: 13.5px; margin: 0 0 12px; line-height: 1.5;">
								<?php esc_html_e( 'Display the floating multi-channel action button (WhatsApp, Facebook, Phone, Email, Telegram, Instagram, and more) across your website pages.', 'dragwyb-click-to-chat' ); ?>
							</p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-channels' ) ); ?>" class="button button-secondary" style="font-size: 12px; font-weight: 600;">
								<?php esc_html_e( 'Configure Channels &rarr;', 'dragwyb-click-to-chat' ); ?>
							</a>
						</div>
						<div>
							<label style="display: flex; align-items: center; cursor: pointer; gap: 8px;">
								<input type="checkbox" id="dctc_gen_channels_enabled" name="channels_enabled" value="1" <?php checked( $dctc_channels_enabled, true ); ?> style="width: 20px; height: 20px; accent-color: #8e44ad;" />
								<span style="font-weight: 600; color: #374151; font-size: 14px;"><?php esc_html_e( 'Enable Channels', 'dragwyb-click-to-chat' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Feature 2: AI Assistant -->
				<div style="background: #fdfdfd; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 22px; margin-bottom: 16px; transition: all 0.2s;">
					<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
						<div style="flex: 1;">
							<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
								<span style="font-size: 24px;">🤖</span>
								<strong style="font-size: 16px; color: #111827;"><?php esc_html_e( 'AI Assistant & Autonomous Chatbot', 'dragwyb-click-to-chat' ); ?></strong>
								<span id="dctc-badge-ai" style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 12px; <?php echo $dctc_ai_enabled ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
									<?php echo $dctc_ai_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
								</span>
							</div>
							<p style="color: #6b7280; font-size: 13.5px; margin: 0 0 12px; line-height: 1.5;">
								<?php esc_html_e( 'Autonomous customer-facing AI assistant powered by OpenAI/Gemini/Anthropic with website knowledge base RAG retrieval, lead capture scoring, and live human agent handoff.', 'dragwyb-click-to-chat' ); ?>
							</p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ); ?>" class="button button-secondary" style="font-size: 12px; font-weight: 600;">
								<?php esc_html_e( 'Open AI Assistant Dashboard &rarr;', 'dragwyb-click-to-chat' ); ?>
							</a>
						</div>
						<div>
							<label style="display: flex; align-items: center; cursor: pointer; gap: 8px;">
								<input type="checkbox" id="dctc_gen_ai_enabled" name="ai_assistant_enabled" value="1" <?php checked( $dctc_ai_enabled, true ); ?> style="width: 20px; height: 20px; accent-color: #8e44ad;" />
								<span style="font-weight: 600; color: #374151; font-size: 14px;"><?php esc_html_e( 'Enable AI Assistant', 'dragwyb-click-to-chat' ); ?></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Feature 3: Help Support Desk -->
				<div style="background: #fdfdfd; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 22px; transition: all 0.2s;">
					<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
						<div style="flex: 1;">
							<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
								<span style="font-size: 24px;">🛡️</span>
								<strong style="font-size: 16px; color: #111827;"><?php esc_html_e( 'Support Center & Helpdesk Ticketing', 'dragwyb-click-to-chat' ); ?></strong>
								<span id="dctc-badge-support" style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 12px; <?php echo $dctc_support_enabled ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
									<?php echo $dctc_support_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
								</span>
							</div>
							<p style="color: #6b7280; font-size: 13.5px; margin: 0 0 12px; line-height: 1.5;">
								<?php esc_html_e( 'Dedicated standalone support workspace with ticket queues, staff workload management, WooCommerce customer profiles, category routing, and human-in-the-loop controls.', 'dragwyb-click-to-chat' ); ?>
							</p>
							<?php if ( $dctc_support_enabled ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-support-center' ) ); ?>" class="button button-secondary" style="font-size: 12px; font-weight: 600;">
									<?php esc_html_e( 'Open Support Center &rarr;', 'dragwyb-click-to-chat' ); ?>
								</a>
							<?php endif; ?>
						</div>
						<div>
							<label style="display: flex; align-items: center; cursor: pointer; gap: 8px;">
								<input type="checkbox" id="dctc_gen_support_enabled" name="support_center_enabled" value="1" <?php checked( $dctc_support_enabled, true ); ?> style="width: 20px; height: 20px; accent-color: #8e44ad;" />
								<span style="font-weight: 600; color: #374151; font-size: 14px;"><?php esc_html_e( 'Enable Support Center', 'dragwyb-click-to-chat' ); ?></span>
							</label>
						</div>
					</div>
				</div>

			</div>

			<!-- Shortcode Info Box -->
			<div class="dctc-info-box" style="background: #f3e8ff; border-left-color: #8e44ad; border-radius: 10px; margin-bottom: 25px; padding: 18px 22px;">
				<p style="color: #5b21b6; font-size: 15px; margin-bottom: 8px;">
					<strong>📋 <?php esc_html_e( 'Shortcode Usage:', 'dragwyb-click-to-chat' ); ?></strong>
				</p>
				<p style="color: #5b21b6; font-size: 13.5px; margin: 0 0 10px;">
					<?php esc_html_e( 'To manually place the multi-channel widget inside a specific page, template, or post:', 'dragwyb-click-to-chat' ); ?>
				</p>
				<p style="margin: 0; display: flex; align-items: center; gap: 10px;">
					<code style="font-size: 15px; padding: 6px 14px; background: #e9d5ff; border-radius: 6px; border: 1px solid #d8b4fe; color: #6b21a8; font-family: monospace;">[dctc-widget]</code>
					<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc-widget]" style="background: white; border: 1px solid #e5e7eb; color: #374151; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s;">
						<span class="dctc-copy-text"><?php esc_html_e( 'Copy', 'dragwyb-click-to-chat' ); ?></span>
					</button>
				</p>
			</div>

		</div>

		<!-- ========================================== -->
		<!-- TAB 2: IMPORT / EXPORT (Settings & Live Data) -->
		<!-- ========================================== -->
		<div id="dctc-tab-import-export" class="dctc-settings-tab-content <?php echo 'import-export' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'import-export' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<!-- PART 1: Configuration Settings Import / Export -->
			<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 30px;">
				<div style="margin-bottom: 24px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<span style="font-size: 22px;">⚙️</span>
						<h2 style="font-size: 19px; font-weight: 700; color: #111827; margin: 0;"><?php esc_html_e( 'Configuration Settings Import / Export', 'dragwyb-click-to-chat' ); ?></h2>
					</div>
					<p style="color: #6b7280; font-size: 13.5px; margin: 6px 0 0;"><?php esc_html_e( 'Back up or migrate module configurations across your sites. API keys and sensitive tokens are omitted for security.', 'dragwyb-click-to-chat' ); ?></p>
				</div>

				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

					<!-- Export Settings Box -->
					<div style="background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="dctc_export_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 14px; display: flex; align-items: center; gap: 8px;">
								<span>📤</span> <?php esc_html_e( 'Export Settings', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 14px;">
								<label style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Include Settings Modules:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div style="display: flex; flex-direction: column; gap: 8px; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
									<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
										<input name="dctc_modules[]" type="checkbox" value="channels" checked="checked" style="accent-color: #8e44ad;" />
										<span>📱 <strong><?php esc_html_e( 'Channels Settings', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>
									<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
										<input name="dctc_modules[]" type="checkbox" value="ai" checked="checked" style="accent-color: #8e44ad;" />
										<span>🤖 <strong><?php esc_html_e( 'AI Assistant Settings', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>
									<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
										<input name="dctc_modules[]" type="checkbox" value="support" checked="checked" style="accent-color: #8e44ad;" />
										<span>🛡️ <strong><?php esc_html_e( 'Support Center Settings', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>
								</div>
							</div>

							<div style="margin-bottom: 20px;">
								<label style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Format:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div style="display: flex; gap: 14px;">
									<label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
										<input name="dctc_format" type="radio" value="json" checked="checked" style="accent-color: #8e44ad;" />
										<span><strong>JSON</strong></span>
									</label>
									<label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
										<input name="dctc_format" type="radio" value="csv" style="accent-color: #8e44ad;" />
										<span>CSV</span>
									</label>
									<label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
										<input name="dctc_format" type="radio" value="sql" style="accent-color: #8e44ad;" />
										<span>SQL</span>
									</label>
								</div>
							</div>

							<button type="submit" class="dctc-btn dctc-btn-primary" style="width: 100%; justify-content: center; padding: 9px 16px;">
								<?php esc_html_e( 'Download Settings Backup', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

					<!-- Import Settings Box -->
					<div style="background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<input type="hidden" name="action" value="dctc_import_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 14px; display: flex; align-items: center; gap: 8px;">
								<span>📥</span> <?php esc_html_e( 'Import Settings', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 14px;">
								<label style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Apply to Modules:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div style="display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
									<label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
										<input name="dctc_import_modules[]" type="checkbox" value="channels" checked="checked" style="accent-color: #8e44ad;" />
										<span>📱 <?php esc_html_e( 'Channels', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
										<input name="dctc_import_modules[]" type="checkbox" value="ai" checked="checked" style="accent-color: #8e44ad;" />
										<span>🤖 <?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
										<input name="dctc_import_modules[]" type="checkbox" value="support" checked="checked" style="accent-color: #8e44ad;" />
										<span>🛡️ <?php esc_html_e( 'Support Center', 'dragwyb-click-to-chat' ); ?></span>
									</label>
								</div>
							</div>

							<div style="margin-bottom: 20px;">
								<label for="dctc_settings_file" style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Select Backup File (.json, .csv, .sql):', 'dragwyb-click-to-chat' ); ?>
								</label>
								<input type="file" name="dctc_import_file" id="dctc_settings_file" accept=".json,.csv,.sql,application/json,text/csv,application/sql" required style="width: 100%; font-size: 12.5px; padding: 8px 10px; background: #fff; border: 1px dashed #d1d5db; border-radius: 8px; cursor: pointer;" />
							</div>

							<button type="submit" class="dctc-btn dctc-btn-secondary" style="width: 100%; justify-content: center; padding: 9px 16px; border-color: #d1d5db;">
								<?php esc_html_e( 'Restore Settings', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

				</div>
			</div>

			<!-- PART 2: Live Application Data Import / Export -->
			<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
				<div style="margin-bottom: 24px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<span style="font-size: 22px;">📊</span>
						<h2 style="font-size: 19px; font-weight: 700; color: #111827; margin: 0;"><?php esc_html_e( 'Application Data Import / Export', 'dragwyb-click-to-chat' ); ?></h2>
					</div>
					<p style="color: #6b7280; font-size: 13.5px; margin: 6px 0 0;"><?php esc_html_e( 'Export or import live customer records, chat conversation logs, qualified leads, and support ticketing threads.', 'dragwyb-click-to-chat' ); ?></p>
				</div>

				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">

					<!-- Export Data Box -->
					<div style="background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="dctc_export_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 14px; display: flex; align-items: center; gap: 8px;">
								<span>📤</span> <?php esc_html_e( 'Export Application Data', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 20px;">
								<label style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Select Datasets to Export:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div style="display: flex; flex-direction: column; gap: 8px; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
									<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
										<input name="dctc_modules[]" type="checkbox" value="ai_data" checked="checked" style="accent-color: #8e44ad;" />
										<span>💬 <strong><?php esc_html_e( 'AI Chatbot Conversations & Leads', 'dragwyb-click-to-chat' ); ?></strong></span>
									</label>

									<?php if ( $dctc_support_enabled ) : ?>
										<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
											<input name="dctc_modules[]" type="checkbox" value="support_data" checked="checked" style="accent-color: #8e44ad;" />
											<span>🎫 <strong><?php esc_html_e( 'Support Help Desk Tickets & Threads', 'dragwyb-click-to-chat' ); ?></strong></span>
										</label>
									<?php else : ?>
										<div style="font-size: 12px; color: #9ca3af; padding: 4px 0;">
											<em><?php esc_html_e( '(Enable Support Center module in General tab to export support ticket data)', 'dragwyb-click-to-chat' ); ?></em>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<input type="hidden" name="dctc_format" value="json" />

							<button type="submit" class="dctc-btn dctc-btn-primary" style="width: 100%; justify-content: center; padding: 9px 16px;">
								<?php esc_html_e( 'Download Live Data Backup (.JSON)', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

					<!-- Import Data Box -->
					<div style="background: #fafafa; border: 1px solid #e5e7eb; border-radius: 10px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<input type="hidden" name="action" value="dctc_import_settings" />
							<?php wp_nonce_field( 'dctc_import_export' ); ?>

							<h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0 0 14px; display: flex; align-items: center; gap: 8px;">
								<span>📥</span> <?php esc_html_e( 'Import Application Data', 'dragwyb-click-to-chat' ); ?>
							</h3>

							<div style="margin-bottom: 14px;">
								<label style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Restore Datasets:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<div style="display: flex; flex-direction: column; gap: 8px; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
									<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
										<input name="dctc_import_modules[]" type="checkbox" value="ai_data" checked="checked" style="accent-color: #8e44ad;" />
										<span>💬 <?php esc_html_e( 'AI Chat Sessions & Leads', 'dragwyb-click-to-chat' ); ?></span>
									</label>
									<?php if ( $dctc_support_enabled ) : ?>
										<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
											<input name="dctc_import_modules[]" type="checkbox" value="support_data" checked="checked" style="accent-color: #8e44ad;" />
											<span>🎫 <?php esc_html_e( 'Support Desk Tickets & Messages', 'dragwyb-click-to-chat' ); ?></span>
										</label>
									<?php endif; ?>
								</div>
							</div>

							<div style="margin-bottom: 20px;">
								<label for="dctc_data_file" style="font-size: 12.5px; font-weight: 600; color: #374151; display: block; margin-bottom: 8px;">
									<?php esc_html_e( 'Select JSON Data File:', 'dragwyb-click-to-chat' ); ?>
								</label>
								<input type="file" name="dctc_import_file" id="dctc_data_file" accept=".json,application/json" required style="width: 100%; font-size: 12.5px; padding: 8px 10px; background: #fff; border: 1px dashed #d1d5db; border-radius: 8px; cursor: pointer;" />
							</div>

							<button type="submit" class="dctc-btn dctc-btn-secondary" style="width: 100%; justify-content: center; padding: 9px 16px; border-color: #d1d5db;">
								<?php esc_html_e( 'Restore Live Data', 'dragwyb-click-to-chat' ); ?>
							</button>
						</form>
					</div>

				</div>
			</div>

		</div>

	</div>

</div>
