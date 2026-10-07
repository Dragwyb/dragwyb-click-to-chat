<?php

/**
 * Section 2: Widget Customization
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current widget settings
$dctc_settings = get_option( 'dctc_settings', array() );

$dctc_widget_position   = isset( $dctc_settings['widget_position'] ) ? $dctc_settings['widget_position'] : 'right';
$dctc_widget_color      = isset( $dctc_settings['widget_color'] ) ? $dctc_settings['widget_color'] : '#4f46e5';
$dctc_widget_size       = isset( $dctc_settings['widget_size'] ) ? $dctc_settings['widget_size'] : '60';
$dctc_custom_bottom     = isset( $dctc_settings['custom_bottom'] ) ? $dctc_settings['custom_bottom'] : '20';
$dctc_custom_horizontal = isset( $dctc_settings['custom_horizontal'] ) ? $dctc_settings['custom_horizontal'] : '20';
$dctc_custom_side       = isset( $dctc_settings['custom_side'] ) ? $dctc_settings['custom_side'] : 'right'; // 'left' or 'right'
$dctc_greeting_message  = isset( $dctc_settings['greeting_message'] ) ? $dctc_settings['greeting_message'] : '';
$dctc_show_widget       = isset( $dctc_settings['show_widget'] ) ? $dctc_settings['show_widget'] : '1';
$dctc_widget_size_unit  = isset( $dctc_settings['widget_size_unit'] ) ? $dctc_settings['widget_size_unit'] : 'px';

// Icon settings
$dctc_icon_type         = isset( $dctc_settings['icon_type'] ) ? $dctc_settings['icon_type'] : 'chat';
$dctc_custom_icon_url   = isset( $dctc_settings['custom_icon_url'] ) ? $dctc_settings['custom_icon_url'] : '';
$dctc_icon_rotation     = isset( $dctc_settings['icon_rotation'] ) ? $dctc_settings['icon_rotation'] : '0';
$dctc_icon_scale        = isset( $dctc_settings['icon_scale'] ) ? $dctc_settings['icon_scale'] : '1';
?>

<!-- Card 1: Call-To-Action Badge & Display Mode -->
<div class="dctc-ai-card dctc-section-card">
	<div class="dctc-ai-card__header">
		<div class="dctc-ai-card__header-left">
			<div class="dctc-ai-card-icon" style="background: #eef2ff; color: #4f46e5;">
				<span class="dashicons dashicons-format-status" style="font-size: 16px; width: 16px; height: 16px;"></span>
			</div>
			<div>
				<h2 class="dctc-card-title"><?php esc_html_e( 'Button Call-To-Action & Display Mode', 'dragwyb-click-to-chat' ); ?></h2>
				<p class="dctc-card-subtitle"><?php esc_html_e( 'Configure the greeting callout pill and floating launcher behavior.', 'dragwyb-click-to-chat' ); ?></p>
			</div>
		</div>
	</div>

	<div class="dctc-card-body">
		<!-- Greeting Call-To-Action Message -->
		<div class="dctc-field-group">
			<label for="dctc_greeting_message" class="dctc-field-label">
				<?php esc_html_e( 'Button Call-To-Action Text Badge (Optional)', 'dragwyb-click-to-chat' ); ?>
			</label>
			<input type="text"
				id="dctc_greeting_message"
				name="dctc_greeting_message"
				value="<?php echo esc_attr( $dctc_greeting_message ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. Chat with us', 'dragwyb-click-to-chat' ); ?>"
				class="dctc-modern-input">
			<p class="dctc-field-hint">
				<?php esc_html_e( 'Small text pill displayed adjacent to the circular floating button.', 'dragwyb-click-to-chat' ); ?>
			</p>
		</div>

		<!-- Show Floating Button Switcher -->
		<div class="dctc-toggle-row" id="dctc-show-widget-setting" style="margin-top: 18px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
			<label class="dctc-ios-switch">
				<input type="checkbox"
					id="dctc_show_widget"
					name="dctc_show_widget"
					value="1"
					<?php checked( $dctc_show_widget, '1' ); ?>>
				<span class="dctc-ios-slider"></span>
			</label>
			<div>
				<span class="dctc-toggle-label"><?php esc_html_e( 'Show Single Expandable Floating Button', 'dragwyb-click-to-chat' ); ?></span>
				<p class="dctc-field-hint">
					<?php esc_html_e( 'When 2 or more channels are enabled, show a master floating button that expands into individual channels when clicked.', 'dragwyb-click-to-chat' ); ?>
				</p>
				<p id="dctc-show-widget-single-hint" style="display: none; color: #6366f1; font-size: 12px; margin: 6px 0 0 0; font-weight: 500;">
					ℹ️ <?php esc_html_e( 'Only one channel is currently active, so that channel button will be displayed directly.', 'dragwyb-click-to-chat' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>

<!-- Card 2: Widget Screen Position -->
<div class="dctc-ai-card dctc-section-card" style="margin-top: 20px;">
	<div class="dctc-ai-card__header">
		<div class="dctc-ai-card__header-left">
			<div class="dctc-ai-card-icon" style="background: #fdf2f8; color: #db2777;">
				<span class="dashicons dashicons-move" style="font-size: 16px; width: 16px; height: 16px;"></span>
			</div>
			<div>
				<h3 class="dctc-card-title"><?php esc_html_e( 'Widget Screen Position', 'dragwyb-click-to-chat' ); ?></h3>
				<p class="dctc-card-subtitle"><?php esc_html_e( 'Choose screen corner placement or fine-tune exact pixel coordinates.', 'dragwyb-click-to-chat' ); ?></p>
			</div>
		</div>
	</div>

	<div class="dctc-card-body">
		<!-- Position Selector Grid -->
		<div class="dctc-position-selector">
			<label class="dctc-position-option <?php echo $dctc_widget_position === 'left' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_widget_position" value="left" <?php checked( $dctc_widget_position, 'left' ); ?>>
				<div class="position-card">
					<svg width="64" height="42" viewBox="0 0 60 40" fill="none">
						<rect width="60" height="40" rx="6" fill="#F1F5F9" stroke="#E2E8F0" stroke-width="1" />
						<circle cx="12" cy="28" r="6" fill="#4F46E5" />
					</svg>
					<span><?php esc_html_e( 'Bottom Left', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>

			<label class="dctc-position-option <?php echo $dctc_widget_position === 'right' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_widget_position" value="right" <?php checked( $dctc_widget_position, 'right' ); ?>>
				<div class="position-card">
					<svg width="64" height="42" viewBox="0 0 60 40" fill="none">
						<rect width="60" height="40" rx="6" fill="#F1F5F9" stroke="#E2E8F0" stroke-width="1" />
						<circle cx="48" cy="28" r="6" fill="#4F46E5" />
					</svg>
					<span><?php esc_html_e( 'Bottom Right', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>

			<label class="dctc-position-option <?php echo $dctc_widget_position === 'custom' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_widget_position" value="custom" <?php checked( $dctc_widget_position, 'custom' ); ?>>
				<div class="position-card">
					<svg width="64" height="42" viewBox="0 0 60 40" fill="none">
						<rect width="60" height="40" rx="6" fill="#F1F5F9" stroke="#E2E8F0" stroke-width="1" />
						<path d="M26 14 L34 14 L30 10 Z" fill="#4F46E5" />
						<path d="M26 26 L34 26 L30 30 Z" fill="#4F46E5" />
						<path d="M14 20 L18 24 L18 16 Z" fill="#4F46E5" />
						<path d="M46 20 L42 24 L42 16 Z" fill="#4F46E5" />
					</svg>
					<span><?php esc_html_e( 'Custom Position', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>
		</div>

		<!-- Custom Position Drawer -->
		<div id="custom-position-settings" class="dctc-drawer-card" style="<?php echo $dctc_widget_position !== 'custom' ? 'display: none;' : ''; ?> margin-top: 18px;">
			<h4 class="dctc-drawer-card-title"><?php esc_html_e( 'Custom Offset Settings', 'dragwyb-click-to-chat' ); ?></h4>

			<?php
			$dctc_custom_vertical_align  = isset( $dctc_settings['custom_vertical_align'] ) ? $dctc_settings['custom_vertical_align'] : 'bottom';
			$dctc_custom_bottom_unit     = isset( $dctc_settings['custom_bottom_unit'] ) ? $dctc_settings['custom_bottom_unit'] : 'px';
			$dctc_custom_horizontal_unit = isset( $dctc_settings['custom_horizontal_unit'] ) ? $dctc_settings['custom_horizontal_unit'] : 'px';
			?>

			<div class="dctc-grid-2-col">
				<!-- Vertical Axis -->
				<div class="dctc-field-group">
					<label for="dctc_custom_vertical_align" class="dctc-field-label">
						<?php esc_html_e( 'Vertical Alignment', 'dragwyb-click-to-chat' ); ?>
					</label>
					<select id="dctc_custom_vertical_align" name="dctc_custom_vertical_align" class="dctc-modern-select" style="margin-bottom: 12px;">
						<option value="bottom" <?php selected( $dctc_custom_vertical_align, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'dragwyb-click-to-chat' ); ?></option>
						<option value="top" <?php selected( $dctc_custom_vertical_align, 'top' ); ?>><?php esc_html_e( 'Top', 'dragwyb-click-to-chat' ); ?></option>
					</select>

					<label for="dctc_custom_bottom" class="dctc-field-label">
						<?php esc_html_e( 'Vertical Distance', 'dragwyb-click-to-chat' ); ?>
					</label>
					<div class="dctc-input-unit-wrap">
						<input type="number"
							id="dctc_custom_bottom"
							name="dctc_custom_bottom"
							value="<?php echo esc_attr( $dctc_custom_bottom ); ?>"
							min="0"
							step="0.1"
							class="dctc-modern-input" style="width: 100px;">
						<select id="dctc_custom_bottom_unit" name="dctc_custom_bottom_unit" class="dctc-modern-select" style="width: 70px;">
							<option value="px" <?php selected( $dctc_custom_bottom_unit, 'px' ); ?>>px</option>
							<option value="rem" <?php selected( $dctc_custom_bottom_unit, 'rem' ); ?>>rem</option>
							<option value="em" <?php selected( $dctc_custom_bottom_unit, 'em' ); ?>>em</option>
							<option value="%" <?php selected( $dctc_custom_bottom_unit, '%' ); ?>>%</option>
						</select>
					</div>
				</div>

				<!-- Horizontal Axis -->
				<div class="dctc-field-group">
					<label for="dctc_custom_side" class="dctc-field-label">
						<?php esc_html_e( 'Horizontal Alignment', 'dragwyb-click-to-chat' ); ?>
					</label>
					<select id="dctc_custom_side" name="dctc_custom_side" class="dctc-modern-select" style="margin-bottom: 12px;">
						<option value="right" <?php selected( $dctc_custom_side, 'right' ); ?>><?php esc_html_e( 'Right', 'dragwyb-click-to-chat' ); ?></option>
						<option value="left" <?php selected( $dctc_custom_side, 'left' ); ?>><?php esc_html_e( 'Left', 'dragwyb-click-to-chat' ); ?></option>
					</select>

					<label for="dctc_custom_horizontal" class="dctc-field-label">
						<?php esc_html_e( 'Horizontal Distance', 'dragwyb-click-to-chat' ); ?>
					</label>
					<div class="dctc-input-unit-wrap">
						<input type="number"
							id="dctc_custom_horizontal"
							name="dctc_custom_horizontal"
							value="<?php echo esc_attr( $dctc_custom_horizontal ); ?>"
							min="0"
							step="0.1"
							class="dctc-modern-input" style="width: 100px;">
						<select id="dctc_custom_horizontal_unit" name="dctc_custom_horizontal_unit" class="dctc-modern-select" style="width: 70px;">
							<option value="px" <?php selected( $dctc_custom_horizontal_unit, 'px' ); ?>>px</option>
							<option value="rem" <?php selected( $dctc_custom_horizontal_unit, 'rem' ); ?>>rem</option>
							<option value="em" <?php selected( $dctc_custom_horizontal_unit, 'em' ); ?>>em</option>
							<option value="%" <?php selected( $dctc_custom_horizontal_unit, '%' ); ?>>%</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Card 3: Colors & Sizing -->
<div class="dctc-ai-card dctc-section-card" style="margin-top: 20px;">
	<div class="dctc-ai-card__header">
		<div class="dctc-ai-card__header-left">
			<div class="dctc-ai-card-icon" style="background: #f0fdf4; color: #16a34a;">
				<span class="dashicons dashicons-art" style="font-size: 16px; width: 16px; height: 16px;"></span>
			</div>
			<div>
				<h3 class="dctc-card-title"><?php esc_html_e( 'Color, Brand & Dimensions', 'dragwyb-click-to-chat' ); ?></h3>
				<p class="dctc-card-subtitle"><?php esc_html_e( 'Customize button colors and scale dimensions.', 'dragwyb-click-to-chat' ); ?></p>
			</div>
		</div>
	</div>

	<div class="dctc-card-body">
		<div class="dctc-grid-2-col">
			<!-- Widget Color -->
			<div class="dctc-field-group">
				<label for="dctc_widget_color" class="dctc-field-label"><?php esc_html_e( 'Widget Brand Color', 'dragwyb-click-to-chat' ); ?></label>
				<p class="dctc-field-hint"><?php esc_html_e( 'Choose primary accent color for the floating launcher button.', 'dragwyb-click-to-chat' ); ?></p>
				<div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
					<input type="text"
						id="dctc_widget_color"
						name="dctc_widget_color"
						value="<?php echo esc_attr( $dctc_widget_color ); ?>"
						class="dctc-color-picker"
						data-default-color="#4f46e5">
					<span class="dctc-color-preview" style="width: 36px; height: 36px; border-radius: 8px; border: 2px solid #e2e8f0; background-color: <?php echo esc_attr( $dctc_widget_color ); ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>
				</div>
			</div>

			<!-- Widget Size -->
			<div class="dctc-field-group">
				<label for="dctc_widget_size" class="dctc-field-label"><?php esc_html_e( 'Widget Launcher Size', 'dragwyb-click-to-chat' ); ?></label>
				<p class="dctc-field-hint"><?php esc_html_e( 'Adjust the width and height diameter of the round launcher.', 'dragwyb-click-to-chat' ); ?></p>
				<div class="dctc-input-unit-wrap" style="margin-top: 8px;">
					<input type="number"
						id="dctc_widget_size"
						name="dctc_widget_size"
						min="10"
						step="0.1"
						value="<?php echo esc_attr( $dctc_widget_size ); ?>"
						class="dctc-modern-input" style="width: 100px;">

					<select id="dctc_widget_size_unit" name="dctc_widget_size_unit" class="dctc-modern-select" style="width: 70px;">
						<option value="px" <?php selected( $dctc_widget_size_unit, 'px' ); ?>>px</option>
						<option value="rem" <?php selected( $dctc_widget_size_unit, 'rem' ); ?>>rem</option>
						<option value="em" <?php selected( $dctc_widget_size_unit, 'em' ); ?>>em</option>
						<option value="%" <?php selected( $dctc_widget_size_unit, '%' ); ?>>%</option>
					</select>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Card 4: Icon & Transformation -->
<div class="dctc-ai-card dctc-section-card" style="margin-top: 20px;">
	<div class="dctc-ai-card__header">
		<div class="dctc-ai-card__header-left">
			<div class="dctc-ai-card-icon" style="background: #fffbeb; color: #d97706;">
				<span class="dashicons dashicons-format-chat" style="font-size: 16px; width: 16px; height: 16px;"></span>
			</div>
			<div>
				<h3 class="dctc-card-title"><?php esc_html_e( 'Widget Icon & Transformation', 'dragwyb-click-to-chat' ); ?></h3>
				<p class="dctc-card-subtitle"><?php esc_html_e( 'Select launcher icon symbol or upload your brand logo.', 'dragwyb-click-to-chat' ); ?></p>
			</div>
		</div>
	</div>

	<div class="dctc-card-body">
		<!-- Icon Selector Grid -->
		<label class="dctc-field-label" style="margin-bottom: 12px; display: block;"><?php esc_html_e( 'Launcher Icon Symbol', 'dragwyb-click-to-chat' ); ?></label>
		<div class="dctc-icon-selector">
			<label class="dctc-icon-option <?php echo $dctc_icon_type === 'chat' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_icon_type" value="chat" <?php checked( $dctc_icon_type, 'chat' ); ?>>
				<div class="icon-card">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
						<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
					</svg>
					<span><?php esc_html_e( 'Chat', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>

			<label class="dctc-icon-option <?php echo $dctc_icon_type === 'message' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_icon_type" value="message" <?php checked( $dctc_icon_type, 'message' ); ?>>
				<div class="icon-card">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
						<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
					</svg>
					<span><?php esc_html_e( 'Message', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>

			<label class="dctc-icon-option <?php echo $dctc_icon_type === 'support' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_icon_type" value="support" <?php checked( $dctc_icon_type, 'support' ); ?>>
				<div class="icon-card">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
					</svg>
					<span><?php esc_html_e( 'Support', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>

			<label class="dctc-icon-option <?php echo $dctc_icon_type === 'phone' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_icon_type" value="phone" <?php checked( $dctc_icon_type, 'phone' ); ?>>
				<div class="icon-card">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
						<path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
					</svg>
					<span><?php esc_html_e( 'Phone', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>

			<label class="dctc-icon-option <?php echo $dctc_icon_type === 'custom' ? 'active' : ''; ?>">
				<input type="radio" name="dctc_icon_type" value="custom" <?php checked( $dctc_icon_type, 'custom' ); ?>>
				<div class="icon-card">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
						<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
					</svg>
					<span><?php esc_html_e( 'Custom', 'dragwyb-click-to-chat' ); ?></span>
				</div>
			</label>
		</div>

		<!-- Custom Icon Upload Box -->
		<div id="custom-icon-upload" class="dctc-drawer-card" style="<?php echo $dctc_icon_type !== 'custom' ? 'display: none;' : ''; ?> margin-top: 18px;">
			<label class="dctc-field-label">
				<?php esc_html_e( 'Upload Custom Launcher Icon (SVG, PNG, JPG)', 'dragwyb-click-to-chat' ); ?>
			</label>
			<div class="dctc-icon-upload-wrap" style="margin-top: 8px;">
				<button type="button" id="upload-icon-button" class="dctc-ai-btn dctc-ai-btn-secondary">
					<span class="dashicons dashicons-upload" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
					<?php esc_html_e( 'Choose Icon', 'dragwyb-click-to-chat' ); ?>
				</button>
				<input type="hidden" id="dctc_custom_icon_url" name="dctc_custom_icon_url" value="<?php echo esc_attr( $dctc_custom_icon_url ); ?>">
				<span id="icon-filename" class="dctc-icon-filename-pill">
					<?php echo $dctc_custom_icon_url ? esc_html( basename( $dctc_custom_icon_url ) ) : esc_html__( 'No icon selected', 'dragwyb-click-to-chat' ); ?>
				</span>
				<?php if ( $dctc_custom_icon_url ) : ?>
					<button type="button" id="remove-icon-button" class="dctc-remove-channel-icon-btn"><?php esc_html_e( 'Remove', 'dragwyb-click-to-chat' ); ?></button>
				<?php endif; ?>
			</div>
		</div>

		<!-- Icon Style Settings (Rotation & Scale) -->
		<div class="dctc-grid-2-col" style="margin-top: 20px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
			<!-- Icon Rotation -->
			<div class="dctc-field-group">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<label for="dctc_icon_rotation" class="dctc-field-label"><?php esc_html_e( 'Icon Rotation', 'dragwyb-click-to-chat' ); ?></label>
					<span class="dctc-rotation-value dctc-slider-badge"><?php echo esc_html( $dctc_icon_rotation ); ?>°</span>
				</div>
				<input type="range"
					id="dctc_icon_rotation"
					name="dctc_icon_rotation"
					min="0"
					max="360"
					value="<?php echo esc_attr( $dctc_icon_rotation ); ?>"
					class="dctc-size-slider"
					style="width: 100%;">
			</div>

			<!-- Icon Scale -->
			<div class="dctc-field-group">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<label for="dctc_icon_scale" class="dctc-field-label"><?php esc_html_e( 'Size Multiplier', 'dragwyb-click-to-chat' ); ?></label>
					<span class="dctc-scale-value dctc-slider-badge"><?php echo esc_html( $dctc_icon_scale ); ?>x</span>
				</div>
				<input type="range"
					id="dctc_icon_scale"
					name="dctc_icon_scale"
					min="0.5"
					max="2"
					step="0.1"
					value="<?php echo esc_attr( $dctc_icon_scale ); ?>"
					class="dctc-size-slider"
					style="width: 100%;">
			</div>
		</div>
	</div>
</div>
