<?php

/**
 * Plugin Name: Dragwyb Click to Chat
 * Description: Connect with visitors via Facebook, WhatsApp, Telegram.
 * Author: Dragwyb
 * Version: 1.0.1
 * Text Domain: dragwyb-click-to-chat
 * License: GPLv2 or later
 */

if (! defined('ABSPATH')) exit;

define('DCTC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DCTC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DCTC_VERSION', '1.0.1');
class DCTC_Click_To_Chat
{

    private static $instance = null;

    public function init()
    {
        if (is_admin()) {
            require_once DCTC_PLUGIN_DIR . 'admin/settings.php';
        }
        require_once DCTC_PLUGIN_DIR . 'includes/channel-registry.php';
        require_once DCTC_PLUGIN_DIR . 'includes/frontend.php';
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new DCTC_Click_To_Chat();
            self::$instance->init();
        }
        return self::$instance;
    }
}

add_action('plugins_loaded', array('DCTC_Click_To_Chat', 'get_instance'));
