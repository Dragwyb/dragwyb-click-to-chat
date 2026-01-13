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

<h2 class="dctc-section-title">
    <?php esc_html_e('Triggers and Targeting', 'dragwyb-click-to-chat'); ?>
</h2>

<p style="color:#6b7280; margin-bottom:30px;">
    <?php esc_html_e(
        'Control when and where your chat widget appears on your website.',
        'dragwyb-click-to-chat'
    ); ?>
</p>

<!-- Display Rules -->
<div class="dctc-form-group">
    <label>
        <?php esc_html_e('Display Rules', 'dragwyb-click-to-chat'); ?>
    </label>

    <p style="color:#9ca3af; font-size:13px; margin-bottom:20px;">
        <?php esc_html_e(
            'Choose where the chat widget should appear.',
            'dragwyb-click-to-chat'
        ); ?>
    </p>

    <div style="display:flex; flex-direction:column; gap:15px;">

        <!-- All Pages -->
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="radio"
                name="dctc_display_mode"
                value="all"
                <?php checked($dctc_display_mode, 'all'); ?>
                style="margin-top:4px;">
            <div>
                <span style="display:block; font-weight:500; font-size:14px; color:#374151;">
                    <?php esc_html_e('All Pages', 'dragwyb-click-to-chat'); ?>
                </span>
                <span style="font-size:13px; color:#6b7280;">
                    <?php esc_html_e(
                        'Show on every page of your website',
                        'dragwyb-click-to-chat'
                    ); ?>
                </span>
            </div>
        </label>

        <!-- Specific Post Types -->
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="radio"
                name="dctc_display_mode"
                value="post_types"
                <?php checked($dctc_display_mode, 'post_types'); ?>
                style="margin-top:4px;">
            <div>
                <span style="display:block; font-weight:500; font-size:14px; color:#374151;">
                    <?php esc_html_e('Specific Post Types', 'dragwyb-click-to-chat'); ?>
                </span>
                <span style="font-size:13px; color:#6b7280;">
                    <?php esc_html_e(
                        'Show only on selected content types',
                        'dragwyb-click-to-chat'
                    ); ?>
                </span>
            </div>
        </label>

        <!-- Post Types List -->
        <div id="dctc-post-types-list"
            style="margin-left:25px; padding:15px; background:#f9fafb; border-radius:6px; border:1px solid #e5e7eb; display:<?php echo $dctc_display_mode === 'post_types' ? 'block' : 'none'; ?>;">
            <?php foreach ($dctc_post_types as $dctc_pt_slug => $dctc_pt_obj) : ?>
                <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:13px;">
                    <input type="checkbox"
                        name="dctc_display_post_types[]"
                        value="<?php echo esc_attr($dctc_pt_slug); ?>"
                        <?php checked(in_array($dctc_pt_slug, $dctc_display_post_types, true)); ?>>
                    <?php echo esc_html($dctc_pt_obj->labels->name); ?>
                </label>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Device Visibility -->
<div class="dctc-form-group">
    <label>
        <?php esc_html_e('Device Visibility', 'dragwyb-click-to-chat'); ?>
    </label>

    <p style="color:#9ca3af; font-size:13px; margin-bottom:15px;">
        <?php esc_html_e(
            'Choose which devices should display the chat widget',
            'dragwyb-click-to-chat'
        ); ?>
    </p>

    <div style="display:flex; flex-direction:column; gap:15px;">

        <label class="dctc-toggle">
            <div class="dctc-switch">
                <input type="checkbox"
                    id="dctc_show_on_desktop"
                    name="dctc_show_on_desktop"
                    value="1"
                    <?php checked($dctc_show_on_desktop, '1'); ?>>
                <span class="dctc-slider"></span>
            </div>
            <span style="margin-left:10px;">
                <?php esc_html_e('Show on Desktop', 'dragwyb-click-to-chat'); ?>
            </span>
        </label>

        <label class="dctc-toggle">
            <div class="dctc-switch">
                <input type="checkbox"
                    id="dctc_show_on_mobile"
                    name="dctc_show_on_mobile"
                    value="1"
                    <?php checked($dctc_show_on_mobile, '1'); ?>>
                <span class="dctc-slider"></span>
            </div>
            <span style="margin-left:10px;">
                <?php esc_html_e('Show on Mobile', 'dragwyb-click-to-chat'); ?>
            </span>
        </label>

    </div>
</div>

<!-- Time Delay -->
<div class="dctc-form-group">
    <label for="dctc_time_delay">
        <?php esc_html_e('Time Delay', 'dragwyb-click-to-chat'); ?>
    </label>

    <p style="color:#9ca3af; font-size:13px; margin-bottom:15px;">
        <?php esc_html_e(
            'Delay widget appearance after page load (in seconds)',
            'dragwyb-click-to-chat'
        ); ?>
    </p>

    <div style="display:flex; align-items:center; gap:15px;">
        <input type="number"
            id="dctc_time_delay"
            name="dctc_time_delay"
            min="0"
            max="60"
            value="<?php echo esc_attr($dctc_time_delay); ?>"
            style="width:100px;">
        <span style="color:#6b7280;">
            <?php esc_html_e('seconds', 'dragwyb-click-to-chat'); ?>
        </span>
    </div>

    <p style="color:#9ca3af; font-size:12px; margin-top:8px;">
        <?php esc_html_e(
            'Set to 0 for immediate display. Recommended: 2–5 seconds for better user experience.',
            'dragwyb-click-to-chat'
        ); ?>
    </p>
</div>