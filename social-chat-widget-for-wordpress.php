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

class SCW_Social_Chat_Widget {

    private static $instance = null;

    public function init() {
        if ( is_admin() ) {
            include_once SCW_PLUGIN_DIR . 'includes/settings.php';
        }
        add_action( 'wp_footer', array( $this, 'scw_render_frontend_widget' ) );
        add_filter( 'plugin_action_links', array( $this, 'scw_plugin_action_links' ), 10, 2 );
    }

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new SCW_Social_Chat_Widget();
        }
        return self::$instance;
    }

    /**
     * Renders the frontend widget.
     */
    public function scw_render_frontend_widget() {
        
        // 1. CHECK VISIBILITY (Stop here if hidden on this page)
        if ( ! $this->scw_should_display() ) {
            return;
        }

        // 2. Get Settings
        $fb_id   = get_option( 'scw_facebook_page_id' );
        $wa_num  = get_option( 'scw_whatsapp_number' );
        $tg_user = get_option( 'scw_telegram_username' );

        // 3. Build array of active channels
        $channels = array();
        
        if ( ! empty( $fb_id ) ) {
            $channels[] = array(
                'name' => 'Messenger',
                'link' => 'https://m.me/' . esc_attr( $fb_id ),
                'color' => '#0084FF',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 11C2 13.66 3.39 16.04 5.55 17.59L5 22L9.5 19.53C10.3 19.83 11.14 20 12 20C17.52 20 22 15.97 22 11C22 6.03 17.52 2 12 2ZM17 13L14 10L11 13L7 9L10 12L13 9L17 13Z"/></svg>'
            );
        }

        if ( ! empty( $wa_num ) ) {
            $channels[] = array(
                'name' => 'WhatsApp',
                'link' => 'https://wa.me/' . esc_attr( $wa_num ),
                'color' => '#25D366',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2ZM12.05 20.16C10.58 20.16 9.11 19.76 7.85 19L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.81 13.47 3.81 11.91C3.81 7.37 7.5 3.67 12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.71 20.28 11.92C20.28 16.46 16.58 20.16 12.05 20.16Z"/></svg>'
            );
        }

        if ( ! empty( $tg_user ) ) {
            $channels[] = array(
                'name' => 'Telegram',
                'link' => 'https://t.me/' . esc_attr( $tg_user ),
                'color' => '#0088cc',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M9.78 18.65L10.06 14.31L17.74 7.51C18.08 7.2 17.66 7.03 17.22 7.31L7.74 13.3L3.64 12C2.75 11.75 2.75 11.14 3.84 10.7L19.87 4.54C20.61 4.24 21.26 4.73 21.02 5.83L18.29 18.71C18.09 19.71 17.5 19.93 16.68 19.48L12.55 16.43L10.57 18.35C10.35 18.57 10.16 18.75 9.78 18.65Z"/></svg>'
            );
        }

        // If no channels active, return
        if ( empty( $channels ) ) return;

        ?>
        <style>
            .scw-widget-container { position: fixed; bottom: 20px; right: 20px; z-index: 999999; display: flex; flex-direction: column-reverse; align-items: center; gap: 10px; }
            .scw-btn { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2); cursor: pointer; transition: all 0.3s ease; text-decoration: none; border: none; }
            .scw-btn svg { width: 30px; height: 30px; fill: white; }
            .scw-btn:hover { transform: scale(1.1); }
            
            /* Sub-buttons (hidden by default) */
            .scw-item { width: 50px; height: 50px; opacity: 0; transform: translateY(20px) scale(0.5); pointer-events: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
            .scw-item svg { width: 24px; height: 24px; }
            
            /* When container is active, show items */
            .scw-active .scw-item { opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
            .scw-active .scw-main-btn { transform: rotate(45deg); }
        </style>

        <div class="scw-widget-container" id="scw-widget">
            
            <?php 
            // CASE 1: Only ONE channel active -> Direct Link
            if ( count( $channels ) === 1 ) : 
                $c = $channels[0];
            ?>
                <a href="<?php echo $c['link']; ?>" target="_blank" class="scw-btn" style="background: <?php echo $c['color']; ?>;" aria-label="Chat on <?php echo $c['name']; ?>">
                    <?php echo $c['icon']; ?>
                </a>

            <?php 
            // CASE 2: Multiple channels -> Toggle Menu
            else : 
            ?>
                <button class="scw-btn scw-main-btn" style="background: #333;" onclick="document.getElementById('scw-widget').classList.toggle('scw-active')">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z"/></svg>
                </button>

                <?php foreach ( $channels as $c ) : ?>
                    <a href="<?php echo $c['link']; ?>" target="_blank" class="scw-btn scw-item" style="background: <?php echo $c['color']; ?>;" aria-label="<?php echo $c['name']; ?>">
                        <?php echo $c['icon']; ?>
                    </a>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Logic: Should we display the widget?
     */
   /**
     * Logic: Should we display the widget?
     */
    public function scw_should_display() {
        // 1. Get Mode ('all' or 'custom')
        $mode = get_option( 'scw_visibility_mode', 'all' );

        // If 'all', show everywhere
        if ( $mode === 'all' ) {
            return true;
        }

        // 2. Get Custom Settings
        $show_on_home = get_option( 'scw_vis_home' ) === '1';
        $show_on_post = get_option( 'scw_vis_post' ) === '1';
        $specific_pages = get_option( 'scw_vis_specific_pages', array() );

        // Check Homepage
        if ( ( is_home() || is_front_page() ) && $show_on_home ) {
            return true;
        }

        // Check Single Posts
        if ( is_single() && $show_on_post ) {
            return true;
        }

        // Check Specific Pages (The User's Choice)
        if ( is_page() && ! empty( $specific_pages ) ) {
            // Check if current page ID is in the saved list
            if ( in_array( get_the_ID(), $specific_pages ) ) {
                return true;
            }
        }

        // If none matched, do NOT display
        return false;
    }

    public function scw_plugin_action_links( $links, $file ) {
        if ( plugin_basename( __FILE__ ) === $file ) {
            array_unshift( $links, '<a href="options-general.php?page=social-chat-widget">Settings</a>' );
        }
        return $links;
    }
}

SCW_Social_Chat_Widget::get_instance()->init();