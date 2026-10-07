<?php
/**
 * Click to Chat - Feature Documentation & Interactive User Guide
 *
 * Dedicated Guide page featuring 3 tabs:
 * 1. Channels Guide (Social Floating Widget)
 * 2. AI Assistant Guide (Autonomous Chatbot & RAG)
 * 3. Support Center Guide (Helpdesk Ticketing & SLA)
 *
 * Provides structured breakdowns of:
 * - 🔴 Required Setup Configurations (Mandatory steps)
 * - 🎯 Visibility Rules & Display Targeting
 * - ⭐ Highlighted / Standout Features & Capabilities
 * - Alternating left content + visual mockup design when enabled
 * - Clear explanations with 1-click activation when disabled
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

// Active guide tab from URL (channels, ai, support, setup)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$dctc_active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array( 'channels', 'ai', 'support', 'setup' ), true ) ? sanitize_key( $_GET['tab'] ) : 'channels';
?>

<div class="dctc-admin-wrap dctc-guide-wrap">

	<!-- Success Toast -->
	<div class="dctc-success-message"></div>

	<!-- Full-Size Sticky Top Header Bar matching Support Center, AI Assistant, Channels & Settings -->
	<header class="dctc-sc-header-bar dctc-guide-top-header">
		<div class="dctc-sc-brand">
			<div class="dctc-sc-brand-icon">
				<span class="dashicons dashicons-book-alt"></span>
			</div>
			<div>
				<h1 class="dctc-sc-app-title"><?php esc_html_e( 'Click to Chat Guide', 'dragwyb-click-to-chat' ); ?></h1>
				<span class="dctc-sc-app-tagline"><?php esc_html_e( 'Documentation, Feature Walkthroughs & Setup Wizard', 'dragwyb-click-to-chat' ); ?></span>
			</div>
		</div>

		<nav class="dctc-sc-top-nav" role="tablist" aria-label="<?php esc_attr_e( 'Guide topics', 'dragwyb-click-to-chat' ); ?>">
			<button
				type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-guide-tab-btn <?php echo 'channels' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="channels">
				<span class="dashicons dashicons-smartphone" aria-hidden="true"></span>
				<?php esc_html_e( '1. Channels', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button 
				type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-guide-tab-btn <?php echo 'ai' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="ai">
				<span class="dashicons dashicons-format-chat" aria-hidden="true"></span>
				<?php esc_html_e( '2. AI Assistant', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button
				type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-guide-tab-btn <?php echo 'support' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="support">
				<span class="dashicons dashicons-shield" aria-hidden="true"></span>
				<?php esc_html_e( '3. Support Center', 'dragwyb-click-to-chat' ); ?>
			</button>

			<button 
				type="button"
				role="tab"
				class="dctc-sc-nav-link dctc-tab dctc-guide-tab-btn <?php echo 'setup' === $dctc_active_tab ? 'active' : ''; ?>"
				data-tab="setup">
				<span class="dashicons dashicons-superhero" aria-hidden="true"></span>
				<?php esc_html_e( '4. Setup Wizard', 'dragwyb-click-to-chat' ); ?>
			</button>
		</nav>

		<div class="dctc-sc-header-right" style="display:flex; align-items:center; gap:10px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-settings' ) ); ?>" class="dctc-ai-guide-btn" title="<?php esc_attr_e( 'Plugin Global Settings', 'dragwyb-click-to-chat' ); ?>">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'Settings', 'dragwyb-click-to-chat' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ); ?>" class="dctc-ai-btn dctc-ai-btn-primary">
				<span class="dashicons dashicons-dashboard" style="margin-right:4px; font-size:16px; width:16px; height:16px;"></span>
				<?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?>
			</a>
		</div>
	</header>

	<!-- Main Content Container -->
	<div class="dctc-guide-container">

		<!-- ================================================================= -->
		<!-- TAB 1: SOCIAL CHANNELS GUIDE                                       -->
		<!-- ================================================================= -->
		<div id="dctc-guide-tab-channels" class="dctc-guide-tab-content <?php echo 'channels' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'channels' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<?php if ( $dctc_channels_enabled ) : ?>
				<!-- Hero Banner -->
				<div class="dctc-guide-hero-banner">
					<div class="dctc-guide-hero-text">
						<div class="dctc-guide-hero-badge-row">
							<span class="dctc-status-pill is-active"><?php esc_html_e( 'Feature Active', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-guide-pill-tag">📱 <?php esc_html_e( 'Multi-Channel Floating Widget', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h1 class="dctc-guide-hero-title"><?php esc_html_e( 'Social Channels Setup & Configuration Guide', 'dragwyb-click-to-chat' ); ?></h1>
						<p class="dctc-guide-hero-desc">
							<?php esc_html_e( 'Connect visitors directly to your team on WhatsApp, Facebook Messenger, Phone, Email, Telegram, Instagram, SMS, and LinkedIn.', 'dragwyb-click-to-chat' ); ?>
						</p>
					</div>
					<div class="dctc-guide-hero-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-channels' ) ); ?>" class="dctc-btn dctc-btn-primary">
							<?php esc_html_e( 'Open Channels Builder &rarr;', 'dragwyb-click-to-chat' ); ?>
						</a>
					</div>
				</div>

				<!-- Quick Setup Checklist Map -->
				<div class="dctc-guide-quick-map">
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-required"><?php esc_html_e( 'REQUIRED', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '1. Channel Inputs', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Enter Phone/WhatsApp with country code or username', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-required"><?php esc_html_e( 'REQUIRED', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '2. Position & Style', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Set Bottom-Right/Left and button brand color', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-visibility"><?php esc_html_e( 'VISIBILITY', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '3. Device & Triggers', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Desktop/Mobile filters & scroll/time delay rules', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-highlight"><?php esc_html_e( 'HIGHLIGHT', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '4. Prefilled Text', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Dynamic WhatsApp greetings with page URL tags', 'dragwyb-click-to-chat' ); ?></small>
					</div>
				</div>

				<!-- Step 1: REQUIRED - Select Channels & Contact Details -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 01', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-required">🔴 <?php esc_html_e( 'Mandatory Configuration', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Enable Channels & Enter Contact Values', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'In Step 1 of the Channels Builder, click to enable the communication channels your team monitors.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'WhatsApp (Recommended):', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Enter full international phone number with country code without spaces or dashes (e.g., +14155552671 or 14155552671).', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Facebook Messenger:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Enter your Facebook Page username or vanity slug (e.g., yourbrand).', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Phone Call & SMS:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Enter your business hotline number for 1-tap dialing on mobile devices.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Telegram & Instagram:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Enter Telegram username (@handle) or Instagram profile handle.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Channels Builder: Step 1', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div class="dctc-mockup-item is-active">
									<span class="dctc-mockup-icon" style="background:#25D366; color:#fff;">📱</span>
									<div>
										<strong>WhatsApp Chat</strong>
										<small>+1 (555) 234-5678 • Country code included</small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-mockup-item is-active">
									<span class="dctc-mockup-icon" style="background:#0084FF; color:#fff;">💬</span>
									<div>
										<strong>Messenger</strong>
										<small>m.me/yourbrand • Page ID verified</small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-mockup-item">
									<span class="dctc-mockup-icon" style="background:#0088cc; color:#fff;">✈️</span>
									<div>
										<strong>Telegram</strong>
										<small>@support_team</small>
									</div>
									<span class="dctc-mockup-tag" style="background:#f1f5f9; color:#64748b; border-color:#e2e8f0;"><?php esc_html_e( 'Ready', 'dragwyb-click-to-chat' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Step 2: REQUIRED - Button Design & Position -->
				<div class="dctc-zigzag-row is-reversed">
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Widget Customization: Step 2', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body" style="text-align: center; padding: 24px 16px;">
								<div style="width: 58px; height: 58px; border-radius: 50%; background: linear-gradient(135deg, #8e44ad, #6c3483); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:24px; box-shadow: 0 6px 18px rgba(142,68,173,0.35);">
									💬
								</div>
								<div style="margin-top: 14px; font-size: 13px; color: #1e293b; font-weight: 600;">
									<?php esc_html_e( 'Anchor: Bottom Right (20px / 20px)', 'dragwyb-click-to-chat' ); ?>
								</div>
								<div style="margin-top: 4px; font-size: 12px; color: #64748b;">
									<?php esc_html_e( 'Size: 60px • Radius: 50% Round • Brand Hex: #8e44ad', 'dragwyb-click-to-chat' ); ?>
								</div>
								<div style="margin-top: 10px; display:inline-block; font-size: 11px; background:#ecfdf5; color:#065f46; padding:3px 10px; border-radius:20px; font-weight:700;">
									✓ <?php esc_html_e( 'Hover Animation: Pulse Glow', 'dragwyb-click-to-chat' ); ?>
								</div>
							</div>
						</div>
					</div>
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 02', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-required">🔴 <?php esc_html_e( 'Mandatory Configuration', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Customize Widget Look, Color & Positioning', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Configure the floating trigger button in Step 2 to match your website theme.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Screen Position:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Place the button at Bottom-Right or Bottom-Left with custom bottom and side offset distances in pixels or rem.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Brand Colors & Diameter:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Select any custom brand hex color and choose button diameter (45px to 75px).', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Call-to-Action Tooltip:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Add an optional attention-grabbing bubble (e.g. "Chat with us 👋") that displays beside the button.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Step 3: VISIBILITY RULES & TRIGGERS -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 03', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-visibility">🎯 <?php esc_html_e( 'Visibility Rules & Targeting', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Device Targeting & Entrance Triggers', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Control exactly where, when, and on which devices your floating contact widget appears in Step 3.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Device Visibility Filter:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Show widget on Desktop only, Mobile only, or Both. Each individual channel can also be set to mobile-only (e.g. 1-tap phone dial).', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Entrance Time Delay:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Set an entrance delay (e.g. 3 seconds) before the widget slides in to prevent banner fatigue.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Post Type & Page Restrictions:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Choose to show Sitewide, Home Page only, or restrict to specific post types (e.g. WooCommerce products).', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Triggers & Targeting: Step 3', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px;">
									<span style="font-size:12.5px; font-weight:600; color:#1e293b;">💻 <?php esc_html_e( 'Desktop Visibility', 'dragwyb-click-to-chat' ); ?></span>
									<span style="color:#10b981; font-weight:700; font-size:12px;">✓ <?php esc_html_e( 'Enabled', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px;">
									<span style="font-size:12.5px; font-weight:600; color:#1e293b;">📱 <?php esc_html_e( 'Mobile Visibility', 'dragwyb-click-to-chat' ); ?></span>
									<span style="color:#10b981; font-weight:700; font-size:12px;">✓ <?php esc_html_e( 'Enabled', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
									<span style="font-size:12.5px; font-weight:600; color:#1e293b;">⏱️ <?php esc_html_e( 'Entrance Delay', 'dragwyb-click-to-chat' ); ?></span>
									<span style="color:#6d28d9; font-weight:700; font-size:12px;">2 <?php esc_html_e( 'Seconds', 'dragwyb-click-to-chat' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Step 4: HIGHLIGHTED FEATURE - WhatsApp Prefilled Inquiries -->
				<div class="dctc-zigzag-row is-reversed">
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'WhatsApp Conversation Preview', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body" style="background:#efeae2; padding:18px;">
								<div style="background:#d9fdd3; border-radius:8px; padding:10px 14px; max-width:85%; margin-left:auto; font-size:12.5px; color:#111b21; box-shadow:0 1px 2px rgba(0,0,0,0.1);">
									<?php esc_html_e( 'Hi! I have a question about', 'dragwyb-click-to-chat' ); ?> <strong>Nike Air Jordan Sneakers</strong> (https://mystore.com/product/air-jordan). <?php esc_html_e( 'Is this in stock?', 'dragwyb-click-to-chat' ); ?>
									<div style="text-align:right; font-size:10px; color:#667781; margin-top:4px;">10:42 AM ✓✓</div>
								</div>
							</div>
						</div>
					</div>
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 04', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-highlight">⭐ <?php esc_html_e( 'Highlighted Feature', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Dynamic WhatsApp Inquiries & Page Tags', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Personalize WhatsApp click greetings automatically so your agents know the exact page or product the visitor was viewing.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Page Context Tags:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Include {title} and {url} tags inside your greeting template.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Higher Conversion Rate:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Visitors send inquiries in 1 tap without typing out what item they are looking at.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Step 5: HIGHLIGHTED FEATURE - Shortcode & Embeds -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 05', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-highlight">⭐ <?php esc_html_e( 'Highlighted Feature', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Shortcode Placement & Page Builder Support', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'In addition to the automatic floating widget, embed contact buttons inside Gutenberg blocks, Elementor, Divi, headers, or footers.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<div class="dctc-shortcode-copy-box" style="margin-top: 14px; display: inline-flex;">
							<code>[dctc-widget]</code>
							<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc-widget]">
								<span class="dctc-copy-text"><?php esc_html_e( 'Copy Shortcode', 'dragwyb-click-to-chat' ); ?></span>
							</button>
						</div>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Gutenberg / Elementor Integration', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body" style="font-family: monospace; font-size: 12.5px; color: #334155; line-height: 1.6;">
								<span style="color:#64748b;">&lt;!-- Inside Page Content / Block Editor --&gt;</span><br />
								&lt;div class="contact-cta-section"&gt;<br />
								&nbsp;&nbsp;&lt;h3&gt;Have questions? Contact our team:&lt;/h3&gt;<br />
								&nbsp;&nbsp;<strong style="color:#7c3aed;">[dctc-widget]</strong><br />
								&lt;/div&gt;
							</div>
						</div>
					</div>
				</div>

			<?php else : ?>
				<!-- DISABLED STATE -->
				<div class="dctc-guide-disabled-hero">
					<div class="dctc-guide-disabled-icon">📱</div>
					<span class="dctc-status-pill is-inactive"><?php esc_html_e( 'Module Disabled', 'dragwyb-click-to-chat' ); ?></span>
					<h2 class="dctc-guide-disabled-title"><?php esc_html_e( 'Social Channels Floating Widget', 'dragwyb-click-to-chat' ); ?></h2>
					<p class="dctc-guide-disabled-desc">
						<?php esc_html_e( 'Connect with website visitors across 8+ social channels (WhatsApp, Facebook Messenger, Phone, Email, Telegram, Instagram, SMS, and LinkedIn).', 'dragwyb-click-to-chat' ); ?>
					</p>

					<div class="dctc-guide-benefits-grid">
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">⚡</span>
							<strong><?php esc_html_e( 'Instant Conversions', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Remove friction and allow visitors to contact you in 1 tap without long forms.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">🎨</span>
							<strong><?php esc_html_e( 'Brand Tailored', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Fully customize colors, sizes, animations, and device visibility rules.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">💬</span>
							<strong><?php esc_html_e( 'WhatsApp Context', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Pre-fills product name and URL in WhatsApp greetings automatically.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
					</div>

					<div style="margin-top: 25px;">
						<button type="button" class="dctc-btn dctc-btn-primary dctc-guide-activate-btn" data-feature="channels" style="padding: 12px 28px; font-size: 15px;">
							<span>⚡</span> <?php esc_html_e( 'Activate & Enable Social Channels', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<!-- ================================================================= -->
		<!-- TAB 2: AI ASSISTANT GUIDE                                         -->
		<!-- ================================================================= -->
		<div id="dctc-guide-tab-ai" class="dctc-guide-tab-content <?php echo 'ai' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'ai' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<?php if ( $dctc_ai_enabled ) : ?>
				<!-- Hero Banner -->
				<div class="dctc-guide-hero-banner">
					<div class="dctc-guide-hero-text">
						<div class="dctc-guide-hero-badge-row">
							<span class="dctc-status-pill is-active"><?php esc_html_e( 'Feature Active', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-guide-pill-tag">🤖 <?php esc_html_e( 'Autonomous AI Copilot', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h1 class="dctc-guide-hero-title"><?php esc_html_e( 'AI Assistant Setup & Training Masterclass', 'dragwyb-click-to-chat' ); ?></h1>
						<p class="dctc-guide-hero-desc">
							<?php esc_html_e( 'Deploy an AI chatbot trained on your website content, qualify leads automatically, and hand off conversations to human agents.', 'dragwyb-click-to-chat' ); ?>
						</p>
					</div>
					<div class="dctc-guide-hero-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ); ?>" class="dctc-btn dctc-btn-primary">
							<?php esc_html_e( 'Open AI Assistant Dashboard &rarr;', 'dragwyb-click-to-chat' ); ?>
						</a>
					</div>
				</div>

				<!-- Quick Setup Checklist Map -->
				<div class="dctc-guide-quick-map">
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-required"><?php esc_html_e( 'REQUIRED', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '1. Connect API Key', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'OpenAI, Gemini, Claude, OpenRouter, Groq or Ollama', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-required"><?php esc_html_e( 'REQUIRED', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '2. Persona Prompt', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Define tone, brand role, and answer boundaries', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-visibility"><?php esc_html_e( 'VISIBILITY', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '3. Sitewide Display', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Toggle Entire Site display or embed via shortcode', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-highlight"><?php esc_html_e( 'HIGHLIGHT', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '4. Knowledge Base', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Sync website articles, FAQs & vector search', 'dragwyb-click-to-chat' ); ?></small>
					</div>
				</div>

				<!-- Step 1: REQUIRED - Connect AI Engine -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 01', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-required">🔴 <?php esc_html_e( 'Mandatory Configuration', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Connect AI Engine Provider & API Key', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Under "AI Engine & Prompt", configure your AI model provider credentials to power the intelligent responses.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Supported AI Providers:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'OpenAI (GPT-4o, GPT-4o-mini), Google Gemini (Gemini 1.5 Flash/Pro), Anthropic Claude (Claude 3.5 Sonnet), OpenRouter, Groq, DeepSeek, and Local Ollama.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Model Recommendation:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'We recommend "gpt-4o-mini" or "gemini-1.5-flash" for ultra-fast replies under 500ms and minimal token cost.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Max Tokens & Temperature:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Keep temperature around 0.3 - 0.7 for accurate, factual, and customer-friendly answers.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'AI Engine Settings', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div class="dctc-mockup-item is-active">
									<span class="dctc-mockup-icon" style="background:#10a37f; color:#fff;">⚡</span>
									<div>
										<strong>OpenAI / Gemini 1.5 Flash</strong>
										<small>sk-proj-••••••••••••• (Connected)</small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div style="margin-top:10px; padding:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:12px; color:#475569;">
									<strong><?php esc_html_e( 'Model:', 'dragwyb-click-to-chat' ); ?></strong> gpt-4o-mini • <strong><?php esc_html_e( 'Max Tokens:', 'dragwyb-click-to-chat' ); ?></strong> 600 • <strong><?php esc_html_e( 'Temp:', 'dragwyb-click-to-chat' ); ?></strong> 0.4
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Step 2: REQUIRED - System Instructions Persona -->
				<div class="dctc-zigzag-row is-reversed">
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'System Prompt Editor', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body" style="font-family:monospace; font-size:12px; color:#334155; line-height:1.6; background:#fafafa; border-radius:8px; padding:14px; border:1px solid #e2e8f0;">
								<span style="color:#7c3aed; font-weight:700;"># Brand Assistant Persona</span><br />
								"You are the friendly customer concierge for our brand. Answer visitor questions courteously using our knowledge base. If you don't know the answer, offer to connect them with a human specialist."
							</div>
						</div>
					</div>
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 02', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-required">🔴 <?php esc_html_e( 'Mandatory Configuration', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Define System Persona & Brand Guidelines', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Set clear instructions so the bot knows how to introduce itself and handle customer inquiries.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Role & Tone:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Define whether the tone is professional, friendly, enthusiastic, or technical.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Multilingual Support:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'The assistant automatically detects the visitor\'s language and replies in 50+ languages.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Guardrails & Boundaries:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Instruct the bot to stay on topic and prevent discussing competitor products.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Step 3: VISIBILITY RULES - Sitewide Display & Shortcodes -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 03', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-visibility">🎯 <?php esc_html_e( 'Visibility Rules & Targeting', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Sitewide Display & Shortcode Placement', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Control how and where the AI Chatbot popup appears across your website pages.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Display on Entire Site:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'In "Chatbot Settings > Display Rules", toggle Sitewide display on or off.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Auto-Popup Greeting Delay:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Automatically expand the chatbot window after a 5s time delay to greet visitors.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Manual Shortcode Embedding:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Embed the full interactive AI chatbot directly inside any page or contact hub with the shortcode.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
						<div class="dctc-shortcode-copy-box" style="margin-top: 14px; display: inline-flex;">
							<code>[dctc_ai]</code>
							<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc_ai]">
								<span class="dctc-copy-text"><?php esc_html_e( 'Copy AI Shortcode', 'dragwyb-click-to-chat' ); ?></span>
							</button>
						</div>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Chatbot Display Controls', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px;">
									<span style="font-size:12.5px; font-weight:600; color:#1e293b;">🌐 <?php esc_html_e( 'Display on Entire Site', 'dragwyb-click-to-chat' ); ?></span>
									<span style="color:#10b981; font-weight:700; font-size:12px;">✓ <?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
									<span style="font-size:12.5px; font-weight:600; color:#1e293b;">📱 <?php esc_html_e( 'Mobile Responsive Mode', 'dragwyb-click-to-chat' ); ?></span>
									<span style="color:#10b981; font-weight:700; font-size:12px;">✓ <?php esc_html_e( 'Full Screen Drawer', 'dragwyb-click-to-chat' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Step 4: HIGHLIGHTED FEATURE - Knowledge Base Indexing (RAG) -->
				<div class="dctc-zigzag-row is-reversed">
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Knowledge Base (RAG)', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div class="dctc-mockup-item is-active">
									<span class="dctc-mockup-icon" style="background:#4f46e5; color:#fff;">📚</span>
									<div>
										<strong><?php esc_html_e( 'WordPress Posts & Pages', 'dragwyb-click-to-chat' ); ?></strong>
										<small><?php esc_html_e( '48 articles indexed • Auto-sync on save', 'dragwyb-click-to-chat' ); ?></small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Synced', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-mockup-item is-active" style="margin-top:8px;">
									<span class="dctc-mockup-icon" style="background:#059669; color:#fff;">📋</span>
									<div>
										<strong><?php esc_html_e( 'Custom FAQs & Policies', 'dragwyb-click-to-chat' ); ?></strong>
										<small><?php esc_html_e( '24 Knowledge Chunks', 'dragwyb-click-to-chat' ); ?></small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
							</div>
						</div>
					</div>
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 04', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-highlight">⭐ <?php esc_html_e( 'Highlighted Feature', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Train Bot on Website Content (RAG)', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Provide facts, FAQs, product details, and let the AI index your WordPress posts for accurate, zero-hallucination answers.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Auto-Sync on Post Update:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Whenever you edit or publish a post, the chatbot updates its memory instantly.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Vector Search Embeddings:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Pinecone vector database integration retrieves exact matching answers across thousands of articles in milliseconds.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Private Knowledge Snippets:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Add internal pricing charts, return policies, and promo codes without publishing them on public pages.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Step 5: HIGHLIGHTED FEATURE - Automated Lead Scoring & Support Center -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 05', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-highlight">⭐ <?php esc_html_e( 'Highlighted Feature', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Automated Lead Scoring & Support Center Handoff', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Collect customer inquiries, score leads automatically, and intervene with live human replies when needed.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'CRM Lead Capture:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Autonomous collection of visitor Name, Email, Phone, Company, and Project Budget.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Lead Scoring (0-100):', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'AI calculates buyer urgency score and tags high-intent leads.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Support Center Live Takeover:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Intervene in real-time or route conversations to WhatsApp or Support Center.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Captured Lead Card', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div style="display:flex; justify-content:space-between; align-items:center;">
									<strong>Alex Morgan</strong>
									<span style="background:#ecfdf5; color:#065f46; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700;">Score: 92/100 (Hot Lead)</span>
								</div>
								<div style="font-size:12px; color:#64748b; margin-top:6px; line-height:1.5;">
									📧 alex@company.com • 📱 +1 (555) 019-2834<br />
									💼 Budget: $5,000+ • Timeline: Immediate
								</div>
							</div>
						</div>
					</div>
				</div>

			<?php else : ?>
				<!-- DISABLED STATE -->
				<div class="dctc-guide-disabled-hero">
					<div class="dctc-guide-disabled-icon">🤖</div>
					<span class="dctc-status-pill is-inactive"><?php esc_html_e( 'Module Disabled', 'dragwyb-click-to-chat' ); ?></span>
					<h2 class="dctc-guide-disabled-title"><?php esc_html_e( 'AI Assistant & Autonomous Chatbot', 'dragwyb-click-to-chat' ); ?></h2>
					<p class="dctc-guide-disabled-desc">
						<?php esc_html_e( 'Deploy a 24/7 intelligent AI chatbot trained on your website content with automated lead scoring and live agent handoffs.', 'dragwyb-click-to-chat' ); ?>
					</p>

					<div class="dctc-guide-benefits-grid">
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">🧠</span>
							<strong><?php esc_html_e( 'Website Knowledge (RAG)', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Accurate answers based on your actual products, blog posts, and custom FAQs.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">🎯</span>
							<strong><?php esc_html_e( 'Lead Scoring & Capture', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Collect customer contact info and score intent autonomously.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">💬</span>
							<strong><?php esc_html_e( 'Live Agent Handoff', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Take over conversations live or route to WhatsApp and Support Tickets.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
					</div>

					<div style="margin-top: 25px;">
						<button type="button" class="dctc-btn dctc-btn-primary dctc-guide-activate-btn" data-feature="ai" style="padding: 12px 28px; font-size: 15px;">
							<span>⚡</span> <?php esc_html_e( 'Activate & Enable AI Assistant', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<!-- ================================================================= -->
		<!-- TAB 3: SUPPORT CENTER GUIDE                                       -->
		<!-- ================================================================= -->
		<div id="dctc-guide-tab-support" class="dctc-guide-tab-content <?php echo 'support' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'support' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">

			<?php if ( $dctc_support_enabled ) : ?>
				<!-- Hero Banner -->
				<div class="dctc-guide-hero-banner">
					<div class="dctc-guide-hero-text">
						<div class="dctc-guide-hero-badge-row">
							<span class="dctc-status-pill is-active"><?php esc_html_e( 'Feature Active', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-guide-pill-tag">🛡️ <?php esc_html_e( 'Dedicated Helpdesk Workspace', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h1 class="dctc-guide-hero-title"><?php esc_html_e( 'Support Center & Ticket Management Guide', 'dragwyb-click-to-chat' ); ?></h1>
						<p class="dctc-guide-hero-desc">
							<?php esc_html_e( 'Manage support tickets, assign staff agents, configure customer frontend portals, and view WooCommerce order profiles.', 'dragwyb-click-to-chat' ); ?>
						</p>
					</div>
					<div class="dctc-guide-hero-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-support-center' ) ); ?>" class="dctc-btn dctc-btn-primary">
							<?php esc_html_e( 'Open Support Workspace &rarr;', 'dragwyb-click-to-chat' ); ?>
						</a>
					</div>
				</div>

				<!-- Quick Setup Checklist Map -->
				<div class="dctc-guide-quick-map">
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-required"><?php esc_html_e( 'REQUIRED', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '1. Ticket Categories', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Create Technical, Billing, Presales & Returns departments', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-required"><?php esc_html_e( 'REQUIRED', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '2. Staff Agents', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Assign WordPress admins/editors to handle tickets', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-visibility"><?php esc_html_e( 'VISIBILITY', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '3. Customer Portals', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Embed [dctc_support_portal] on your support page', 'dragwyb-click-to-chat' ); ?></small>
					</div>
					<div class="dctc-quick-map-item">
						<span class="dctc-map-badge is-highlight"><?php esc_html_e( 'HIGHLIGHT', 'dragwyb-click-to-chat' ); ?></span>
						<strong><?php esc_html_e( '4. SLA & Woo Context', 'dragwyb-click-to-chat' ); ?></strong>
						<small><?php esc_html_e( 'Order history lookup, urgent SLA flags & auto-routing', 'dragwyb-click-to-chat' ); ?></small>
					</div>
				</div>

				<!-- Step 1: REQUIRED - Ticket Categories & Routing -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 01', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-required">🔴 <?php esc_html_e( 'Mandatory Configuration', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Setup Ticket Categories & Departments', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'In Support Center Settings, configure categories so incoming tickets are routed to the right team members.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Recommended Categories:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Technical Support, Billing & Invoices, Product Inquiries, and Returns / Refunds.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Custom Tags:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Tag tickets with keywords (e.g., bug, urgent, v1.2, refund-approved) for fast search and filtering.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Category Configuration', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div class="dctc-mockup-item is-active">
									<span class="dctc-mockup-icon" style="background:#3b82f6; color:#fff;">🛠️</span>
									<div>
										<strong><?php esc_html_e( 'Technical Support', 'dragwyb-click-to-chat' ); ?></strong>
										<small><?php esc_html_e( 'Default Category • 12 Active Tickets', 'dragwyb-click-to-chat' ); ?></small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
								<div class="dctc-mockup-item is-active" style="margin-top:8px;">
									<span class="dctc-mockup-icon" style="background:#10b981; color:#fff;">💳</span>
									<div>
										<strong><?php esc_html_e( 'Billing & Subscriptions', 'dragwyb-click-to-chat' ); ?></strong>
										<small><?php esc_html_e( 'Auto-assigned to Finance team', 'dragwyb-click-to-chat' ); ?></small>
									</div>
									<span class="dctc-mockup-tag"><?php esc_html_e( 'Active', 'dragwyb-click-to-chat' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Step 2: REQUIRED - Staff Agents Assignment -->
				<div class="dctc-zigzag-row is-reversed">
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Staff Agent Workload', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px;">
									<div>
										<strong>Sarah Jenkins</strong><br />
										<small style="color:#64748b;">Senior Support Lead</small>
									</div>
									<span style="font-size:11px; background:#eff6ff; color:#1e40af; padding:2px 8px; border-radius:12px; font-weight:700;">4 Assigned</span>
								</div>
								<div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
									<div>
										<strong>Michael Scott</strong><br />
										<small style="color:#64748b;">Technical Specialist</small>
									</div>
									<span style="font-size:11px; background:#ecfdf5; color:#065f46; padding:2px 8px; border-radius:12px; font-weight:700;">2 Assigned</span>
								</div>
							</div>
						</div>
					</div>
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 02', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-required">🔴 <?php esc_html_e( 'Mandatory Configuration', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Assign Staff Agents & Balance Workloads', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Select WordPress users to act as support specialists. Assign tickets manually or let the system route them evenly.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'Agent Roles:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Any WordPress user with Administrator or Editor permissions can be assigned as a support specialist.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Internal Private Notes:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Staff can write private internal notes on tickets that remain invisible to customers.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Auto Email Alerts:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Agents receive email alerts whenever a new ticket is assigned or a customer replies.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Step 3: VISIBILITY - Customer Frontend Portals (Shortcodes) -->
				<div class="dctc-zigzag-row">
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 03', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-visibility">🎯 <?php esc_html_e( 'Visibility & Customer Portals', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'Customer Support Portals (Shortcodes)', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Create a dedicated "Help / Support" page on your website and embed the customer support portal shortcodes.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<div style="display:flex; flex-direction:column; gap:10px; margin-top:14px;">
							<div class="dctc-shortcode-copy-box">
								<code>[dctc_support_portal]</code>
								<button type="button" class="dctc-copy-btn" data-clipboard-text="[dctc_support_portal]">
									<span class="dctc-copy-text"><?php esc_html_e( 'Copy Shortcode', 'dragwyb-click-to-chat' ); ?></span>
								</button>
							</div>
							<p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">
								<?php esc_html_e( 'Renders the complete self-service customer portal including interactive ticket submission form, user ticket history dashboard, real-time message threading, and file attachment support.', 'dragwyb-click-to-chat' ); ?>
							</p>
						</div>
					</div>
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'Frontend Customer Portal', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div style="font-size:12px; color:#64748b; margin-bottom:8px;">
									<strong><?php esc_html_e( 'Submit a Support Ticket', 'dragwyb-click-to-chat' ); ?></strong>
								</div>
								<div style="border:1px solid #e2e8f0; border-radius:6px; padding:8px 10px; font-size:11.5px; color:#94a3b8; margin-bottom:6px;">
									<?php esc_html_e( 'Subject: Order #8921 Delivery Question', 'dragwyb-click-to-chat' ); ?>
								</div>
								<div style="border:1px solid #e2e8f0; border-radius:6px; padding:8px 10px; font-size:11.5px; color:#94a3b8; margin-bottom:8px;">
									<?php esc_html_e( 'Category: Shipping & Delivery', 'dragwyb-click-to-chat' ); ?>
								</div>
								<div style="background:#7c3aed; color:#fff; text-align:center; padding:6px; border-radius:6px; font-size:11.5px; font-weight:700;">
									<?php esc_html_e( 'Submit Ticket &rarr;', 'dragwyb-click-to-chat' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Step 4: HIGHLIGHTED FEATURE - WooCommerce & SLA Metrics -->
				<div class="dctc-zigzag-row is-reversed">
					<div class="dctc-zigzag-visual">
						<div class="dctc-mockup-card">
							<div class="dctc-mockup-header">
								<span class="dctc-mockup-dot red"></span>
								<span class="dctc-mockup-dot yellow"></span>
								<span class="dctc-mockup-dot green"></span>
								<span class="dctc-mockup-title"><?php esc_html_e( 'WooCommerce Customer Context', 'dragwyb-click-to-chat' ); ?></span>
							</div>
							<div class="dctc-mockup-body">
								<div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px; font-size:12px; color:#1e40af;">
									<strong>🛒 <?php esc_html_e( 'WooCommerce Customer Profile', 'dragwyb-click-to-chat' ); ?></strong><br />
									3 Lifetime Orders • $480 Total Spend • Last Order: #8921 (Shipped)
								</div>
								<div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
									<span style="font-size:11px; font-weight:700; color:#ef4444; background:#fef2f2; padding:2px 8px; border-radius:12px;">🔥 SLA: Urgent (Reply due in 2h)</span>
									<span style="font-size:11px; color:#64748b;">Status: Open</span>
								</div>
							</div>
						</div>
					</div>
					<div class="dctc-zigzag-content">
						<div class="dctc-step-badge-row">
							<span class="dctc-step-chip"><?php esc_html_e( 'Step 04', 'dragwyb-click-to-chat' ); ?></span>
							<span class="dctc-chip-tag is-highlight">⭐ <?php esc_html_e( 'Highlighted Feature', 'dragwyb-click-to-chat' ); ?></span>
						</div>
						<h2 class="dctc-zigzag-title"><?php esc_html_e( 'WooCommerce Intelligence & SLA Priority Tracking', 'dragwyb-click-to-chat' ); ?></h2>
						<p class="dctc-zigzag-desc">
							<?php esc_html_e( 'Equip agents with customer purchase context right beside the ticket thread to resolve support requests 3x faster.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<ul class="dctc-guide-bullet-list">
							<li><strong><?php esc_html_e( 'WooCommerce Order Context:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'View lifetime spend, recent orders, order items, and tracking status directly beside the ticket.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Priority SLA Indicators:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Tag tickets with Urgent, High, or Normal priority flags with countdown response targets.', 'dragwyb-click-to-chat' ); ?></li>
							<li><strong><?php esc_html_e( 'Complete Audit Timeline:', 'dragwyb-click-to-chat' ); ?></strong> <?php esc_html_e( 'Full activity history recording every status change, reassignment, and reply timestamp.', 'dragwyb-click-to-chat' ); ?></li>
						</ul>
					</div>
				</div>

			<?php else : ?>
				<!-- DISABLED STATE -->
				<div class="dctc-guide-disabled-hero">
					<div class="dctc-guide-disabled-icon">🛡️</div>
					<span class="dctc-status-pill is-inactive"><?php esc_html_e( 'Module Disabled', 'dragwyb-click-to-chat' ); ?></span>
					<h2 class="dctc-guide-disabled-title"><?php esc_html_e( 'Support Center & Helpdesk Ticketing', 'dragwyb-click-to-chat' ); ?></h2>
					<p class="dctc-guide-disabled-desc">
						<?php esc_html_e( 'Provide enterprise customer care with dedicated ticket queues, staff agent rosters, customer frontend portals, and WooCommerce purchase intelligence.', 'dragwyb-click-to-chat' ); ?>
					</p>

					<div class="dctc-guide-benefits-grid">
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">🎫</span>
							<strong><?php esc_html_e( 'Full Helpdesk Workspace', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Dedicated ticketing dashboard positioned directly below Core Pages in your WordPress sidebar.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">👨‍💼</span>
							<strong><?php esc_html_e( 'Agent Load Balancing', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'Assign tickets to specialists and collaborate with private internal notes.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
						<div class="dctc-benefit-card">
							<span class="dctc-benefit-icon">🛒</span>
							<strong><?php esc_html_e( 'WooCommerce Intelligence', 'dragwyb-click-to-chat' ); ?></strong>
							<p><?php esc_html_e( 'View customer lifetime value and past orders right beside each support ticket.', 'dragwyb-click-to-chat' ); ?></p>
						</div>
					</div>

					<div style="margin-top: 25px;">
						<button type="button" class="dctc-btn dctc-btn-primary dctc-guide-activate-btn" data-feature="support" style="padding: 12px 28px; font-size: 15px;">
							<span>⚡</span> <?php esc_html_e( 'Activate & Enable Support Center', 'dragwyb-click-to-chat' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>

		</div>

		<!-- ================================================================= -->
		<!-- TAB 4: SETUP WIZARD & CONFIGURATION OVERVIEW                       -->
		<!-- ================================================================= -->
		<div id="dctc-guide-tab-setup" class="dctc-guide-tab-content <?php echo 'setup' === $dctc_active_tab ? 'active' : ''; ?>" style="<?php echo 'setup' === $dctc_active_tab ? 'display: block;' : 'display: none;'; ?>">
			<div class="dctc-setup-landing-container" style="max-width: 100%; margin: 0 auto;">
				<div class="dctc-setup-landing-header">
					<div class="dctc-setup-landing-header__badge">
						<span>✦</span> <?php esc_html_e( 'Configuration & Setup', 'dragwyb-click-to-chat' ); ?>
					</div>
					<h1 class="dctc-setup-landing-header__title">
						<?php esc_html_e( 'Dragwyb Click to Chat Setup', 'dragwyb-click-to-chat' ); ?>
					</h1>
					<p class="dctc-setup-landing-header__desc">
						<?php esc_html_e( 'Configure communication channels, AI assistant automation, and customer support helpdesk from one centralized place.', 'dragwyb-click-to-chat' ); ?>
					</p>
				</div>

				<div class="dctc-setup-landing-cards">
					<div class="dctc-setup-landing-card">
						<div class="dctc-setup-landing-card__icon" style="background: #ec4899;">
							<span class="dashicons dashicons-format-chat" style="font-size: 20px; color: #fff;"></span>
						</div>
						<h3 class="dctc-setup-landing-card__title">
							<?php esc_html_e( 'Social Chat (Channels)', 'dragwyb-click-to-chat' ); ?>
						</h3>
						<p class="dctc-setup-landing-card__desc">
							<?php esc_html_e( 'Configure WhatsApp, Phone, Email and custom action buttons for your visitors.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-channels' ) ); ?>" class="dctc-setup-landing-card__link">
							<?php esc_html_e( 'Configure Channels →', 'dragwyb-click-to-chat' ); ?>
						</a>
					</div>

					<div class="dctc-setup-landing-card">
						<div class="dctc-setup-landing-card__icon" style="background: #6366f1;">
							<span class="dashicons dashicons-superhero" style="font-size: 20px; color: #fff;"></span>
						</div>
						<h3 class="dctc-setup-landing-card__title">
							<?php esc_html_e( 'AI Assistant', 'dragwyb-click-to-chat' ); ?>
						</h3>
						<p class="dctc-setup-landing-card__desc">
							<?php esc_html_e( 'Manage AI engine providers, training content, knowledge base and lead scoring.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat' ) ); ?>" class="dctc-setup-landing-card__link">
							<?php esc_html_e( 'Open AI Dashboard →', 'dragwyb-click-to-chat' ); ?>
						</a>
					</div>

					<div class="dctc-setup-landing-card">
						<div class="dctc-setup-landing-card__icon" style="background: #10b981;">
							<span class="dashicons dashicons-tickets-alt" style="font-size: 20px; color: #fff;"></span>
						</div>
						<h3 class="dctc-setup-landing-card__title">
							<?php esc_html_e( 'Support Center', 'dragwyb-click-to-chat' ); ?>
						</h3>
						<p class="dctc-setup-landing-card__desc">
							<?php esc_html_e( 'Manage support tickets, assign agent staff, taxonomies and customer portal.', 'dragwyb-click-to-chat' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-support-center' ) ); ?>" class="dctc-setup-landing-card__link">
							<?php esc_html_e( 'View Support Center →', 'dragwyb-click-to-chat' ); ?>
						</a>
					</div>
				</div>

				<div class="dctc-setup-landing-action-box">
					<h2 class="dctc-setup-landing-action-title">
						<?php esc_html_e( 'Need to re-run the complete onboarding?', 'dragwyb-click-to-chat' ); ?>
					</h2>
					<p class="dctc-setup-landing-action-desc">
						<?php esc_html_e( 'You can launch the 4-step wizard anytime to configure features, contact channels, and AI settings all in one flow.', 'dragwyb-click-to-chat' ); ?>
					</p>
					<button
						type="button"
						class="dctc-btn-primary dctc-guide-launch-wizard-btn"
						style="padding: 14px 36px; font-size: 15px; cursor: pointer;"
					>
						🚀 <?php esc_html_e( 'Launch 4-Step Setup Wizard', 'dragwyb-click-to-chat' ); ?>
					</button>
				</div>
			</div>
		</div>

	</div>

	<!-- React Root for Onboarding Wizard Full-Screen Modal -->
	<div id="dctc-ai-admin-root"></div>

</div>
