<?php
/**
 * Plugin Name: Social Chat Widget for WordPress
 * Description: Connect with visitors via Facebook, WhatsApp, Telegram.
 * Author: Vishabjeet Singh
 * Version: 1.0.0
 * Text Domain: social-chat-widget
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SCW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCW_VERSION', '1.0.0' );
define( 'SCW_TABLE_NAME', 'scw_chat_messages' );

class SCW_Social_Chat_Widget {

    private static $instance = null;

    public function init() {
        if ( is_admin() ) {
            require_once SCW_PLUGIN_DIR . 'admin/settings.php';
            require_once SCW_PLUGIN_DIR . 'includes/admin-messages.php';
        }
        require_once SCW_PLUGIN_DIR . 'includes/frontend.php';
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new SCW_Social_Chat_Widget();
        }
        return self::$instance;
    }

    /**
     * Create/Upgrade Database Table
     */
    public static function scw_install() {
        global $wpdb;
        $table_name = $wpdb->prefix . SCW_TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) NOT NULL,
            sender varchar(10) NOT NULL, 
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

add_action( 'plugins_loaded', array( 'SCW_Social_Chat_Widget', 'get_instance' ) );
SCW_Social_Chat_Widget::get_instance()->init();

// Create DB on activation
register_activation_hook( __FILE__, array( 'SCW_Social_Chat_Widget', 'scw_install' ) );

// FORCE DB UPDATE (Uncomment this line once, reload page, then comment it out again if you have DB errors)
SCW_Social_Chat_Widget::scw_install();