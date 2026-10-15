<?php

/**
 * Section 4: Settings & Modules
 * Modular activation for Channels, AI Assistant, and Support Center, plus usage instructions.
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
?>

<h2 class="dctc-section-title"><?php esc_html_e( 'Modules & Feature Management', 'dragwyb-click-to-chat' ); ?></h2>
<p style="color: #6b7280; font-size: 14px; margin-top: -10px; margin-bottom: 25px; line-height: 1.5;">
	<?php esc_html_e( 'Enable or disable the core plugin modules. You can independently activate multi-channel buttons, autonomous AI chatbot assistance, and the standalone ticketing support desk.', 'dragwyb-click-to-chat' ); ?>
</p>

<!-- Modular Feature Cards -->
<div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 35px;">

	<!-- Module 1: Social Channels -->
	<div class="dctc-module-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); transition: all 0.2s ease;">
		<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
			<div style="flex: 1;">
				<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
					<span style="font-size: 22px;">📱</span>
					<strong style="font-size: 16px; color: #111827;"><?php esc_html_e( 'Social Channels Floating Widget', 'dragwyb-click-to-chat' ); ?></strong>
					<span id="dctc-status-channels" class="dctc-module-badge <?php echo $dctc_channels_enabled ? 'is-active' : 'is-disabled'; ?>" style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 12px; <?php echo $dctc_channels_enabled ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
						<?php echo $dctc_channels_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
					</span>
				</div>
				<p style="color: #6b7280; font-size: 13.5px; margin: 0 0 12px; line-height: 1.5;">
					<?php esc_html_e( 'Display the multi-channel floating action button (WhatsApp, Facebook, Phone, Email, Telegram, Instagram, and more) across your website pages.', 'dragwyb-click-to-chat' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-click-to-chat-channels' ) ); ?>" class="button button-secondary" style="font-size: 12px; font-weight: 600;">
					<?php esc_html_e( 'Configure Channels &rarr;', 'dragwyb-click-to-chat' ); ?>
				</a>
			</div>

			<div>
				<label style="display: flex; align-items: center; cursor: pointer; gap: 8px;">
					<input type="checkbox" id="dctc_channels_enabled" name="channels_enabled" value="1" <?php checked( $dctc_channels_enabled, true ); ?> style="width: 20px; height: 20px; accent-color: #8e44ad;" />
					<span style="font-weight: 600; color: #374151; font-size: 14px;"><?php esc_html_e( 'Enable Channels', 'dragwyb-click-to-chat' ); ?></span>
				</label>
			</div>
		</div>
	</div>

	<!-- Module 2: AI Assistant -->
	<div class="dctc-module-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); transition: all 0.2s ease;">
		<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
			<div style="flex: 1;">
				<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
					<span style="font-size: 22px;">🤖</span>
					<strong style="font-size: 16px; color: #111827;"><?php esc_html_e( 'AI Assistant & Autonomous Chatbot', 'dragwyb-click-to-chat' ); ?></strong>
					<span id="dctc-status-ai" class="dctc-module-badge <?php echo $dctc_ai_enabled ? 'is-active' : 'is-disabled'; ?>" style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 12px; <?php echo $dctc_ai_enabled ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
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
					<input type="checkbox" id="dctc_ai_assistant_enabled" name="ai_assistant_enabled" value="1" <?php checked( $dctc_ai_enabled, true ); ?> style="width: 20px; height: 20px; accent-color: #8e44ad;" />
					<span style="font-weight: 600; color: #374151; font-size: 14px;"><?php esc_html_e( 'Enable AI Assistant', 'dragwyb-click-to-chat' ); ?></span>
				</label>
			</div>
		</div>
	</div>

	<!-- Module 3: Help Support Desk -->
	<div class="dctc-module-card" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); transition: all 0.2s ease;">
		<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 20px;">
			<div style="flex: 1;">
				<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
					<span style="font-size: 22px;">🛡️</span>
					<strong style="font-size: 16px; color: #111827;"><?php esc_html_e( 'Support Center & Helpdesk Ticketing', 'dragwyb-click-to-chat' ); ?></strong>
					<span id="dctc-status-support" class="dctc-module-badge <?php echo $dctc_support_enabled ? 'is-active' : 'is-disabled'; ?>" style="font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 12px; <?php echo $dctc_support_enabled ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;'; ?>">
						<?php echo $dctc_support_enabled ? esc_html__( 'Active', 'dragwyb-click-to-chat' ) : esc_html__( 'Disabled', 'dragwyb-click-to-chat' ); ?>
					</span>
				</div>
				<p style="color: #6b7280; font-size: 13.5px; margin: 0 0 12px; line-height: 1.5;">
					<?php esc_html_e( 'Dedicated full-page support workspace with ticket queues, staff workload management, WooCommerce customer profiles, category routing, and human-in-the-loop controls.', 'dragwyb-click-to-chat' ); ?>
				</p>
				<?php if ( $dctc_support_enabled ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dragwyb-support-center' ) ); ?>" class="button button-secondary" style="font-size: 12px; font-weight: 600;">
						<?php esc_html_e( 'Open Support Center &rarr;', 'dragwyb-click-to-chat' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div>
				<label style="display: flex; align-items: center; cursor: pointer; gap: 8px;">
					<input type="checkbox" id="dctc_support_center_enabled" name="support_center_enabled" value="1" <?php checked( $dctc_support_enabled, true ); ?> style="width: 20px; height: 20px; accent-color: #8e44ad;" />
					<span style="font-weight: 600; color: #374151; font-size: 14px;"><?php esc_html_e( 'Enable Support Center', 'dragwyb-click-to-chat' ); ?></span>
				</label>
			</div>
		</div>
	</div>

</div>

<!-- Shortcode Info -->
<div class="dctc-info-box" style="background: #f3e8ff; border-left-color: #8e44ad; border-radius: 8px; margin-bottom: 30px;">
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

<!-- Current Settings Summary -->
<div>
	<h3 style="font-size: 17px; font-weight: 600; color: #374151; margin-bottom: 16px;">
		<?php esc_html_e( 'Active Channels Summary', 'dragwyb-click-to-chat' ); ?>
	</h3>

	<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px 20px;">
		<?php
		$dctc_all_channels           = dctc_get_channels();
		$dctc_has_configured_channel = false;

		foreach ( $dctc_all_channels as $dctc_slug => $dctc_channel ) :
			$dctc_is_enabled = isset( $dctc_settings[ $dctc_slug . '_enabled' ] ) && '1' === $dctc_settings[ $dctc_slug . '_enabled' ];
			$dctc_value      = isset( $dctc_settings[ $dctc_slug . '_value' ] ) ? $dctc_settings[ $dctc_slug . '_value' ] : '';

			if ( $dctc_is_enabled || ! empty( $dctc_value ) ) :
				$dctc_has_configured_channel = true;
				$dctc_active_color           = $dctc_is_enabled ? '#10b981' : '#9ca3af';
				$dctc_status_text            = $dctc_is_enabled ? '✓ ' . __( 'Active', 'dragwyb-click-to-chat' ) : '○ ' . __( 'Inactive', 'dragwyb-click-to-chat' );
				?>
				<div style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo esc_attr( $dctc_channel['color'] ); ?>">
								<?php echo wp_kses( $dctc_channel['icon'], array( 'path' => array( 'd' => array() ) ) ); ?>
							</svg>
						</div>
						<div style="flex: 1;">
							<strong><?php echo esc_html( $dctc_channel['label'] ); ?>:</strong>
							<span style="color: #6b7280; margin-left: 10px; word-break: break-all;">
								<?php echo ! empty( $dctc_value ) ? esc_html( $dctc_value ) : '<em>' . esc_html__( 'Not configured', 'dragwyb-click-to-chat' ) . '</em>'; ?>
							</span>
						</div>
						<span style="color: <?php echo esc_attr( $dctc_active_color ); ?>; font-weight: 600; font-size: 13px; white-space: nowrap;">
							<?php echo esc_html( $dctc_status_text ); ?>
						</span>
					</div>
				</div>
				<?php
			endif;
		endforeach;

		if ( ! $dctc_has_configured_channel ) :
			?>
			<div style="padding: 16px; text-align: center; color: #9ca3af; font-size: 13.5px;">
				<?php esc_html_e( 'No channels configured yet. Go to "Select Channels" tab to get started!', 'dragwyb-click-to-chat' ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>