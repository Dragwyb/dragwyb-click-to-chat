<?php

/**
 * Section 3: Triggers & Targeting
 */

if (! defined('ABSPATH')) {
    exit;
}

// $dctc_settings is available from admin-main.php
if (! isset($dctc_settings)) {
    $dctc_settings = get_option('dctc_settings', array());
}

$dctc_show_on_desktop = isset($dctc_settings['show_on_desktop']) ? $dctc_settings['show_on_desktop'] : '1';
$dctc_show_on_mobile  = isset($dctc_settings['show_on_mobile']) ? $dctc_settings['show_on_mobile'] : '1';
$dctc_time_delay      = isset($dctc_settings['time_delay']) ? $dctc_settings['time_delay'] : '0';

// Display rules
$dctc_display_mode       = isset($dctc_settings['display_mode']) ? $dctc_settings['display_mode'] : 'all';
$dctc_display_post_types = isset($dctc_settings['display_post_types']) ? $dctc_settings['display_post_types'] : array();

// Get public post types (exclude attachment)
$dctc_post_types = get_post_types(array('public' => true), 'objects');
if (isset($dctc_post_types['attachment'])) {
    unset($dctc_post_types['attachment']);
}
?>

<!-- Card 1: Display Rules -->
<div class="dctc-ai-card dctc-section-card">
    <div class="dctc-ai-card__header">
        <div class="dctc-ai-card__header-left">
            <div class="dctc-ai-card-icon" style="background: #eef2ff; color: #4f46e5;">
                <span class="dashicons dashicons-admin-site-alt3" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
            <div>
                <h2 class="dctc-card-title"><?php esc_html_e('Display Rules & Site Scope', 'dragwyb-click-to-chat'); ?></h2>
                <p class="dctc-card-subtitle"><?php esc_html_e('Choose where the chat widget should appear across your public site content.', 'dragwyb-click-to-chat'); ?></p>
            </div>
        </div>
    </div>

    <div class="dctc-card-body">
        <div class="dctc-radio-cards-group">
            <!-- All Pages -->
            <label class="dctc-radio-card <?php echo $dctc_display_mode === 'all' ? 'is-selected' : ''; ?>">
                <input type="radio"
                    name="dctc_display_mode"
                    value="all"
                    <?php checked($dctc_display_mode, 'all'); ?>>
                <div class="dctc-radio-card-content">
                    <span class="dctc-radio-card-title">🌐 <?php esc_html_e('All Pages (Site-Wide)', 'dragwyb-click-to-chat'); ?></span>
                    <span class="dctc-radio-card-desc"><?php esc_html_e('Show the floating channels widget on every public page and post of your website.', 'dragwyb-click-to-chat'); ?></span>
                </div>
            </label>

            <!-- Specific Post Types -->
            <label class="dctc-radio-card <?php echo $dctc_display_mode === 'post_types' ? 'is-selected' : ''; ?>">
                <input type="radio"
                    name="dctc_display_mode"
                    value="post_types"
                    <?php checked($dctc_display_mode, 'post_types'); ?>>
                <div class="dctc-radio-card-content">
                    <span class="dctc-radio-card-title">📑 <?php esc_html_e('Specific Post Types Only', 'dragwyb-click-to-chat'); ?></span>
                    <span class="dctc-radio-card-desc"><?php esc_html_e('Limit widget visibility to selected custom post types or specific content.', 'dragwyb-click-to-chat'); ?></span>
                </div>
            </label>
        </div>

        <!-- Post Types List Drawer -->
        <div id="dctc-post-types-list"
            class="dctc-drawer-card"
            style="margin-top: 15px; display:<?php echo $dctc_display_mode === 'post_types' ? 'block' : 'none'; ?>;">
            <p class="dctc-field-label" style="margin-bottom: 10px;"><?php esc_html_e('Select Allowed Post Types:', 'dragwyb-click-to-chat'); ?></p>
            <div class="dctc-checkboxes-grid">
                <?php foreach ($dctc_post_types as $dctc_pt_slug => $dctc_pt_obj) : ?>
                    <label class="dctc-checkbox-pill">
                        <input type="checkbox"
                            name="dctc_display_post_types[]"
                            value="<?php echo esc_attr($dctc_pt_slug); ?>"
                            <?php checked(in_array($dctc_pt_slug, $dctc_display_post_types, true)); ?>>
                        <span><?php echo esc_html($dctc_pt_obj->labels->name); ?> <small style="color:#94a3b8;">(<?php echo esc_html($dctc_pt_slug); ?>)</small></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Card 2: Device Visibility -->
<div class="dctc-ai-card dctc-section-card" style="margin-top: 20px;">
    <div class="dctc-ai-card__header">
        <div class="dctc-ai-card__header-left">
            <div class="dctc-ai-card-icon" style="background: #fdf2f8; color: #db2777;">
                <span class="dashicons dashicons-smartphone" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
            <div>
                <h3 class="dctc-card-title"><?php esc_html_e('Device Target Visibility', 'dragwyb-click-to-chat'); ?></h3>
                <p class="dctc-card-subtitle"><?php esc_html_e('Independently toggle widget visibility for desktop computers and mobile devices.', 'dragwyb-click-to-chat'); ?></p>
            </div>
        </div>
    </div>

    <div class="dctc-card-body">
        <div class="dctc-grid-2-col">
            <!-- Desktop -->
            <div class="dctc-toggle-row">
                <label class="dctc-ios-switch">
                    <input type="checkbox"
                        id="dctc_show_on_desktop"
                        name="dctc_show_on_desktop"
                        value="1"
                        <?php checked($dctc_show_on_desktop, '1'); ?>>
                    <span class="dctc-ios-slider"></span>
                </label>
                <div>
                    <span class="dctc-toggle-label"><span class="dashicons dashicons-desktop" style="font-size: 16px; width: 16px; height: 16px;"></span> <?php esc_html_e('Show on Desktop', 'dragwyb-click-to-chat'); ?></span>
                    <p class="dctc-field-hint"><?php esc_html_e('Display widget on laptops and desktop screens.', 'dragwyb-click-to-chat'); ?></p>
                </div>
            </div>

            <!-- Mobile -->
            <div class="dctc-toggle-row">
                <label class="dctc-ios-switch">
                    <input type="checkbox"
                        id="dctc_show_on_mobile"
                        name="dctc_show_on_mobile"
                        value="1"
                        <?php checked($dctc_show_on_mobile, '1'); ?>>
                    <span class="dctc-ios-slider"></span>
                </label>
                <div>
                    <span class="dctc-toggle-label"><span class="dashicons dashicons-smartphone" style="font-size: 16px; width: 16px; height: 16px;"></span> <?php esc_html_e('Show on Mobile Devices', 'dragwyb-click-to-chat'); ?></span>
                    <p class="dctc-field-hint"><?php esc_html_e('Optimize touch targets for smartphones and tablets.', 'dragwyb-click-to-chat'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card 3: Time Delay -->
<div class="dctc-ai-card dctc-section-card" style="margin-top: 20px;">
    <div class="dctc-ai-card__header">
        <div class="dctc-ai-card__header-left">
            <div class="dctc-ai-card-icon" style="background: #f0fdf4; color: #16a34a;">
                <span class="dashicons dashicons-clock" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </div>
            <div>
                <h3 class="dctc-card-title"><?php esc_html_e('Display Time Delay', 'dragwyb-click-to-chat'); ?></h3>
                <p class="dctc-card-subtitle"><?php esc_html_e('Delay widget appearance after page load in seconds.', 'dragwyb-click-to-chat'); ?></p>
            </div>
        </div>
    </div>

    <div class="dctc-card-body">
        <div class="dctc-field-group">
            <label for="dctc_time_delay" class="dctc-field-label">
                <?php esc_html_e('Delay Duration (Seconds)', 'dragwyb-click-to-chat'); ?>
            </label>
            <div class="dctc-input-unit-wrap" style="max-width: 200px;">
                <input type="number"
                    id="dctc_time_delay"
                    name="dctc_time_delay"
                    min="0"
                    max="60"
                    value="<?php echo esc_attr($dctc_time_delay); ?>"
                    class="dctc-modern-input"
                    style="width: 100px;">
                <span style="font-size: 13px; font-weight: 600; color: #64748b;"><?php esc_html_e('seconds', 'dragwyb-click-to-chat'); ?></span>
            </div>
            <p class="dctc-field-hint">
                <?php esc_html_e('Set to 0 for immediate display. Recommended: 2–5 seconds for optimal visitor engagement without blocking hero content.', 'dragwyb-click-to-chat'); ?>
            </p>
        </div>
    </div>
</div>
v>