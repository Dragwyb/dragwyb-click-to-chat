<?php

/**
 * Dragwyb Click To Chat - Channels Builder Template
 * Multi-channel social widget builder with live preview.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$dctc_settings = get_option( 'dctc_settings', array() );

// Helper vars for header/summary
$dctc_fb_id  = isset( $dctc_settings['facebook_value'] ) ? $dctc_settings['facebook_value'] : '';
$dctc_wa_num = isset( $dctc_settings['whatsapp_value'] ) ? $dctc_settings['whatsapp_value'] : '';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verification not needed for navigational GET parameter
$dctc_current_step = isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 0;
?>

<div class="dctc-admin-wrap">

	<!-- Success Message Toast -->
	<div class="dctc-success-message"></div>

	<!-- Full-Size Sticky Top Header Bar matching Support Center -->
	<header class="dctc-sc-header-bar dctc-channels-top-header">
		<div class="dctc-sc-brand">
			<div class="dctc-sc-brand-icon">
				<span class="dashicons dashicons-networking"></span>
			</div>
			<div>
				<h1 class="dctc-sc-app-title"><?php esc_html_e( 'Click to Chat Channels', 'dragwyb-click-to-chat' ); ?></h1>
				<span class="dctc-sc-app-tagline"><?php esc_html_e( 'Multi-Channel Floating Social Widget', 'dragwyb-click-to-chat' ); ?></span>
			</div>
		</div>

		<nav class="dctc-sc-top-nav" role="tablist" aria-label="<?php esc_attr_e( 'Channels setup steps', 'dragwyb-click-to-chat' ); ?>">
			<button type="button"
				role="tab"
				aria-selected="<?php echo $dctc_current_step === 0 ? 'true' : 'false'; ?>"
				class="dctc-sc-nav-link dctc-tab <?php echo $dctc_current_step === 0 ? 'active' : ''; ?>"
				data-step="0">
				<span class="dashicons dashicons-share" aria-hidden="true"></span>
				<?php esc_html_e( '1. Select Channels', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button type="button"
				role="tab"
				aria-selected="<?php echo $dctc_current_step === 1 ? 'true' : 'false'; ?>"
				class="dctc-sc-nav-link dctc-tab <?php echo $dctc_current_step === 1 ? 'active' : ''; ?>"
				data-step="1">
				<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
				<?php esc_html_e( '2. Customization', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button type="button"
				role="tab"
				aria-selected="<?php echo $dctc_current_step === 2 ? 'true' : 'false'; ?>"
				class="dctc-sc-nav-link dctc-tab <?php echo $dctc_current_step === 2 ? 'active' : ''; ?>"
				data-step="2">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<?php esc_html_e( '3. Triggers & Targeting', 'dragwyb-click-to-chat' ); ?>
			</button>
		</nav>

		<div class="dctc-sc-header-right" style="display:flex; align-items:center; gap:10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-guide&tab=channels' ) ); ?>" class="dctc-ai-guide-btn" title="<?php esc_attr_e( 'View Channels setup documentation', 'dragwyb-click-to-chat' ); ?>">
				<span class="dashicons dashicons-book"></span>
				<?php esc_html_e( 'User Guide', 'dragwyb-click-to-chat' ); ?>
			</a>
			<button type="button" id="dctc-save-btn" class="dctc-ai-btn dctc-ai-btn-primary">
				<span class="dashicons dashicons-saved" style="margin-right: 4px; font-size: 16px; width: 16px; height: 16px;"></span>
				<?php esc_html_e( 'Save Changes', 'dragwyb-click-to-chat' ); ?>
			</button>
		</div>
	</header>

	<!-- Main Content -->
	<div class="dctc-content">

		<!-- Settings Panel -->
		<div class="dctc-settings-panel">

			<!-- Section 1: Select Channels -->
			<div class="dctc-section <?php echo $dctc_current_step === 0 ? 'active' : ''; ?>" data-section="0">
				<?php require 'section-channels.php'; ?>
			</div>

			<!-- Section 2: Widget Customization -->
			<div class="dctc-section <?php echo $dctc_current_step === 1 ? 'active' : ''; ?>" data-section="1">
				<?php require 'section-customization.php'; ?>
			</div>

			<!-- Section 3: Triggers & Targeting -->
			<div class="dctc-section <?php echo $dctc_current_step === 2 ? 'active' : ''; ?>" data-section="2">
				<?php require 'section-triggers.php'; ?>
			</div>

		</div>

		<!-- Preview Panel -->
		<div class="dctc-preview-panel">
			<div class="dctc-preview dctc-ai-card">
				<div class="dctc-ai-card__header">
					<div class="dctc-ai-card__header-left">
						<span class="dashicons dashicons-visibility" style="color: #6366f1; font-size: 18px; width: 18px; height: 18px;"></span>
						<h3 class="dctc-preview-title" style="margin: 0; font-size: 14px; font-weight: 700; color: #0f172a;">
							<?php esc_html_e( 'Live Preview', 'dragwyb-click-to-chat' ); ?>
						</h3>
					</div>
					<span class="dctc-ai-status-pill is-active" style="font-size: 0.65rem;">
						<?php esc_html_e( 'Interactive', 'dragwyb-click-to-chat' ); ?>
					</span>
				</div>

				<div class="dctc-preview-device-wrap" style="padding: 15px; height: calc(100% - 46px)">
					<div class="dctc-preview-device">
						<p style="text-align:center; color:#9ca3af; padding:20px;">
							<?php esc_html_e( 'Widget preview will appear here', 'dragwyb-click-to-chat' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

	</div>

</div>