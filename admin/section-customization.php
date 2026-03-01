<?php

/**
 * Section 2: Widget Customization
 */

if (! defined('ABSPATH')) exit;

// Get current widget settings
$dctc_settings = get_option('dctc_settings', array());

$dctc_widget_position = isset($dctc_settings['widget_position']) ? $dctc_settings['widget_position'] : 'right';
$dctc_widget_color = isset($dctc_settings['widget_color']) ? $dctc_settings['widget_color'] : '#8e44ad';
$dctc_widget_size = isset($dctc_settings['widget_size']) ? $dctc_settings['widget_size'] : '60';
$dctc_custom_bottom = isset($dctc_settings['custom_bottom']) ? $dctc_settings['custom_bottom'] : '20';
$dctc_custom_horizontal = isset($dctc_settings['custom_horizontal']) ? $dctc_settings['custom_horizontal'] : '20';
$dctc_custom_side = isset($dctc_settings['custom_side']) ? $dctc_settings['custom_side'] : 'right'; // 'left' or 'right'
?>

<h2 class="dctc-section-title">Customize Your Widget</h2>

<p style="color: #6b7280; margin-bottom: 30px;">
    Customize the appearance and position of your chat widget to match your website design.
</p>

<!-- Widget Position -->
<div class="dctc-form-group">
    <label for="dctc_greeting_message">Greeting Message</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Add a call-to-action message that appears next to the widget button
    </p>
    <?php $dctc_greeting_message = isset($dctc_settings['greeting_message']) ? $dctc_settings['greeting_message'] : ''; ?>
    <input type="text"
        id="dctc_greeting_message"
        name="dctc_greeting_message"
        value="<?php echo esc_attr($dctc_greeting_message); ?>"
        placeholder="e.g. Need Help? Chat with us!"
        style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
</div>

<!-- Widget Position -->
<div class="dctc-form-group">
    <label>Widget Position</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose where the chat widget appears on your website
    </p>
    <div class="dctc-position-selector">
        <label class="dctc-position-option <?php echo $dctc_widget_position === 'left' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_widget_position" value="left" <?php checked($dctc_widget_position, 'left'); ?>>
            <div class="position-card">
                <svg width="60" height="40" viewBox="0 0 60 40" fill="none">
                    <rect width="60" height="40" rx="4" fill="#F3F4F6" />
                    <circle cx="10" cy="30" r="5" fill="#8e44ad" />
                </svg>
                <span>Bottom Left</span>
            </div>
        </label>

        <label class="dctc-position-option <?php echo $dctc_widget_position === 'right' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_widget_position" value="right" <?php checked($dctc_widget_position, 'right'); ?>>
            <div class="position-card">
                <svg width="60" height="40" viewBox="0 0 60 40" fill="none">
                    <rect width="60" height="40" rx="4" fill="#F3F4F6" />
                    <circle cx="50" cy="30" r="5" fill="#8e44ad" />
                </svg>
                <span>Bottom Right</span>
            </div>
        </label>

        <label class="dctc-position-option <?php echo $dctc_widget_position === 'custom' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_widget_position" value="custom" <?php checked($dctc_widget_position, 'custom'); ?>>
            <div class="position-card">
                <svg width="60" height="40" viewBox="0 0 60 40" fill="none">
                    <rect width="60" height="40" rx="4" fill="#F3F4F6" />
                    <path d="M25 15 L35 15 L30 10 Z" fill="#8e44ad" />
                    <path d="M25 25 L35 25 L30 30 Z" fill="#8e44ad" />
                    <path d="M15 20 L20 25 L20 15 Z" fill="#8e44ad" />
                    <path d="M40 20 L35 25 L35 15 Z" fill="#8e44ad" />
                </svg>
                <span>Custom</span>
            </div>
        </label>
    </div>

    <!-- Custom Position Settings -->
    <div id="custom-position-settings" style="margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; <?php echo $dctc_widget_position !== 'custom' ? 'display: none;' : ''; ?>">
        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #374151;">Custom Position Settings</h4>

        <?php
        $dctc_custom_vertical_align = isset($dctc_settings['custom_vertical_align']) ? $dctc_settings['custom_vertical_align'] : 'bottom';
        $dctc_custom_bottom_unit = isset($dctc_settings['custom_bottom_unit']) ? $dctc_settings['custom_bottom_unit'] : 'px';
        $dctc_custom_horizontal_unit = isset($dctc_settings['custom_horizontal_unit']) ? $dctc_settings['custom_horizontal_unit'] : 'px';
        ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Vertical Axis -->
            <div>
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Vertical Alignment
                </label>
                <select id="dctc_custom_vertical_align" name="dctc_custom_vertical_align" style="width: 150px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 15px;">
                    <option value="bottom" <?php selected($dctc_custom_vertical_align, 'bottom'); ?>>Bottom</option>
                    <option value="top" <?php selected($dctc_custom_vertical_align, 'top'); ?>>Top</option>
                </select>

                <label for="dctc_custom_bottom" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Vertical Distance
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number"
                        id="dctc_custom_bottom"
                        name="dctc_custom_bottom"
                        value="<?php echo esc_attr($dctc_custom_bottom); ?>"
                        min="0"
                        step="0.1"
                        style="width: 80px;">
                    <select id="dctc_custom_bottom_unit" name="dctc_custom_bottom_unit" style="padding: 0 5px; height: 30px; width: 45px">
                        <option value="px" <?php selected($dctc_custom_bottom_unit, 'px'); ?>>px</option>
                        <option value="rem" <?php selected($dctc_custom_bottom_unit, 'rem'); ?>>rem</option>
                        <option value="em" <?php selected($dctc_custom_bottom_unit, 'em'); ?>>em</option>
                        <option value="%" <?php selected($dctc_custom_bottom_unit, '%'); ?>>%</option>
                    </select>
                </div>
            </div>

            <!-- Horizontal Axis -->
            <div>
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Horizontal Alignment
                </label>
                <select id="dctc_custom_side" name="dctc_custom_side" style="width: 150px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 15px;">
                    <option value="right" <?php selected($dctc_custom_side, 'right'); ?>>Right</option>
                    <option value="left" <?php selected($dctc_custom_side, 'left'); ?>>Left</option>
                </select>

                <label for="dctc_custom_horizontal" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Horizontal Distance
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number"
                        id="dctc_custom_horizontal"
                        name="dctc_custom_horizontal"
                        value="<?php echo esc_attr($dctc_custom_horizontal); ?>"
                        min="0"
                        step="0.1"
                        style="width: 80px;">
                    <select id="dctc_custom_horizontal_unit" name="dctc_custom_horizontal_unit" style="padding: 0 5px; height: 30px; width: 45px">
                        <option value="px" <?php selected($dctc_custom_horizontal_unit, 'px'); ?>>px</option>
                        <option value="rem" <?php selected($dctc_custom_horizontal_unit, 'rem'); ?>>rem</option>
                        <option value="em" <?php selected($dctc_custom_horizontal_unit, 'em'); ?>>em</option>
                        <option value="%" <?php selected($dctc_custom_horizontal_unit, '%'); ?>>%</option>
                    </select>
                </div>
            </div>
        </div>

        <p style="margin: 15px 0 0 0; font-size: 12px; color: #9ca3af;">
            💡 Tip: Use custom positioning to place the widget exactly where you want it on your page.
        </p>
    </div>
</div>

<!-- Widget Color -->
<div class="dctc-form-group">
    <label for="dctc_widget_color">Widget Color</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose a color that matches your brand
    </p>
    <div style="display: flex; align-items: center; gap: 15px;">
        <input type="text"
            id="dctc_widget_color"
            name="dctc_widget_color"
            value="<?php echo esc_attr($dctc_widget_color); ?>"
            class="dctc-color-picker"
            data-default-color="#8e44ad">
        <span class="dctc-color-preview" style="width: 40px; height: 40px; border-radius: 6px; border: 2px solid #e5e7eb; background-color: <?php echo esc_attr($dctc_widget_color); ?>;"></span>
    </div>
</div>

<!-- Widget Size -->
<?php $dctc_widget_size_unit = isset($dctc_settings['widget_size_unit']) ? $dctc_settings['widget_size_unit'] : 'px'; ?>
<div class="dctc-form-group">
    <label for="dctc_widget_size">Widget Size</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Adjust the size of the chat button
    </p>
    <div style="display: flex; align-items: center; gap: 15px;">
        <input type="number"
            id="dctc_widget_size"
            name="dctc_widget_size"
            min="10"
            step="0.1"
            value="<?php echo esc_attr($dctc_widget_size); ?>"
            style="width: 80px;">

        <select id="dctc_widget_size_unit" name="dctc_widget_size_unit" style="padding: 0 5px; height: 30px; width: 45px">
            <option value="px" <?php selected($dctc_widget_size_unit, 'px'); ?>>px</option>
            <option value="rem" <?php selected($dctc_widget_size_unit, 'rem'); ?>>rem</option>
            <option value="em" <?php selected($dctc_widget_size_unit, 'em'); ?>>em</option>
            <option value="%" <?php selected($dctc_widget_size_unit, '%'); ?>>%</option>
        </select>
    </div>
</div>

<?php
// Get icon settings
$dctc_icon_type = isset($dctc_settings['icon_type']) ? $dctc_settings['icon_type'] : 'chat';
$dctc_custom_icon_url = isset($dctc_settings['custom_icon_url']) ? $dctc_settings['custom_icon_url'] : '';
$dctc_icon_rotation = isset($dctc_settings['icon_rotation']) ? $dctc_settings['icon_rotation'] : '0';
$dctc_icon_scale = isset($dctc_settings['icon_scale']) ? $dctc_settings['icon_scale'] : '1';
?>

<!-- Widget Icon -->
<div class="dctc-form-group">
    <label>Widget Icon</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose an icon for your chat button
    </p>

    <!-- Icon Type Selector -->
    <div class="dctc-icon-selector" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <label class="dctc-icon-option <?php echo $dctc_icon_type === 'chat' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_icon_type" value="chat" <?php checked($dctc_icon_type, 'chat'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                </svg>
                <span>Chat</span>
            </div>
        </label>

        <label class="dctc-icon-option <?php echo $dctc_icon_type === 'message' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_icon_type" value="message" <?php checked($dctc_icon_type, 'message'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
                <span>Message</span>
            </div>
        </label>

        <label class="dctc-icon-option <?php echo $dctc_icon_type === 'support' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_icon_type" value="support" <?php checked($dctc_icon_type, 'support'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                </svg>
                <span>Support</span>
            </div>
        </label>

        <label class="dctc-icon-option <?php echo $dctc_icon_type === 'phone' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_icon_type" value="phone" <?php checked($dctc_icon_type, 'phone'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                </svg>
                <span>Phone</span>
            </div>
        </label>

        <label class="dctc-icon-option <?php echo $dctc_icon_type === 'custom' ? 'active' : ''; ?>">
            <input type="radio" name="dctc_icon_type" value="custom" <?php checked($dctc_icon_type, 'custom'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                </svg>
                <span>Custom</span>
            </div>
        </label>
    </div>

    <!-- Custom Icon Upload -->
    <div id="custom-icon-upload" style="margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; <?php echo $dctc_icon_type !== 'custom' ? 'display: none;' : ''; ?>">
        <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
            Upload Custom Icon (SVG recommended)
        </label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" id="upload-icon-button" class="button button-secondary">
                Choose Icon
            </button>
            <input type="hidden" id="dctc_custom_icon_url" name="dctc_custom_icon_url" value="<?php echo esc_attr($dctc_custom_icon_url); ?>">
            <span id="icon-filename" style="font-size: 13px; color: #6b7280;">
                <?php echo $dctc_custom_icon_url ? esc_html(basename($dctc_custom_icon_url)) : 'No icon selected'; ?>
            </span>
            <?php if ($dctc_custom_icon_url): ?>
                <button type="button" id="remove-icon-button" class="button button-secondary" style="color: #dc2626;">Remove</button>
            <?php endif; ?>
        </div>
        <p style="margin: 10px 0 0 0; font-size: 12px; color: #9ca3af;">
            💡 For best results, use an SVG file. PNG and JPG are also supported.
        </p>
    </div>
</div>

<!-- Icon Style Settings -->
<div class="dctc-form-group">
    <label>Icon Style</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Adjust the appearance of your icon
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Icon Rotation -->
        <div>
            <label for="dctc_icon_rotation" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                Rotation
            </label>
            <div style="display: flex; align-items: center; gap: 15px;">
                <input type="range"
                    id="dctc_icon_rotation"
                    name="dctc_icon_rotation"
                    min="0"
                    max="360"
                    value="<?php echo esc_attr($dctc_icon_rotation); ?>"
                    class="dctc-size-slider"
                    style="flex: 1;">
                <span class="dctc-rotation-value" style="min-width: 45px; font-weight: 600; color: #374151;">
                    <?php echo esc_html($dctc_icon_rotation); ?>°
                </span>
            </div>
        </div>

        <!-- Icon Scale -->
        <div>
            <label for="dctc_icon_scale" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                Size Multiplier
            </label>
            <div style="display: flex; align-items: center; gap: 15px;">
                <input type="range"
                    id="dctc_icon_scale"
                    name="dctc_icon_scale"
                    min="0.5"
                    max="2"
                    step="0.1"
                    value="<?php echo esc_attr($dctc_icon_scale); ?>"
                    class="dctc-size-slider"
                    style="flex: 1;">
                <span class="dctc-scale-value" style="min-width: 50px; font-weight: 600; color: #374151;">
                    <?php echo esc_html($dctc_icon_scale); ?>x
                </span>
            </div>
        </div>
    </div>
</div>