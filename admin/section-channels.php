<?php

/**
 * Section 1: Select Channels (Dynamic Multi-Channel System)
 */

if (! defined('ABSPATH')) exit;

// Get all channels from registry
$dctc_all_channels = dctc_get_channels();

// Phase 1: Top 9 social channels (Live Chat is separate)
$dctc_phase_one_channels = array('whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin');

// Get saved channel data
// Get saved channel data
$dctc_settings = get_option('dctc_settings', array());
$dctc_channel_data = array();
foreach ($dctc_phase_one_channels as $dctc_slug) {
    $dctc_channel_data[$dctc_slug] = array(
        'enabled' => isset($dctc_settings[$dctc_slug . '_enabled']) ? $dctc_settings[$dctc_slug . '_enabled'] : '0',
        'value' => isset($dctc_settings[$dctc_slug . '_value']) ? $dctc_settings[$dctc_slug . '_value'] : '',
        'custom_icon' => isset($dctc_settings[$dctc_slug . '_custom_icon']) ? $dctc_settings[$dctc_slug . '_custom_icon'] : '',
        'desktop' => isset($dctc_settings[$dctc_slug . '_desktop']) ? $dctc_settings[$dctc_slug . '_desktop'] : '1',
        'mobile' => isset($dctc_settings[$dctc_slug . '_mobile']) ? $dctc_settings[$dctc_slug . '_mobile'] : '1',
    );
}
?>

<h2 class="dctc-section-title"><?php esc_html_e('Choose Your Channels', 'dragwyb-click-to-chat'); ?></h2>

<p style="color: #6b7280; margin-bottom: 30px;">
    <?php esc_html_e('Select which communication channels you want to display on your website.', 'dragwyb-click-to-chat'); ?>
</p>

<!-- Channel Selection Grid -->
<div class="dctc-channels-grid">
    <?php foreach ($dctc_phase_one_channels as $dctc_slug):
        $dctc_channel = $dctc_all_channels[$dctc_slug];
        $dctc_is_active = $dctc_channel_data[$dctc_slug]['enabled'] === '1';
    ?>
        <div class="dctc-channel-card <?php echo $dctc_is_active ? 'active' : ''; ?>" data-channel="<?php echo esc_attr($dctc_slug); ?>">
            <!-- Selection Toggle Switch -->
            <label class="dctc-card-switch">
                <input type="checkbox" class="dctc-card-checkbox" <?php checked($dctc_is_active); ?>>
                <span class="dctc-card-slider"></span>
            </label>

            <div class="dctc-channel-icon" style="background: <?php echo esc_attr($dctc_channel['color']); ?>;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="white">
                    <?php
                    $dctc_allowed_svg = array(
                        'path' => array('d' => array()),
                        'svg' => array('viewbox' => array(), 'fill' => array(), 'width' => array(), 'height' => array())
                    );
                    echo wp_kses($dctc_channel['icon'], $dctc_allowed_svg);
                    ?>
                </svg>
            </div>
            <div class="dctc-channel-name"><?php echo esc_html($dctc_channel['name']); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Channel Configuration Section -->
<div style="margin-top: 40px;">
    <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 20px;"><?php esc_html_e('Channel Configuration', 'dragwyb-click-to-chat'); ?></h3>

    <?php foreach ($dctc_phase_one_channels as $dctc_slug):
        $dctc_channel = $dctc_all_channels[$dctc_slug];
        $dctc_is_active = $dctc_channel_data[$dctc_slug]['enabled'] === '1';
        $dctc_value = $dctc_channel_data[$dctc_slug]['value'];
        $dctc_custom_icon = $dctc_channel_data[$dctc_slug]['custom_icon'];
    ?>

        <!-- <?php echo esc_html($dctc_channel['name']); ?> Settings -->
        <div class="dctc-form-group dctc-channel-config" data-channel-input="<?php echo esc_attr($dctc_slug); ?>" style="<?php echo !$dctc_is_active ? 'display:none;' : ''; ?>">
            <input type="hidden" name="dctc_<?php echo esc_attr($dctc_slug); ?>_enabled" id="dctc_<?php echo esc_attr($dctc_slug); ?>_enabled" value="<?php echo esc_attr($dctc_channel_data[$dctc_slug]['enabled']); ?>">

            <?php if ($dctc_channel['input_type'] === 'toggle'): ?>
                <!-- Toggle for Live Chat -->
                <label class="dctc-toggle">
                    <div class="dctc-switch">
                        <input type="checkbox"
                            id="dctc_<?php echo esc_attr($dctc_slug); ?>_value"
                            name="dctc_<?php echo esc_attr($dctc_slug); ?>_value"
                            value="1"
                            <?php checked($dctc_value, '1'); ?>>
                        <span class="dctc-slider"></span>
                    </div>
                    <span style="margin-left: 10px;"><?php echo esc_html($dctc_channel['label']); ?></span>
                </label>
                <p style="color: #9ca3af; font-size: 13px; margin-top: 8px;">
                    <?php esc_html_e('Enable a popup chat window where visitors can send you messages directly.', 'dragwyb-click-to-chat'); ?>
                </p>
            <?php else: ?>
                <!-- Input field for other channels -->
                <label for="dctc_<?php echo esc_attr($dctc_slug); ?>_value" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo esc_attr($dctc_channel['color']); ?>">
                        <?php echo wp_kses($dctc_channel['icon'], $dctc_allowed_svg); ?>
                    </svg>
                    <span style="font-weight: 500; color: #374151;"><?php echo esc_html($dctc_channel['label']); ?></span>
                </label>
                <input type="<?php echo esc_attr($dctc_channel['input_type']); ?>"
                    id="dctc_<?php echo esc_attr($dctc_slug); ?>_value"
                    name="dctc_<?php echo esc_attr($dctc_slug); ?>_value"
                    value="<?php echo esc_attr($dctc_value); ?>"
                    placeholder="<?php echo esc_attr($dctc_channel['placeholder']); ?>"
                    style="width: 100%; max-width: 400px; margin-bottom: 15px;">
            <?php endif; ?>

            <!-- Custom Icon Upload -->
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    <?php esc_html_e('Custom Icon (Optional)', 'dragwyb-click-to-chat'); ?>
                </label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="button button-secondary dctc-upload-channel-icon" data-target="<?php echo esc_attr($dctc_slug); ?>">
                        <?php esc_html_e('Choose Icon', 'dragwyb-click-to-chat'); ?>
                    </button>
                    <input type="hidden" id="dctc_<?php echo esc_attr($dctc_slug); ?>_custom_icon" name="dctc_<?php echo esc_attr($dctc_slug); ?>_custom_icon" value="<?php echo esc_attr($dctc_custom_icon); ?>">

                    <span class="dctc-icon-filename-<?php echo esc_attr($dctc_slug); ?>" style="font-size: 13px; color: #6b7280;">
                        <?php echo $dctc_custom_icon ? esc_html(basename($dctc_custom_icon)) : esc_html__('Default Icon', 'dragwyb-click-to-chat'); ?>
                    </span>

                    <?php if ($dctc_custom_icon): ?>
                        <button type="button" class="button button-secondary dctc-remove-channel-icon" data-target="<?php echo esc_attr($dctc_slug); ?>" style="color: #dc2626;"><?php esc_html_e('Remove', 'dragwyb-click-to-chat'); ?></button>
                    <?php else: ?>
                        <button type="button" class="button button-secondary dctc-remove-channel-icon" data-target="<?php echo esc_attr($dctc_slug); ?>" style="color: #dc2626; display: none;"><?php esc_html_e('Remove', 'dragwyb-click-to-chat'); ?></button>
                    <?php endif; ?>
                </div>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">
                    <?php esc_html_e('Upload an SVG or Image to replace the default logo.', 'dragwyb-click-to-chat'); ?>
                </p>
            </div>

            <!-- Device Visibility Settings -->
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 10px;"><?php esc_html_e('Device Visibility', 'dragwyb-click-to-chat'); ?></p>
                <div style="display: flex; gap: 20px;">
                    <label class="dctc-toggle">
                        <div class="dctc-switch">
                            <input type="checkbox"
                                id="dctc_<?php echo esc_attr($dctc_slug); ?>_desktop"
                                name="dctc_<?php echo esc_attr($dctc_slug); ?>_desktop"
                                value="1"
                                <?php
                                $dctc_d_val = $dctc_channel_data[$dctc_slug]['desktop'];
                                // Default to ON if missing (false) or explicitly '1'
                                checked(($dctc_d_val === false || $dctc_d_val === '1'));
                                ?>>
                            <span class="dctc-slider"></span>
                        </div>
                        <span style="margin-left: 10px; font-size: 14px;"><?php esc_html_e('Show on Desktop', 'dragwyb-click-to-chat'); ?></span>
                    </label>
                    <label class="dctc-toggle">
                        <div class="dctc-switch">
                            <input type="checkbox"
                                id="dctc_<?php echo esc_attr($dctc_slug); ?>_mobile"
                                name="dctc_<?php echo esc_attr($dctc_slug); ?>_mobile"
                                value="1"
                                <?php
                                $dctc_m_val = $dctc_channel_data[$dctc_slug]['mobile'];
                                // Default to ON if missing (false) or explicitly '1'
                                checked(($dctc_m_val === false || $dctc_m_val === '1'));
                                ?>>
                            <span class="dctc-slider"></span>
                        </div>
                        <span style="margin-left: 10px; font-size: 14px;"><?php esc_html_e('Show on Mobile', 'dragwyb-click-to-chat'); ?></span>
                    </label>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
</div>