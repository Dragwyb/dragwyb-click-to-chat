<?php
if (! defined('ABSPATH')) exit;

add_action('admin_menu', 'dctc_add_settings_page');
add_action('wp_ajax_dctc_save_settings', 'dctc_save_settings');

add_action('admin_enqueue_scripts', 'dctc_admin_scripts');

function dctc_add_settings_page()
{
    add_menu_page('Social Chat', 'Social Chat', 'manage_options', 'dragwyb-click-to-chat', 'dctc_settings_page_html', 'dashicons-format-chat', 90);
}

function dctc_admin_scripts($hook)
{
    // Only load on our plugin page
    if ('toplevel_page_dragwyb-click-to-chat' !== $hook) {
        return;
    }

    // Enqueue WordPress color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Enqueue WordPress media uploader
    wp_enqueue_media();

    // Enqueue admin styles and scripts
    wp_enqueue_style(
        'dctc-admin-style',
        DCTC_PLUGIN_URL . 'admin/assets/css/admin-style.css',
        array('wp-color-picker'),
        DCTC_VERSION
    );

    wp_enqueue_script(
        'dctc-admin-script',
        DCTC_PLUGIN_URL . 'admin/assets/js/admin-script.js',
        array('jquery', 'wp-color-picker'),
        DCTC_VERSION,
        true
    );

    // Localize script with AJAX data
    wp_localize_script('dctc-admin-script', 'dctc_admin', array(
        'nonce' => wp_create_nonce('dctc_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

function dctc_settings_page_html()
{
    // Load the new admin template
    include DCTC_PLUGIN_DIR . 'admin/admin-main.php';
}

function dctc_save_settings()
{
    check_ajax_referer('dctc_nonce', 'nonce');
    if (!current_user_can('manage_options')) return;


    $settings = array();

    // Social channels list
    $phase1_channels = array('whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin');

    foreach ($phase1_channels as $slug) {
        // Enabled
        $enabled_key = $slug . '_enabled';
        if (isset($_POST[$enabled_key])) {
            $settings[$slug . '_enabled'] = $_POST[$enabled_key] === '1' ? '1' : '0';
        }

        // Value
        $value_key = $slug . '_value';
        if (isset($_POST[$value_key])) {
            if ($slug === 'email') {
                $settings[$slug . '_value'] = sanitize_email(wp_unslash($_POST[$value_key]));
            } elseif (in_array($slug, array('linkedin', 'maps', 'waze', 'contact', 'poptin', 'slack', 'discord'))) {
                $settings[$slug . '_value'] = esc_url_raw(wp_unslash($_POST[$value_key]));
            } else {
                $settings[$slug . '_value'] = sanitize_text_field(wp_unslash($_POST[$value_key]));
            }
        }

        // Device visibility
        $desktop_key = $slug . '_desktop';
        $mobile_key = $slug . '_mobile';
        $settings[$slug . '_desktop'] = isset($_POST[$desktop_key]) && $_POST[$desktop_key] === '1' ? '1' : '0';
        $settings[$slug . '_mobile'] = isset($_POST[$mobile_key]) && $_POST[$mobile_key] === '1' ? '1' : '0';

        // Custom Icon
        $icon_key = $slug . '_custom_icon';
        if (isset($_POST[$icon_key])) {
            $settings[$slug . '_custom_icon'] = esc_url_raw(wp_unslash($_POST[$icon_key]));
        }

        // Chat Widget Settings
        $chat_widget_key = $slug . '_chat_widget_enabled';
        if (isset($_POST[$chat_widget_key])) {
            $settings[$slug . '_chat_widget_enabled'] = $_POST[$chat_widget_key] === '1' ? '1' : '0';
        }

        $default_message_key = $slug . '_default_message';
        if (isset($_POST[$default_message_key])) {
            $settings[$slug . '_default_message'] = sanitize_textarea_field(wp_unslash($_POST[$default_message_key]));
        }
    }

    // Widget Customization
    if (isset($_POST['widget_position'])) {
        $settings['widget_position'] = sanitize_text_field(wp_unslash($_POST['widget_position']));
    }
    if (isset($_POST['widget_color'])) {
        $settings['widget_color'] = sanitize_hex_color(wp_unslash($_POST['widget_color']));
    }
    if (isset($_POST['widget_size'])) {
        $settings['widget_size'] = floatval($_POST['widget_size']);
    }
    if (isset($_POST['widget_size_unit'])) {
        $settings['widget_size_unit'] = sanitize_text_field(wp_unslash($_POST['widget_size_unit']));
    }

    // Custom Position
    if (isset($_POST['custom_bottom'])) {
        $settings['custom_bottom'] = floatval($_POST['custom_bottom']);
    }
    if (isset($_POST['custom_bottom_unit'])) {
        $settings['custom_bottom_unit'] = sanitize_text_field(wp_unslash($_POST['custom_bottom_unit']));
    }
    if (isset($_POST['custom_horizontal'])) {
        $settings['custom_horizontal'] = floatval($_POST['custom_horizontal']);
    }
    if (isset($_POST['custom_horizontal_unit'])) {
        $settings['custom_horizontal_unit'] = sanitize_text_field(wp_unslash($_POST['custom_horizontal_unit']));
    }
    if (isset($_POST['custom_side'])) {
        $settings['custom_side'] = sanitize_text_field(wp_unslash($_POST['custom_side']));
    }
    if (isset($_POST['custom_vertical_align'])) {
        $settings['custom_vertical_align'] = sanitize_text_field(wp_unslash($_POST['custom_vertical_align']));
    }

    // Icon Settings
    if (isset($_POST['icon_type'])) {
        $settings['icon_type'] = sanitize_text_field(wp_unslash($_POST['icon_type']));
    }
    if (isset($_POST['custom_icon_url'])) {
        $settings['custom_icon_url'] = esc_url_raw(wp_unslash($_POST['custom_icon_url']));
    }
    if (isset($_POST['icon_rotation'])) {
        $rotation = intval($_POST['icon_rotation']);
        if ($rotation >= 0 && $rotation <= 360) {
            $settings['icon_rotation'] = $rotation;
        }
    }
    if (isset($_POST['icon_scale'])) {
        $scale = floatval($_POST['icon_scale']);
        if ($scale >= 0.5 && $scale <= 2) {
            $settings['icon_scale'] = $scale;
        }
    }

    // Triggers and Targeting
    if (isset($_POST['show_on_desktop'])) {
        $settings['show_on_desktop'] = $_POST['show_on_desktop'] === '1' ? '1' : '0';
    }
    if (isset($_POST['show_on_mobile'])) {
        $settings['show_on_mobile'] = $_POST['show_on_mobile'] === '1' ? '1' : '0';
    }
    if (isset($_POST['time_delay'])) {
        $delay = intval($_POST['time_delay']);
        if ($delay >= 0 && $delay <= 60) {
            $settings['time_delay'] = $delay;
        }
    }

    // Display Rules
    if (isset($_POST['dctc_display_mode'])) {
        $settings['display_mode'] = sanitize_text_field(wp_unslash($_POST['dctc_display_mode']));
    }
    if (isset($_POST['dctc_display_post_types']) && is_array($_POST['dctc_display_post_types'])) {
        $settings['display_post_types'] = array_map('sanitize_text_field', wp_unslash($_POST['dctc_display_post_types']));
    } else {
        $settings['display_post_types'] = array();
    }

    // Save all to single option
    update_option('dctc_settings', $settings);

    wp_send_json_success();
}
