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

// 4. Privacy & Uninstall cleanup settings
$dctc_uninstall_settings = get_option( 'dctc_uninstall_settings', null );
if ( ! is_array( $dctc_uninstall_settings ) ) {
	$dctc_uninstall_settings = array(
		'delete_options'      => 0, // Unselected by default (Preserve on reinstall)
		'delete_ai_data'      => 0, // Unselected by default (Preserve on reinstall)
		'delete_rag_data'     => 0, // Unselected by default (Preserve on reinstall)
		'delete_support_data' => 0, // Unselected by default (Preserve on reinstall)
		'delete_error_logs'   => 1, // Selected by default (Clean temporary error logs)
		'delete_user_meta'    => 0, // Unselected by default (Preserve on reinstall)
		'delete_transients'   => 1, // Selected by default (Clean temporary transients)
	);
}

// Default active tab (from URL or default to 'general')
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter for settings tab display.
$dctc_requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
$dctc_valid_tabs    = array( 'general', 'import-export', 'privacy', 'free-vs-pro' );
$dctc_active_tab    = in_array( $dctc_requested_tab, $dctc_valid_tabs, true ) ? $dctc_requested_tab : 'general';
?>

<div class="dctc-admin-wrap dctc-settings-wrap">

	<!-- Success / Notice Toast -->
	<div class="dctc-success-message"></div>

	<!-- Full-Size Sticky Top Header Bar matching Support Center -->
	<header class="dctc-sc-header-bar dctc-settings-top-header">
		<div class="dctc-sc-brand">
			<div class="dctc-sc-brand-icon">
				<span class="dashicons dashicons-admin-generic"></span>
			</div>
			<div>
				<h1 class="dctc-sc-app-title"><?php esc_html_e( 'Click to Chat Settings', 'dragwyb-click-to-chat' ); ?></h1>
				<span class="dctc-sc-app-tagline"><?php esc_html_e( 'Global Modules, Backup & Privacy Control', 'dragwyb-click-to-chat' ); ?></span>
			</div>
		</div>

		<nav class="dctc-sc-top-nav" role="tablist" aria-label="<?php esc_attr_e( 'Settings categories', 'dragwyb-click-to-chat' ); ?>">
			<button type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-settings-tab-btn <?php echo 'general' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="general">
				<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
				<?php esc_html_e( '1. General', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-settings-tab-btn <?php echo 'import-export' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="import-export">
				<span class="dashicons dashicons-database-export" aria-hidden="true"></span>
				<?php esc_html_e( '2. Import / Export', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-settings-tab-btn <?php echo 'privacy' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="privacy">
				<span class="dashicons dashicons-shield" aria-hidden="true"></span>
				<?php esc_html_e( '3. Privacy & Uninstall', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-settings-tab-btn <?php echo 'free-vs-pro' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="free-vs-pro">
				<span class="dashicons dashicons-star-filled" style="color:#f59e0b;" aria-hidden="true"></span>
				<?php esc_html_e( '4. Free vs Pro', 'dragwyb-click-to-chat' ); ?>
				<span class="dctc-pro-badge" style="font-size:10px; padding:2px 5px; margin-left:4px; background:#fef3c7; color:#b45309; border:1px solid #fde68a; border-radius:4px;">PRO</span>
			</button>
		</nav>

		<div class="dctc-sc-header-right" style="display:flex; align-items:center; gap:10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-guide' ) ); ?>" class="dctc-ai-guide-btn" title="<?php esc_attr_e( 'View plugin documentation', 'dragwyb-click-to-chat' ); ?>">
				<span class="dashicons dashicons-book"></span>
				<?php esc_html_e( 'User Guide', 'dragwyb-click-to-chat' ); ?>
			</a>
			<button type="button" id="dctc-save-settings-top-btn" class="dctc-ai-btn dctc-ai-btn-primary">
				<span class="dashicons dashicons-saved" style="margin-right:4px; font-size:16px; width:16px; height:16px;"></span>
				<?php esc_html_e( 'Save Settings', 'dragwyb-click-to-chat' ); ?>
			</button>
		</div>
	</header>

	<!-- Main Container -->
	<div class="dctc-settings-main-container">

		<!-- ========================================== -->
		<!-- TAB 1: GENERAL (Module Activation & Cards)  -->
		<!-- ========================================== -->
		<div id="dctc-tab-general" class="dctc-settings-tab-content <?php echo 'general' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'general' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<div class="dctc-settings-card-box">
				<div class="dctc-settings-card-header">
					<div>
						<h2 class="dctc-settings-card-title"><?php esc_html_e( 'Feature & Module Management', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-settings-card-sub"><?php esc_html_e( 'Enable or disable the core plugin features independently. When enabled, direct configuration tools and shortcodes appear inside each module.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
				</div>

				<div class="dctc-modules-card-list">

					<!-- Feature 1: Social Channels Widget -->
					<div class="dctc-modern-module-card dctc-module-channels <?php echo $dctc_channels_enabled ? 'is-enabled' : 'is-disabled'; ?>" id="dctc-card-channels">
						<div class="dctc-module-header">
							<div class="dctc-module-header-left">
								<div class="dctc-module-icon-wrap dctc-icon-channels">
									<span class="dashicons dashicons-smartphone" style="font-size:22px; width:22px; height:22px;"></span>
								</div>
								<div class="dctc-module-title-area">
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
								<label class="dctc-ios-switch" for="dctc_gen_channels_enabled" title="<?php esc_attr_e( 'Toggle Social Channels', 'dragwyb-click-to-chat' ); ?>">
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
											<span class="dashicons dashicons-admin-page" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span>
											<span class="dctc-copy-text"><?php esc_html_e( 'Copy', 'dragwyb-click-to-chat' ); ?></span>
										</button>
									</div>
								</div>
								<div class="dctc-drawer-actions">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-channels' ) ); ?>" class="dctc-btn-action">
										<span class="dashicons dashicons-admin-generic" style="font-size:15px; width:15px; height:15px; margin-right:6px;"></span>
										<?php esc_html_e( 'Configure Channels & Layout &rarr;', 'dragwyb-click-to-chat' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Feature 2: AI Assistant & Autonomous Chatbot -->
					<div class="dctc-modern-module-card dctc-module-ai <?php echo $dctc_ai_enabled ? 'is-enabled' : 'is-disabled'; ?>" id="dctc-card-ai">
						<div class="dctc-module-header">
							<div class="dctc-module-header-left">
								<div class="dctc-module-icon-wrap dctc-icon-ai">
									<span class="dashicons dashicons-format-chat" style="font-size:22px; width:22px; height:22px;"></span>
								</div>
								<div class="dctc-module-title-area">
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
								<label class="dctc-ios-switch" for="dctc_gen_ai_enabled" title="<?php esc_attr_e( 'Toggle AI Assistant', 'dragwyb-click-to-chat' ); ?>">
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
											<span class="dashicons dashicons-admin-page" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span>
											<span class="dctc-copy-text"><?php esc_html_e( 'Copy', 'dragwyb-click-to-chat' ); ?></span>
										</button>
									</div>
								</div>
								<div class="dctc-drawer-actions">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ); ?>" class="dctc-btn-action">
										<span class="dashicons dashicons-dashboard" style="font-size:15px; width:15px; height:15px; margin-right:6px;"></span>
										<?php esc_html_e( 'Open AI Assistant Dashboard &rarr;', 'dragwyb-click-to-chat' ); ?>
									</a>
								</div>
							</div>
						</div>
					</div>

					<!-- Feature 3: Support Center & Helpdesk -->
					<div class="dctc-modern-module-card dctc-module-support <?php echo $dctc_support_enabled ? 'is-enabled' : 'is-disabled'; ?>" id="dctc-card-support">
						<div class="dctc-module-header">
							<div class="dctc-module-header-left">
								<div class="dctc-module-icon-wrap dctc-icon-support">
									<span class="dashicons dashicons-shield" style="font-size:22px; width:22px; height:22px;"></span>
								</div>
								<div class="dctc-module-title-area">
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
								<label class="dctc-ios-switch" for="dctc_gen_support_enabled" title="<?php esc_attr_e( 'Toggle Support Center', 'dragwyb-click-to-chat' ); ?>">
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
										<span class="dctc-drawer-hint"><?php esc_html_e( 'Embed the support helpdesk portal on your website using the shortcode:', 'dragwyb-click-to-chat' ); ?></span>
									</div>
									<div class="dctc-shortcode-copy-box">
										<code>[dctc_support_portal]</code>
										<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc_support_portal]">
											<span class="dashicons dashicons-admin-page" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span>
											<span class="dctc-copy-text"><?php esc_html_e( 'Copy', 'dragwyb-click-to-chat' ); ?></span>
										</button>
									</div>
								</div>
								<div class="dctc-drawer-actions">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-support-center' ) ); ?>" class="dctc-btn-action">
										<span class="dashicons dashicons-tickets-alt" style="font-size:15px; width:15px; height:15px; margin-right:6px;"></span>
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

		<!-- ========================================== -->
		<!-- TAB 3: PRIVACY & UNINSTALL DATA CLEANUP   -->
		<!-- ========================================== -->
		<div id="dctc-tab-privacy" class="dctc-settings-tab-content <?php echo 'privacy' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'privacy' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<div class="dctc-settings-card-box">
				<div class="dctc-settings-card-header">
					<div>
						<div style="display: flex; align-items: center; gap: 8px;">
							<span class="dashicons dashicons-shield" style="font-size: 22px; width: 22px; height: 22px; color: #4f46e5;"></span>
							<h2 class="dctc-settings-card-title"><?php esc_html_e( 'Privacy & Uninstall Data Management', 'dragwyb-click-to-chat' ); ?></h2>
						</div>
						<p class="dctc-settings-card-sub"><?php esc_html_e( 'Configure which plugin database tables, options, and live customer records are permanently deleted when uninstalling the plugin.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
				</div>

				<!-- Info Notice Box -->
				<div class="dctc-privacy-info-banner">
					<div class="dctc-privacy-info-icon">
						<span class="dashicons dashicons-info"></span>
					</div>
					<div class="dctc-privacy-info-content">
						<strong><?php esc_html_e( 'How Uninstall Cleanup Works:', 'dragwyb-click-to-chat' ); ?></strong>
						<p><?php esc_html_e( 'Simply deactivating this plugin does NOT remove your data. The selections below are only executed if an administrator clicks "Delete" on the Plugins screen.', 'dragwyb-click-to-chat' ); ?></p>
					</div>
				</div>

				<!-- Quick Bulk Selection Actions -->
				<div class="dctc-privacy-toolbar">
					<span class="dctc-privacy-toolbar-title">
						<?php esc_html_e( 'Select Data to Delete Upon Plugin Uninstallation:', 'dragwyb-click-to-chat' ); ?>
					</span>
					<div class="dctc-privacy-bulk-actions">
						<button type="button" class="dctc-pill-btn dctc-pill-recommended" id="dctc-privacy-recommended">
							<span class="dashicons dashicons-shield"></span>
							<?php esc_html_e( 'Recommended Defaults', 'dragwyb-click-to-chat' ); ?>
						</button>
						<button type="button" class="dctc-pill-btn dctc-pill-danger" id="dctc-privacy-select-all">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Select All (Full Wipe)', 'dragwyb-click-to-chat' ); ?>
						</button>
						<button type="button" class="dctc-pill-btn dctc-pill-ghost" id="dctc-privacy-deselect-all">
							<span class="dashicons dashicons-undo"></span>
							<?php esc_html_e( 'Deselect All', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>
				</div>

				<!-- Granular Uninstall Options Cards Grid -->
				<div class="dctc-uninstall-options-list dctc-privacy-cards-grid">

					<!-- Option 1: Plugin Settings & API Keys (Critical) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_options'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_options">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_options" name="delete_options" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_options'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#eff6ff; color:#3b82f6;">
									<span class="dashicons dashicons-admin-generic"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'Plugin Options & API Keys', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-critical">
								<?php esc_html_e( 'Critical — Kept on reinstall', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'If checked, deletes all plugin configuration options, channel settings, custom colors, trigger rules, AI model prompts, and stored provider API keys (OpenAI, Gemini, Anthropic, etc.).', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

					<!-- Option 2: AI Chat History & Leads (Critical) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_ai_data'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_ai_data">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_ai_data" name="delete_ai_data" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_ai_data'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#f5f3ff; color:#7c3aed;">
									<span class="dashicons dashicons-format-chat"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'AI Chat Sessions & Leads', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-critical">
								<?php esc_html_e( 'Critical — Kept on reinstall', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'If checked, permanently drops the chat conversations and captured visitor leads database tables (wp_dctc_ai_sessions, wp_dctc_ai_leads).', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

					<!-- Option 3: Knowledge Base RAG & Embeddings (Critical) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_rag_data'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_rag_data">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_rag_data" name="delete_rag_data" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_rag_data'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#fdf2f8; color:#db2777;">
									<span class="dashicons dashicons-book"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'Knowledge Base & Vectors', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-critical">
								<?php esc_html_e( 'Critical — Kept on reinstall', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'If checked, permanently drops all vector RAG tables (wp_dctc_ai_rag_documents, wp_dctc_ai_rag_chunks, wp_dctc_ai_rag_metadata, wp_dctc_ai_embeddings).', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

					<!-- Option 4: Support Center & Helpdesk Ticketing (Critical) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_support_data'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_support_data">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_support_data" name="delete_support_data" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_support_data'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#ecfdf5; color:#059669;">
									<span class="dashicons dashicons-tickets-alt"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'Support Tickets & Roster', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-critical">
								<?php esc_html_e( 'Critical — Kept on reinstall', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'If checked, permanently drops all 9 support workspace database tables (tickets, categories, tags, agents, audit events, internal staff notes, assignments, notification logs).', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

					<!-- Option 5: Error & Diagnostics Logs (Temporary) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_error_logs'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_error_logs">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_error_logs" name="delete_error_logs" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_error_logs'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#fffbeb; color:#d97706;">
									<span class="dashicons dashicons-warning"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'Error & Diagnostic Logs', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-temp">
								<?php esc_html_e( 'Temporary / Cache', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'Drops the error logging table (wp_dctc_error_logs) and clears recorded diagnostic trace events.', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

					<!-- Option 6: User Metadata (Critical) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_user_meta'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_user_meta">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_user_meta" name="delete_user_meta" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_user_meta'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#f1f5f9; color:#475569;">
									<span class="dashicons dashicons-admin-users"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'User Preferences & Meta', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-critical">
								<?php esc_html_e( 'Critical — Kept on reinstall', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'If checked, deletes saved user meta rows associated with admin dashboard preferences, dismissals, and filter states.', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

					<!-- Option 7: Crons & Transients (Temporary) -->
					<label class="dctc-privacy-option-card <?php echo ! empty( $dctc_uninstall_settings['delete_transients'] ) ? 'is-selected' : ''; ?>" for="dctc_un_delete_transients">
						<div class="dctc-privacy-card-top">
							<div class="dctc-privacy-check-wrap">
								<input type="checkbox" id="dctc_un_delete_transients" name="delete_transients" value="1" <?php checked( ! empty( $dctc_uninstall_settings['delete_transients'] ), true ); ?> />
								<div class="dctc-privacy-icon-box" style="background:#fef3c7; color:#b45309;">
									<span class="dashicons dashicons-clock"></span>
								</div>
								<strong class="dctc-privacy-card-title"><?php esc_html_e( 'Cron Jobs & Transients', 'dragwyb-click-to-chat' ); ?></strong>
							</div>
							<span class="dctc-status-badge dctc-badge-temp">
								<?php esc_html_e( 'Temporary / Cache', 'dragwyb-click-to-chat' ); ?>
							</span>
						</div>
						<p class="dctc-privacy-card-desc">
							<?php esc_html_e( 'De-registers background WordPress cron schedules and purges temporary cache transients.', 'dragwyb-click-to-chat' ); ?>
						</p>
					</label>

				</div>
			</div>

		</div>

		<!-- ========================================================= -->
		<!-- TAB 4: FREE VS PRO COMPARISON                             -->
		<!-- ========================================================= -->
		<div id="dctc-tab-free-vs-pro"
			class="dctc-settings-tab-content dctc-pro <?php echo 'free-vs-pro' === $dctc_active_tab ? 'active' : ''; ?>"
			style="<?php echo 'free-vs-pro' === $dctc_active_tab ? 'display:block;' : 'display:none;'; ?>"
			data-pro-feature="free-vs-pro-comparison">

			<!-- Hero Banner -->
			<div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); color:#fff; border-radius:12px; padding:28px 32px; margin-bottom:24px; box-shadow:0 4px 20px rgba(49, 46, 129, 0.15); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
				<div style="max-width:620px;">
					<div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
						<span class="dashicons dashicons-awards" style="font-size:28px; width:28px; height:28px; color:#fbbf24;"></span>
						<h2 style="margin:0; color:#fff; font-size:22px; font-weight:700;"><?php esc_html_e( 'Dragwyb Free vs Pro Edition', 'dragwyb-click-to-chat' ); ?></h2>
					</div>
					<p style="margin:0; color:#c7d2fe; font-size:14px; line-height:1.5;">
						<?php esc_html_e( 'Free gives you a complete customer communication foundation with OpenAI, Gemini, Support Center, and WooCommerce product search. Pro unlocks advanced AI providers, automatic failover, AI staff assistants, and CRM automation.', 'dragwyb-click-to-chat' ); ?>
					</p>
				</div>
				<div>
					<a href="<?php echo esc_url( DCTC_Helper::get_pro_url( 'settings_free_vs_pro' ) ); ?>" target="_blank" rel="noopener noreferrer" class="dctc-pro-upgrade" style="display:inline-flex; align-items:center; gap:8px; background:#fbbf24; color:#1e1b4b; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:700; text-decoration:none; box-shadow:0 4px 12px rgba(251, 191, 36, 0.35);">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Explore Dragwyb Pro', 'dragwyb-click-to-chat' ); ?>
					</a>
				</div>
			</div>

			<!-- Comparison Table Card -->
			<div class="dctc-settings-section-card" style="padding:0; overflow:hidden; border:1px solid #e5e7eb; border-radius:12px; background:#fff;">
				<div style="padding:20px 24px; border-bottom:1px solid #e5e7eb; background:#f9fafb; display:flex; justify-content:space-between; align-items:center;">
					<h3 style="margin:0; font-size:16px; font-weight:600; color:#111827;"><?php esc_html_e( 'Detailed Feature Entitlement Matrix', 'dragwyb-click-to-chat' ); ?></h3>
					<span style="font-size:12px; color:#6b7280;"><?php esc_html_e( 'Zero data migration required on Pro activation', 'dragwyb-click-to-chat' ); ?></span>
				</div>

				<div style="overflow-x:auto;">
					<table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
						<thead>
							<tr style="background:#f3f4f6; color:#374151; font-weight:600; border-bottom:2px solid #e5e7eb;">
								<th style="padding:14px 20px; width:40%;"><?php esc_html_e( 'Feature Capability', 'dragwyb-click-to-chat' ); ?></th>
								<th style="padding:14px 20px; width:30%;"><?php esc_html_e( 'Free Edition (Included)', 'dragwyb-click-to-chat' ); ?></th>
								<th style="padding:14px 20px; width:30%; background:#f5f3ff; color:#6d28d9;"><?php esc_html_e( 'Dragwyb Pro Add-on', 'dragwyb-click-to-chat' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<!-- Group 1: AI Providers -->
							<tr style="background:#fafafa; font-weight:600; color:#4b5563;">
								<td colspan="3" style="padding:10px 20px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; border-top:1px solid #e5e7eb;">
									<?php esc_html_e( '1. AI Engine & Provider Reliability', 'dragwyb-click-to-chat' ); ?>
								</td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'OpenAI & Google Gemini', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'GPT-4o, GPT-4o Mini, Gemini 2.5 Flash, Gemini 3.5 Flash', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Anthropic Claude, Groq, DeepSeek, OpenRouter, Ollama', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Claude 3.5 Sonnet, ultra-fast Groq LPUs, DeepSeek reasoning, local LLMs', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#9ca3af;"><span class="dashicons dashicons-minus" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Pro Preview', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#7c3aed; font-weight:600;"><span class="dashicons dashicons-star-filled" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'All 5+ Providers', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Automatic Multi-Tier Failover', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Guarantees 99.99% chatbot uptime on quota/rate-limit errors', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#9ca3af;"><span class="dashicons dashicons-minus" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Standard error log', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#7c3aed; font-weight:600;"><span class="dashicons dashicons-star-filled" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Auto-Failover Chain', 'dragwyb-click-to-chat' ); ?></td>
							</tr>

							<!-- Group 2: Knowledge Base -->
							<tr style="background:#fafafa; font-weight:600; color:#4b5563;">
								<td colspan="3" style="padding:10px 20px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; border-top:1px solid #e5e7eb;">
									<?php esc_html_e( '2. Knowledge Base & Vector RAG', 'dragwyb-click-to-chat' ); ?>
								</td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Pinecone Vector Database & Local RAG', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Index posts, pages, custom text, and sync on trash/delete', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Confidence Threshold Filtering & Handoff', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'min_confidence tuning and human handoff fallback buttons', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included', 'dragwyb-click-to-chat' ); ?></td>
							</tr>

							<!-- Group 3: Support Center -->
							<tr style="background:#fafafa; font-weight:600; color:#4b5563;">
								<td colspan="3" style="padding:10px 20px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; border-top:1px solid #e5e7eb;">
									<?php esc_html_e( '3. Support Center & Helpdesk Ticketing', 'dragwyb-click-to-chat' ); ?>
								</td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Core Ticket Lifecycle & Customer Portal', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Shortcodes, ticket threads, agent replies, notes, email notifications', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included (Unlimited)', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included (Unlimited)', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'AI Reply Suggestions & Ticket Summaries', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( '1-click smart draft responses and contextual conversation summaries', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#9ca3af;"><span class="dashicons dashicons-minus" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Manual responses', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#7c3aed; font-weight:600;"><span class="dashicons dashicons-star-filled" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'AI Staff Copilot', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Round-Robin Assignment & Live Agent Presence', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Automatic workload balancing, agent capacity ceilings, real-time presence', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#9ca3af;"><span class="dashicons dashicons-minus" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Manual assignment', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#7c3aed; font-weight:600;"><span class="dashicons dashicons-star-filled" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Auto-Balancing Engine', 'dragwyb-click-to-chat' ); ?></td>
							</tr>

							<!-- Group 4: Lead Management -->
							<tr style="background:#fafafa; font-weight:600; color:#4b5563;">
								<td colspan="3" style="padding:10px 20px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; border-top:1px solid #e5e7eb;">
									<?php esc_html_e( '4. Lead Capture & CRM Integration', 'dragwyb-click-to-chat' ); ?>
								</td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Lead Capture, Scoring, Table & CSV Export', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Conversational lead forms, 0–100 intent scoring, transcript inspection', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included (Unlimited)', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#059669;"><span class="dashicons dashicons-yes-alt" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Included (Unlimited)', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'CRM Webhook Dispatcher (Zapier, Make, HubSpot)', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Instant webhook event dispatch with auto-retry and delivery logging', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#9ca3af;"><span class="dashicons dashicons-minus" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'CSV Export', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#7c3aed; font-weight:600;"><span class="dashicons dashicons-star-filled" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Instant CRM Webhooks', 'dragwyb-click-to-chat' ); ?></td>
							</tr>

							<!-- Group 5: Admin AI Copilot -->
							<tr style="background:#fafafa; font-weight:600; color:#4b5563;">
								<td colspan="3" style="padding:10px 20px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; border-top:1px solid #e5e7eb;">
									<?php esc_html_e( '5. Business Intelligence & AI Copilot', 'dragwyb-click-to-chat' ); ?>
								</td>
							</tr>
							<tr style="border-bottom:1px solid #f3f4f6;">
								<td style="padding:12px 20px;"><strong><?php esc_html_e( 'Admin AI Copilot & Gap Analysis', 'dragwyb-click-to-chat' ); ?></strong><br><span style="color:#6b7280; font-size:12px;"><?php esc_html_e( 'Natural language queries over customer conversations and FAQ gap discoveries', 'dragwyb-click-to-chat' ); ?></span></td>
								<td style="padding:12px 20px; color:#9ca3af;"><span class="dashicons dashicons-minus" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Pro Preview', 'dragwyb-click-to-chat' ); ?></td>
								<td style="padding:12px 20px; background:#faf5ff; color:#7c3aed; font-weight:600;"><span class="dashicons dashicons-star-filled" style="vertical-align:middle; margin-right:4px;"></span><?php esc_html_e( 'Full Copilot Access', 'dragwyb-click-to-chat' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

		</div>

	</div>

</div>

