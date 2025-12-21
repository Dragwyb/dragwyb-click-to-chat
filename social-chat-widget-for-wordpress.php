<?php
/**
 * Plugin Name: Social Chat Widget
 * Description: Connect with visitors via Facebook, WhatsApp, Telegram.
 * Author: Dragwyb
 * Version: 1.0.0
 * Text Domain: social-chat-widget-for-wordpress
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SCW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCW_VERSION', '1.0.0' );
class SCW_Social_Chat_Widget {

    private static $instance = null;

    public function init() {
		if ( is_admin() ) {
			require_once SCW_PLUGIN_DIR . 'admin/settings.php';
		}
		require_once SCW_PLUGIN_DIR . 'includes/channel-registry.php';
		require_once SCW_PLUGIN_DIR . 'includes/frontend.php';
	}    

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new SCW_Social_Chat_Widget();
        }
        return self::$instance;
    }
}

add_action( 'plugins_loaded', array( 'SCW_Social_Chat_Widget', 'get_instance' ) );
SCW_Social_Chat_Widget::get_instance()->init();