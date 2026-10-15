<?php

/**
 * Section 1: Select Channels (Dynamic Multi-Channel System)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all channels from registry
$dctc_all_channels = dctc_get_channels();

// Phase 1: Top 9 social channels
$dctc_phase_one_channels = array( 'whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin' );

// Get saved channel data
$dctc_settings     = get_option( 'dctc_settings', array() );
$dctc_channel_data = array();
$dctc_active_count = 0;

foreach ( $dctc_phase_one_channels as $dctc_slug ) {
	$dctc_is_en = isset( $dctc_settings[ $dctc_slug . '_enabled' ] ) ? $dctc_settings[ $dctc_slug . '_enabled' ] : '0';
	if ( $dctc_is_en === '1' ) {
		++$dctc_active_count;
	}
	$dctc_channel_data[ $dctc_slug ] = array(
		'enabled'             => $dctc_is_en,
		'value'               => isset( $dctc_settings[ $dctc_slug . '_value' ] ) ? $dctc_settings[ $dctc_slug . '_value' ] : '',
		'custom_icon'         => isset( $dctc_settings[ $dctc_slug . '_custom_icon' ] ) ? $dctc_settings[ $dctc_slug . '_custom_icon' ] : '',
		'desktop'             => isset( $dctc_settings[ $dctc_slug . '_desktop' ] ) ? $dctc_settings[ $dctc_slug . '_desktop' ] : '1',
		'mobile'              => isset( $dctc_settings[ $dctc_slug . '_mobile' ] ) ? $dctc_settings[ $dctc_slug . '_mobile' ] : '1',
		'chat_widget_enabled' => isset( $dctc_settings[ $dctc_slug . '_chat_widget_enabled' ] ) ? $dctc_settings[ $dctc_slug . '_chat_widget_enabled' ] : '0',
		'default_message'     => isset( $dctc_settings[ $dctc_slug . '_default_message' ] ) ? $dctc_settings[ $dctc_slug . '_default_message' ] : '',
	);
}
?>

<!-- Card 1: Choose Your Channels Grid -->
<div class="dctc-ai-card dctc-section-card">
	<div class="dctc-ai-card__header">
		<div class="dctc-ai-card__header-left">
			<div class="dctc-ai-card-icon" style="background: #eef2ff; color: #4f46e5;">
				<span class="dashicons dashicons-share" style="font-size: 16px; width: 16px; height: 16px;"></span>
			</div>
			<div>
				<h2 class="dctc-card-title"><?php esc_html_e( 'Choose Your Channels', 'dragwyb-click-to-chat' ); ?></h2>
				<p class="dctc-card-subtitle"><?php esc_html_e( 'Select which communication channels you want to display on your website.', 'dragwyb-click-to-chat' ); ?></p>
			</div>
		</div>
		<span class="dctc-ai-status-pill is-active" id="dctc-active-channels-badge">
			<?php
			/* translators: %d: Number of enabled channels. */
			printf( esc_html__( '%d Channels Enabled', 'dragwyb-click-to-chat' ), absint( $dctc_active_count ) );
			?>
		</span>
	</div>

	<div class="dctc-card-body">
		<!-- Channel Selection Grid -->
		<div class="dctc-channels-grid">
			<?php
			foreach ( $dctc_phase_one_channels as $dctc_slug ) :
				$dctc_channel   = $dctc_all_channels[ $dctc_slug ];
				$dctc_is_active = $dctc_channel_data[ $dctc_slug ]['enabled'] === '1';
				?>
				<div class="dctc-channel-card <?php echo $dctc_is_active ? 'active' : ''; ?>" data-channel="<?php echo esc_attr( $dctc_slug ); ?>">
					<!-- Selection Toggle Switch -->
					<label class="dctc-card-switch">
						<input type="checkbox" class="dctc-card-checkbox" <?php checked( $dctc_is_active ); ?>>
						<span class="dctc-card-slider"></span>
					</label>

					<div class="dctc-channel-icon" style="background: <?php echo esc_attr( $dctc_channel['color'] ); ?>;">
						<svg width="28" height="28" viewBox="0 0 24 24" fill="white">
							<?php
							$dctc_allowed_svg = array(
								'path' => array( 'd' => array() ),
								'svg'  => array(
									'viewbox' => array(),
									'fill'    => array(),
									'width'   => array(),
									'height'  => array(),
								),
							);
							echo wp_kses( $dctc_channel['icon'], $dctc_allowed_svg );
							?>
						</svg>
					</div>
					<div class="dctc-channel-name"><?php echo esc_html( $dctc_channel['name'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<!-- Card 2: Channel Configuration Section -->
<div class="dctc-ai-card dctc-section-card" style="margin-top: 20px;">
	<div class="dctc-ai-card__header">
		<div class="dctc-ai-card__header-left">
			<div class="dctc-ai-card-icon" style="background: #ecfdf5; color: #10b981;">
				<span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px;"></span>
			</div>
			<div>
				<h3 class="dctc-card-title"><?php esc_html_e( 'Channel Configuration', 'dragwyb-click-to-chat' ); ?></h3>
				<p class="dctc-card-subtitle"><?php esc_html_e( 'Configure link destinations, phone numbers, greeting popups, and device targeting for active channels.', 'dragwyb-click-to-chat' ); ?></p>
			</div>
		</div>
	</div>

	<div class="dctc-card-body">
		<?php
		foreach ( $dctc_phase_one_channels as $dctc_slug ) :
			$dctc_channel     = $dctc_all_channels[ $dctc_slug ];
			$dctc_is_active   = $dctc_channel_data[ $dctc_slug ]['enabled'] === '1';
			$dctc_value       = $dctc_channel_data[ $dctc_slug ]['value'];
			$dctc_custom_icon = $dctc_channel_data[ $dctc_slug ]['custom_icon'];
			?>

			<!-- <?php echo esc_html( $dctc_channel['name'] ); ?> Settings -->
			<div class="dctc-channel-config-box dctc-channel-config" data-channel-input="<?php echo esc_attr( $dctc_slug ); ?>" style="<?php echo ! $dctc_is_active ? 'display:none;' : ''; ?>">
				<input type="hidden" name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_enabled" id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_enabled" value="<?php echo esc_attr( $dctc_channel_data[ $dctc_slug ]['enabled'] ); ?>">

				<div class="dctc-config-header">
					<div class="dctc-config-header-left">
						<div class="dctc-config-icon-badge" style="background: <?php echo esc_attr( $dctc_channel['color'] ); ?>;">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="white">
								<?php echo wp_kses( $dctc_channel['icon'], $dctc_allowed_svg ); ?>
							</svg>
						</div>
						<label for="dctc_<?php echo esc_attr( $dctc_slug ); ?>_value" class="dctc-config-channel-title">
							<?php echo esc_html( $dctc_channel['name'] ); ?> <?php esc_html_e( 'Settings', 'dragwyb-click-to-chat' ); ?>
						</label>
					</div>
					<span class="dctc-ai-status-pill is-active" style="font-size: 0.65rem;">
						<?php esc_html_e( 'ACTIVE', 'dragwyb-click-to-chat' ); ?>
					</span>
				</div>

				<div class="dctc-config-body">
					<?php if ( $dctc_channel['input_type'] === 'toggle' ) : ?>
						<!-- Toggle for Live Chat -->
						<div class="dctc-toggle-row">
							<label class="dctc-ios-switch">
								<input type="checkbox"
									id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_value"
									name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_value"
									value="1"
									<?php checked( $dctc_value, '1' ); ?>>
								<span class="dctc-ios-slider"></span>
							</label>
							<div>
								<span class="dctc-toggle-label"><?php echo esc_html( $dctc_channel['label'] ); ?></span>
								<p class="dctc-field-hint"><?php esc_html_e( 'Enable a popup chat window where visitors can send you messages directly.', 'dragwyb-click-to-chat' ); ?></p>
							</div>
						</div>
					<?php else : ?>
						<!-- Input field for other channels -->
						<div class="dctc-field-row">
							<label for="dctc_<?php echo esc_attr( $dctc_slug ); ?>_value" class="dctc-field-label">
								<?php echo esc_html( $dctc_channel['label'] ); ?>
							</label>
							<input type="<?php echo esc_attr( $dctc_channel['input_type'] ); ?>"
								id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_value"
								name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_value"
								value="<?php echo esc_attr( $dctc_value ); ?>"
								placeholder="<?php echo esc_attr( $dctc_channel['placeholder'] ); ?>"
								class="dctc-modern-input">
						</div>
					<?php endif; ?>

					<?php if ( in_array( $dctc_slug, array( 'whatsapp', 'instagram', 'telegram' ) ) ) : ?>
						<!-- Chat Widget Settings -->
						<div class="dctc-chat-widget-nested-card">
							<div class="dctc-toggle-row">
								<label class="dctc-ios-switch">
									<input type="checkbox"
										class="dctc-chat-widget-toggle"
										id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_chat_widget_enabled"
										name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_chat_widget_enabled"
										value="1"
										data-target="dctc_<?php echo esc_attr( $dctc_slug ); ?>_default_message_wrapper"
										<?php checked( $dctc_channel_data[ $dctc_slug ]['chat_widget_enabled'], '1' ); ?>>
									<span class="dctc-ios-slider"></span>
								</label>
								<div>
									<span class="dctc-toggle-label"><?php esc_html_e( 'Enable Channel Chat Popup Widget', 'dragwyb-click-to-chat' ); ?></span>
									<p class="dctc-field-hint"><?php esc_html_e( 'Open a popup chat window with a default message instead of redirecting immediately.', 'dragwyb-click-to-chat' ); ?></p>
								</div>
							</div>

							<div id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_default_message_wrapper" class="dctc-drawer-content" style="<?php echo $dctc_channel_data[ $dctc_slug ]['chat_widget_enabled'] === '1' ? 'display:block;' : 'display:none;'; ?>">
								<label for="dctc_<?php echo esc_attr( $dctc_slug ); ?>_default_message" class="dctc-field-label">
									<?php esc_html_e( 'Default Greeting Message', 'dragwyb-click-to-chat' ); ?>
								</label>
								<input type="text"
									id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_default_message"
									name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_default_message"
									value="<?php echo esc_attr( $dctc_channel_data[ $dctc_slug ]['default_message'] ); ?>"
									placeholder="<?php esc_attr_e( 'Hi! How can I help you?', 'dragwyb-click-to-chat' ); ?>"
									class="dctc-modern-input">
							</div>
						</div>
					<?php endif; ?>

					<!-- Custom Icon Upload & Device Visibility Grid -->
					<div class="dctc-config-footer-grid">
						<!-- Custom Icon Upload -->
						<div class="dctc-icon-upload-col">
							<label class="dctc-field-label">
								<?php esc_html_e( 'Custom Icon (Optional)', 'dragwyb-click-to-chat' ); ?>
							</label>
							<div class="dctc-icon-upload-wrap">
								<button type="button" class="dctc-ai-btn dctc-ai-btn-secondary dctc-upload-channel-icon" data-target="<?php echo esc_attr( $dctc_slug ); ?>">
									<span class="dashicons dashicons-upload" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
									<?php esc_html_e( 'Choose Icon', 'dragwyb-click-to-chat' ); ?>
								</button>
								<input type="hidden" id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_custom_icon" name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_custom_icon" value="<?php echo esc_attr( $dctc_custom_icon ); ?>">

								<span class="dctc-icon-filename-<?php echo esc_attr( $dctc_slug ); ?> dctc-icon-filename-pill">
									<?php echo $dctc_custom_icon ? esc_html( basename( $dctc_custom_icon ) ) : esc_html__( 'Default Logo', 'dragwyb-click-to-chat' ); ?>
								</span>

								<button type="button" class="dctc-remove-channel-icon-btn dctc-remove-channel-icon" data-target="<?php echo esc_attr( $dctc_slug ); ?>" style="<?php echo $dctc_custom_icon ? 'display:inline-flex;' : 'display:none;'; ?>">
									<?php esc_html_e( 'Remove', 'dragwyb-click-to-chat' ); ?>
								</button>
							</div>
						</div>
						<!-- Device Visibility Settings -->
						<div class="dctc-device-visibility-col">
							<label class="dctc-field-label"><?php esc_html_e( 'Device Visibility', 'dragwyb-click-to-chat' ); ?></label>
							<div class="dctc-device-switches-row">
								<label class="dctc-device-switch-item">
									<label class="dctc-ios-switch">
										<input type="checkbox"
											id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_desktop"
											name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_desktop"
											value="1"
											<?php
											$dctc_d_val = $dctc_channel_data[ $dctc_slug ]['desktop'];
											checked( ( $dctc_d_val === false || $dctc_d_val === '1' ) );
											?>
											>
										<span class="dctc-ios-slider"></span>
									</label>
									<span class="dctc-switch-text"><span class="dashicons dashicons-desktop" style="font-size: 14px; width: 14px; height: 14px;"></span> <?php esc_html_e( 'Desktop', 'dragwyb-click-to-chat' ); ?></span>
								</label>

								<label class="dctc-device-switch-item">
									<label class="dctc-ios-switch">
										<input type="checkbox"
											id="dctc_<?php echo esc_attr( $dctc_slug ); ?>_mobile"
											name="dctc_<?php echo esc_attr( $dctc_slug ); ?>_mobile"
											value="1"
											<?php
											$dctc_m_val = $dctc_channel_data[ $dctc_slug ]['mobile'];
											checked( ( $dctc_m_val === false || $dctc_m_val === '1' ) );
											?>
											>
										<span class="dctc-ios-slider"></span>
									</label>
									<span class="dctc-switch-text"><span class="dashicons dashicons-smartphone" style="font-size: 14px; width: 14px; height: 14px;"></span> <?php esc_html_e( 'Mobile', 'dragwyb-click-to-chat' ); ?></span>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>

		<?php endforeach; ?>
	</div>
</div>
