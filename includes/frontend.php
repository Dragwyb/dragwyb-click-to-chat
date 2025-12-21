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

        $channels = array();

        // 1. Live Chat
        if ( $live_chat ) {
            $channels[] = array(
                'type' => 'internal',
                'color' => '#8e44ad',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>'
            );
        }

        // 2. WhatsApp
        if ( ! empty( $wa_num ) ) {
            $channels[] = array(
                'type' => 'link',
                'link' => 'https://wa.me/' . esc_attr( $wa_num ),
                'color' => '#25D366',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2Z"/></svg>'
            );
        }

        // 3. Messenger
        if ( ! empty( $fb_id ) ) {
            $channels[] = array(
                'type' => 'link',
                'link' => 'https://m.me/' . esc_attr( $fb_id ),
                'color' => '#0084FF',
                'icon' => '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 11C2 13.66 3.39 16.04 5.55 17.59L5 22L9.5 19.53C10.3 19.83 11.14 20 12 20C17.52 20 22 15.97 22 11C22 6.03 17.52 2 12 2ZM17 13L14 10L11 13L7 9L10 12L13 9L17 13Z"/></svg>'
            );
        }

        // If NO channels are active, return nothing.
        if ( empty( $channels ) ) return;
        ?>

        <style>
            /* The Floating Button */
            .scw-widget-btn { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 999999; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.3s; }
            .scw-widget-btn:hover { transform: scale(1.1); }
            .scw-widget-btn svg { width: 30px; fill: white; }

            /* Sub Menu Items */
            .scw-menu { position: fixed; bottom: 90px; right: 25px; display: flex; flex-direction: column; gap: 10px; z-index: 999998; opacity: 0; pointer-events: none; transform: translateY(20px); transition: all 0.3s; }
            .scw-menu.scw-open { opacity: 1; pointer-events: auto; transform: translateY(0); }
            .scw-sub-btn { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,0.2); cursor: pointer; border: none; }
            .scw-sub-btn svg { width: 24px; fill: white; }

            /* Chat Window */
            .scw-chat-box { display: none; position: fixed; bottom: 90px; right: 20px; width: 350px; height: 450px; background: #fff; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); z-index: 1000000; overflow: hidden; font-family: sans-serif; flex-direction: column; }
            .scw-header { background: #8e44ad; color: #fff; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; }
            .scw-close { cursor: pointer; }
            .scw-messages { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; }
            .msg-bubble { max-width: 80%; padding: 10px; border-radius: 10px; font-size: 14px; }
            .msg-user { align-self: flex-end; background: #8e44ad; color: white; }
            .msg-admin { align-self: flex-start; background: #e0e0e0; color: #333; }
            .scw-footer { padding: 10px; border-top: 1px solid #ddd; display: flex; gap: 5px; background: white; }
            .scw-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
            .scw-send { background: #8e44ad; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        </style>

        <div class="scw-widget-btn" onclick="scwToggleMenu()">
            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
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