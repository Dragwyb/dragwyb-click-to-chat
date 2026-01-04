<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'scw_add_settings_page' );
add_action( 'wp_ajax_scw_save_settings', 'scw_save_settings' );

add_action( 'admin_enqueue_scripts', 'scw_admin_scripts' );

function scw_add_settings_page() {
    add_menu_page( 'Social Chat', 'Social Chat', 'manage_options', 'social-chat-widget', 'scw_settings_page_html', 'dashicons-format-chat', 90 );
}

function scw_admin_scripts( $hook ) {
    // Only load on our plugin page
    if ( 'toplevel_page_social-chat-widget' !== $hook ) {
        return;
    }

    // Enqueue WordPress color picker
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    
    // Enqueue WordPress media uploader
    wp_enqueue_media();
    
    // Enqueue admin styles and scripts
    wp_enqueue_style( 
        'scw-admin-style', 
        SCW_PLUGIN_URL . 'admin/assets/css/admin-style.css', 
        array('wp-color-picker'), 
        SCW_VERSION 
    );
    
    wp_enqueue_script( 
        'scw-admin-script', 
        SCW_PLUGIN_URL . 'admin/assets/js/admin-script.js', 
        array('jquery', 'wp-color-picker'), 
        SCW_VERSION, 
        true 
    );
    
    // Localize script with AJAX data
    wp_localize_script( 'scw-admin-script', 'scw_admin', array(
        'nonce' => wp_create_nonce('scw_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

function scw_settings_page_html() {
    // Load the new admin template
    include SCW_PLUGIN_DIR . 'admin/admin-main.php';
}

function scw_save_settings() {
    check_ajax_referer('scw_nonce', 'nonce');
    if(!current_user_can('manage_options')) return;
    
    // Initialize settings array with defaults or existing data if we want to merge (but here we probably overwrite from form)
    // Actually, safest is to get existing and merge, or just build fresh from POST if POST contains everything.
    // Since POST only contains what's on the page, and we are on a single page app (sort of), we should probably merge.
    // However, for clean state, building fresh is often better if we know we cover all fields. 
    // Let's assume we cover all fields in the form.
    
    $settings = array();
    
    // Social channels list
    $phase1_channels = array('whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin');
    
    foreach ($phase1_channels as $slug) {
        // Enabled
        $enabled_key = $slug . '_enabled';
        if(isset($_POST[$enabled_key])) {
            $settings[$slug . '_enabled'] = $_POST[$enabled_key] === '1' ? '1' : '0';
        }
        
        // Value
        $value_key = $slug . '_value';
        if(isset($_POST[$value_key])) {
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
        if(isset($_POST[$icon_key])) {
            $settings[$slug . '_custom_icon'] = esc_url_raw(wp_unslash($_POST[$icon_key]));
        }
    }
    
    // Widget Customization
    if(isset($_POST['widget_position'])) {
        $settings['widget_position'] = sanitize_text_field(wp_unslash($_POST['widget_position']));
    }
    if(isset($_POST['widget_color'])) {
        $settings['widget_color'] = sanitize_hex_color(wp_unslash($_POST['widget_color']));
    }
    if(isset($_POST['widget_size'])) {
        $settings['widget_size'] = floatval($_POST['widget_size']);
    }
    if(isset($_POST['widget_size_unit'])) {
        $settings['widget_size_unit'] = sanitize_text_field(wp_unslash($_POST['widget_size_unit']));
    }
    
    // Custom Position
    if(isset($_POST['custom_bottom'])) {
        $settings['custom_bottom'] = floatval($_POST['custom_bottom']);
    }
    if(isset($_POST['custom_bottom_unit'])) {
        $settings['custom_bottom_unit'] = sanitize_text_field(wp_unslash($_POST['custom_bottom_unit']));
    }
    if(isset($_POST['custom_horizontal'])) {
        $settings['custom_horizontal'] = floatval($_POST['custom_horizontal']);
    }
    if(isset($_POST['custom_horizontal_unit'])) {
        $settings['custom_horizontal_unit'] = sanitize_text_field(wp_unslash($_POST['custom_horizontal_unit']));
    }
    if(isset($_POST['custom_side'])) {
        $settings['custom_side'] = sanitize_text_field(wp_unslash($_POST['custom_side']));
    }
    if(isset($_POST['custom_vertical_align'])) {
        $settings['custom_vertical_align'] = sanitize_text_field(wp_unslash($_POST['custom_vertical_align']));
    }
    
    // Icon Settings
    if(isset($_POST['icon_type'])) {
        $settings['icon_type'] = sanitize_text_field(wp_unslash($_POST['icon_type']));
    }
    if(isset($_POST['custom_icon_url'])) {
        $settings['custom_icon_url'] = esc_url_raw(wp_unslash($_POST['custom_icon_url']));
    }
    if(isset($_POST['icon_rotation'])) {
        $rotation = intval($_POST['icon_rotation']);
        if($rotation >= 0 && $rotation <= 360) {
            $settings['icon_rotation'] = $rotation;
        }
    }
    if(isset($_POST['icon_scale'])) {
        $scale = floatval($_POST['icon_scale']);
        if($scale >= 0.5 && $scale <= 2) {
            $settings['icon_scale'] = $scale;
        }
    }
    
    // Triggers and Targeting
    if(isset($_POST['show_on_desktop'])) {
        $settings['show_on_desktop'] = $_POST['show_on_desktop'] === '1' ? '1' : '0';
    }
    if(isset($_POST['show_on_mobile'])) {
        $settings['show_on_mobile'] = $_POST['show_on_mobile'] === '1' ? '1' : '0';
    }
    if(isset($_POST['time_delay'])) {
        $delay = intval($_POST['time_delay']);
        if($delay >= 0 && $delay <= 60) {
            $settings['time_delay'] = $delay;
        }
    }
    
    // Save all to single option
    update_option('scw_settings', $settings);
    
    wp_send_json_success();
}