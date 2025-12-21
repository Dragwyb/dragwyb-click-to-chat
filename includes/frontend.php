<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SCW_Frontend {

    public function __construct() {
        // REMOVED: Auto-add to footer
        // add_action( 'wp_footer', array( $this, 'render_widget_auto' ) );
        
        // 1. Register Shortcode [social_chat]
        add_shortcode( 'social_chat', array( $this, 'render_widget_shortcode' ) );

        // 2. AJAX Handlers (For Live Chat)
        add_action( 'wp_ajax_scw_send_message', array( $this, 'ajax_send_message' ) );
        add_action( 'wp_ajax_nopriv_scw_send_message', array( $this, 'ajax_send_message' ) );
        add_action( 'wp_ajax_scw_get_messages', array( $this, 'ajax_get_messages' ) );
        add_action( 'wp_ajax_nopriv_scw_get_messages', array( $this, 'ajax_get_messages' ) );
    }

    /**
     * Wrapper for Shortcode [social_chat]
     */
    public function render_widget_shortcode() {
        ob_start(); // Start recording output
        $this->render_widget_html(); // Generate HTML/CSS/JS
        return ob_get_clean(); // Return output to the page
    }

    /**
     * The Main Render Function
     */
    public function render_widget_html() {
        $live_chat = get_option( 'scw_enable_live_chat' );
        $fb_id     = get_option( 'scw_facebook_page_id' );
        $wa_num    = get_option( 'scw_whatsapp_number' );
        
        // Get widget customization settings
        $widget_position = get_option( 'scw_widget_position', 'right' );
        $widget_color = get_option( 'scw_widget_color', '#8e44ad' );
        $widget_size = get_option( 'scw_widget_size', '60' );
        $time_delay = get_option( 'scw_time_delay', '0' );
        
        // Get device visibility settings
        $show_on_desktop = get_option( 'scw_show_on_desktop', '1' );
        $show_on_mobile = get_option( 'scw_show_on_mobile', '1' );
        
        // Check if widget should be displayed based on device
        $is_mobile = wp_is_mobile();
        if ( $is_mobile && $show_on_mobile !== '1' ) {
            return; // Don't show on mobile
        }
        if ( ! $is_mobile && $show_on_desktop !== '1' ) {
            return; // Don't show on desktop
        }
        
        // Determine position styles
        if ($widget_position === 'custom') {
            // Custom position
            $custom_bottom = get_option('scw_custom_bottom', '20');
            $custom_horizontal = get_option('scw_custom_horizontal', '20');
            $custom_side = get_option('scw_custom_side', 'right');
            
            $position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . esc_attr($custom_horizontal) . 'px;';
            $position_style .= ' bottom: ' . esc_attr($custom_bottom) . 'px;';
            
            $menu_position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . esc_attr($custom_horizontal + 5) . 'px;';
            $chatbox_position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . esc_attr($custom_horizontal) . 'px;';
        } else {
            // Preset position (left or right)
            $position_style = $widget_position === 'left' ? 'left: 20px;' : 'right: 20px;';
            $menu_position_style = $widget_position === 'left' ? 'left: 25px;' : 'right: 25px;';
            $chatbox_position_style = $widget_position === 'left' ? 'left: 20px;' : 'right: 20px;';
        }

        $channels = array();

        // 1. Live Chat - check if enabled
        if ( $live_chat === '1' ) {
            $channels[] = array(
                'type' => 'internal',
                'color' => '#8e44ad',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>'
            );
        }

        // 2. WhatsApp - check if enabled AND has number
        $wa_enabled = get_option( 'scw_whatsapp_enabled', '1' );
        if ( $wa_enabled === '1' && ! empty( $wa_num ) ) {
            $channels[] = array(
                'type' => 'link',
                'link' => 'https://wa.me/' . esc_attr( $wa_num ),
                'color' => '#25D366',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/></svg>'
            );
        }

        // 3. Messenger - check if enabled AND has Page ID
        $fb_enabled = get_option( 'scw_facebook_enabled', '1' );
        if ( $fb_enabled === '1' && ! empty( $fb_id ) ) {
            $channels[] = array(
                'type' => 'link',
                'link' => 'https://m.me/' . esc_attr( $fb_id ),
                'color' => '#0084FF',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 11C2 13.66 3.39 16.04 5.55 17.59L5 22L9.5 19.53C10.3 19.83 11.14 20 12 20C17.52 20 22 15.97 22 11C22 6.03 17.52 2 12 2ZM17 13L14 10L11 13L7 9L10 12L13 9L17 13Z"/></svg>'
            );
        }

        // If NO channels are active, return nothing.
        if ( empty( $channels ) ) return;
        
        // Get icon settings
        $icon_type = get_option('scw_icon_type', 'chat');
        $custom_icon_url = get_option('scw_custom_icon_url', '');
        $icon_rotation = get_option('scw_icon_rotation', '0');
        $icon_scale = get_option('scw_icon_scale', '1');
        
        // Generate icon HTML based on type
        $icon_html = '';
        $icon_transform = 'transform: rotate(' . esc_attr($icon_rotation) . 'deg) scale(' . esc_attr($icon_scale) . ');';
        
        switch ($icon_type) {
            case 'chat':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
                break;
            case 'message':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
                break;
            case 'support':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
                break;
            case 'phone':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
                break;
            case 'custom':
                if (!empty($custom_icon_url)) {
                    $file_ext = strtolower(pathinfo($custom_icon_url, PATHINFO_EXTENSION));
                    if ($file_ext === 'svg') {
                        // For SVG, embed it directly
                        $svg_content = @file_get_contents($custom_icon_url);
                        if ($svg_content) {
                            // Add transform to SVG
                            $icon_html = str_replace('<svg', '<svg style="' . $icon_transform . '"', $svg_content);
                        } else {
                            // Fallback if can't read file
                            $icon_html = '<img src="' . esc_url($custom_icon_url) . '" alt="Chat" style="width: 100%; height: 100%; object-fit: contain; ' . $icon_transform . '">';
                        }
                    } else {
                        // For PNG/JPG
                        $icon_html = '<img src="' . esc_url($custom_icon_url) . '" alt="Chat" style="width: 100%; height: 100%; object-fit: contain; ' . $icon_transform . '">';
                    }
                } else {
                    // Fallback to default chat icon
                    $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
                }
                break;
            default:
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
        }
        ?>

        <style>
            /* The Floating Button */
            .scw-widget-btn { position: fixed; bottom: 20px; <?php echo $position_style; ?> width: <?php echo esc_attr($widget_size); ?>px; height: <?php echo esc_attr($widget_size); ?>px; background: <?php echo esc_attr($widget_color); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 999999; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.3s; }
            .scw-widget-btn:hover { transform: scale(1.1); }
            .scw-widget-btn svg { width: <?php echo esc_attr($widget_size * 0.5); ?>px; fill: white; }

            /* Sub Menu Items */
            .scw-menu { position: fixed; bottom: <?php echo esc_attr($widget_size + 30); ?>px; <?php echo $menu_position_style; ?> display: flex; flex-direction: column; gap: 10px; z-index: 999998; opacity: 0; pointer-events: none; transform: translateY(20px); transition: all 0.3s; }
            .scw-menu.scw-open { opacity: 1; pointer-events: auto; transform: translateY(0); }
            .scw-sub-btn { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,0.2); cursor: pointer; border: none; }
            .scw-sub-btn svg { width: 24px; fill: white; }

            /* Chat Window */
            .scw-chat-box { display: none; position: fixed; bottom: <?php echo esc_attr($widget_size + 30); ?>px; <?php echo $chatbox_position_style; ?> width: 350px; height: 450px; background: #fff; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); z-index: 1000000; overflow: hidden; font-family: sans-serif; flex-direction: column; }
            .scw-header { background: <?php echo esc_attr($widget_color); ?>; color: #fff; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; }
            .scw-close { cursor: pointer; }
            .scw-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
            .msg-bubble { max-width: 80%; padding: 10px; border-radius: 10px; font-size: 14px; }
            .msg-user { align-self: flex-end; background: <?php echo esc_attr($widget_color); ?>; color: white; }
            .msg-admin { align-self: flex-start; background: #e0e0e0; color: #333; }
            .scw-footer { padding: 10px; border-top: 1px solid #ddd; display: flex; gap: 5px; background: white; }
            .scw-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
            .scw-send { background: <?php echo esc_attr($widget_color); ?>; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
            
            /* Initially hide the widget */
            .scw-widget-hidden { opacity: 0; pointer-events: none; }
        </style>

        <div class="scw-widget-btn <?php echo $time_delay > 0 ? 'scw-widget-hidden' : ''; ?>" id="scw-widget-btn" onclick="scwToggleMenu()">
            <?php echo $icon_html; ?>
        </div>

        <div class="scw-menu" id="scw-menu">
            <?php foreach ( $channels as $c ) : ?>
                <?php if ( $c['type'] === 'internal' ) : ?>
                    <button class="scw-sub-btn" style="background: <?php echo $c['color']; ?>;" onclick="scwOpenChat()">
                        <?php echo $c['icon']; ?>
                    </button>
                <?php else : ?>
                    <a href="<?php echo $c['link']; ?>" target="_blank" class="scw-sub-btn" style="background: <?php echo $c['color']; ?>;">
                        <?php echo $c['icon']; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="scw-chat-box" id="scw-chat-box">
            <div class="scw-header">
                <span>Support Chat</span>
                <span class="scw-close" onclick="scwCloseChat()">&times;</span>
            </div>
            <div class="scw-messages" id="scw-messages"></div>
            <div class="scw-footer">
                <input type="text" id="scw-input" class="scw-input" placeholder="Type a message..." onkeypress="handleEnter(event)">
                <button class="scw-send" onclick="scwSendMessage()">
                    <svg viewBox="0 0 24 24" style="width: 20px;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>

        <script>
            // Time delay for widget appearance
            <?php if ($time_delay > 0) : ?>
            setTimeout(function() {
                const widget = document.getElementById('scw-widget-btn');
                if (widget) {
                    widget.classList.remove('scw-widget-hidden');
                    widget.style.opacity = '1';
                    widget.style.pointerEvents = 'auto';
                    widget.style.transition = 'opacity 0.3s ease';
                }
            }, <?php echo esc_js($time_delay * 1000); ?>);
            <?php endif; ?>
            
            let sessionId = localStorage.getItem('scw_session_id');
            if (!sessionId) {
                sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('scw_session_id', sessionId);
            }

            function scwToggleMenu() {
                document.getElementById('scw-menu').classList.toggle('scw-open');
                document.getElementById('scw-chat-box').style.display = 'none';
            }

            function scwOpenChat() {
                document.getElementById('scw-chat-box').style.display = 'flex';
                document.getElementById('scw-menu').classList.remove('scw-open');
                scwLoadMessages();
                scwScrollBottom();
            }

            function scwCloseChat() {
                document.getElementById('scw-chat-box').style.display = 'none';
            }

            function handleEnter(e) {
                if(e.key === 'Enter') scwSendMessage();
            }

            function scwScrollBottom() {
                const div = document.getElementById('scw-messages');
                div.scrollTop = div.scrollHeight;
            }

            function scwLoadMessages() {
                const data = new FormData();
                data.append('action', 'scw_get_messages');
                data.append('session_id', sessionId);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
                .then(res => res.json())
                .then(response => {
                    if(response.success) {
                        const div = document.getElementById('scw-messages');
                        div.innerHTML = ''; 
                        response.data.forEach(msg => {
                            const bubble = document.createElement('div');
                            bubble.className = 'msg-bubble ' + (msg.sender === 'user' ? 'msg-user' : 'msg-admin');
                            bubble.innerText = msg.message;
                            div.appendChild(bubble);
                        });
                        scwScrollBottom();
                    }
                });
            }

            function scwSendMessage() {
                const input = document.getElementById('scw-input');
                const msg = input.value.trim();
                if(!msg) return;

                const div = document.getElementById('scw-messages');
                const bubble = document.createElement('div');
                bubble.className = 'msg-bubble msg-user';
                bubble.innerText = msg;
                div.appendChild(bubble);
                scwScrollBottom();
                input.value = '';

                const data = new FormData();
                data.append('action', 'scw_send_message');
                data.append('session_id', sessionId);
                data.append('message', msg);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data });
            }

            setInterval(() => {
                if(document.getElementById('scw-chat-box').style.display === 'flex') {
                    scwLoadMessages();
                }
            }, 5000);
        </script>
        <?php
    }

    public function ajax_get_messages() {
        global $wpdb;
        $table = $wpdb->prefix . 'scw_chat_messages';
        $session_id = sanitize_text_field( $_POST['session_id'] );
        $messages = $wpdb->get_results( $wpdb->prepare("SELECT sender, message FROM $table WHERE session_id = %s ORDER BY created_at ASC", $session_id) );
        wp_send_json_success( $messages );
    }

    public function ajax_send_message() {
        global $wpdb;
        $table = $wpdb->prefix . 'scw_chat_messages';
        $wpdb->insert($table, array(
            'session_id' => sanitize_text_field($_POST['session_id']),
            'sender' => 'user',
            'message' => sanitize_textarea_field($_POST['message']),
            'created_at' => current_time('mysql')
        ));
        wp_send_json_success();
    }
}

new SCW_Frontend();