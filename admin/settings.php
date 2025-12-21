<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'scw_add_settings_page' );
add_action( 'wp_ajax_scw_save_settings', 'scw_save_settings' );

function scw_add_settings_page() {
    add_menu_page( 'Social Chat', 'Social Chat', 'manage_options', 'social-chat-widget', 'scw_settings_page_html', 'dashicons-format-chat', 90 );
}

function scw_settings_page_html() {
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
    
    // Load the new admin template
    include SCW_PLUGIN_DIR . 'admin/admin-main.php';
}

function scw_save_settings() {
    check_ajax_referer('scw_nonce', 'nonce');
    if(!current_user_can('manage_options')) return;
    
    // Save channel settings
    update_option('scw_facebook_page_id', sanitize_text_field($_POST['fb']));
    update_option('scw_whatsapp_number', sanitize_text_field($_POST['wa']));
    update_option('scw_enable_live_chat', sanitize_text_field($_POST['lc']));
    
    // Save channel enabled states
    if(isset($_POST['fb_enabled'])) {
        update_option('scw_facebook_enabled', $_POST['fb_enabled'] === '1' ? '1' : '0');
    }
    if(isset($_POST['wa_enabled'])) {
        update_option('scw_whatsapp_enabled', $_POST['wa_enabled'] === '1' ? '1' : '0');
    }
    
    // Save widget customization settings (if provided)
    if(isset($_POST['widget_position'])) {
        update_option('scw_widget_position', sanitize_text_field($_POST['widget_position']));
    }
    if(isset($_POST['widget_color'])) {
        update_option('scw_widget_color', sanitize_hex_color($_POST['widget_color']));
    }
    if(isset($_POST['widget_size'])) {
        $size = intval($_POST['widget_size']);
        if($size >= 40 && $size <= 80) {
            update_option('scw_widget_size', $size);
        }
    }
    
    // Save custom position settings (if provided)
    if(isset($_POST['custom_bottom'])) {
        $bottom = intval($_POST['custom_bottom']);
        if($bottom >= 0 && $bottom <= 500) {
            update_option('scw_custom_bottom', $bottom);
        }
    }
    if(isset($_POST['custom_horizontal'])) {
        $horizontal = intval($_POST['custom_horizontal']);
        if($horizontal >= 0 && $horizontal <= 500) {
            update_option('scw_custom_horizontal', $horizontal);
        }
    }
    if(isset($_POST['custom_side'])) {
        update_option('scw_custom_side', sanitize_text_field($_POST['custom_side']));
    }
    
    // Save icon settings (if provided)
    if(isset($_POST['icon_type'])) {
        update_option('scw_icon_type', sanitize_text_field($_POST['icon_type']));
    }
    if(isset($_POST['custom_icon_url'])) {
        update_option('scw_custom_icon_url', esc_url_raw($_POST['custom_icon_url']));
    }
    if(isset($_POST['icon_rotation'])) {
        $rotation = intval($_POST['icon_rotation']);
        if($rotation >= 0 && $rotation <= 360) {
            update_option('scw_icon_rotation', $rotation);
        }
    }
    if(isset($_POST['icon_scale'])) {
        $scale = floatval($_POST['icon_scale']);
        if($scale >= 0.5 && $scale <= 2) {
            update_option('scw_icon_scale', $scale);
        }
    }
    
    // Save triggers and targeting settings (if provided)
    if(isset($_POST['show_on_desktop'])) {
        update_option('scw_show_on_desktop', $_POST['show_on_desktop'] === '1' ? '1' : '0');
    }
    if(isset($_POST['show_on_mobile'])) {
        update_option('scw_show_on_mobile', $_POST['show_on_mobile'] === '1' ? '1' : '0');
    }
    if(isset($_POST['time_delay'])) {
        $delay = intval($_POST['time_delay']);
        if($delay >= 0 && $delay <= 60) {
            update_option('scw_time_delay', $delay);
        }
    }
    
    wp_send_json_success();
}