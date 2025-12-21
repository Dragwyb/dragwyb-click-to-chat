<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'scw_register_messages_page' );
add_action( 'admin_init', 'scw_handle_admin_reply' );

function scw_register_messages_page() {
    add_submenu_page( 'social-chat-widget', 'Inbox', 'Inbox', 'manage_options', 'scw-inbox', 'scw_render_inbox' );
}

/**
 * Handle Admin Reply Submission
 */
function scw_handle_admin_reply() {
    if ( isset( $_POST['scw_admin_reply'] ) && current_user_can( 'manage_options' ) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'scw_chat_messages';
        
        // Save Admin Reply
        $wpdb->insert($table, array(
            'session_id' => sanitize_text_field($_POST['session_id']),
            'sender' => 'admin', // Mark as Admin
            'message' => sanitize_textarea_field($_POST['message']),
            'created_at' => current_time('mysql')
        ));
    }
}

function scw_render_inbox() {
    global $wpdb;
    $table = $wpdb->prefix . 'scw_chat_messages';

    // 1. Get List of Unique Users (Sessions)
    // We group by session_id to show one line per user
    $conversations = $wpdb->get_results( "SELECT session_id, MAX(created_at) as last_msg_time FROM $table GROUP BY session_id ORDER BY last_msg_time DESC" );
    
    // 2. Get Currently Selected User
    $active_session = isset($_GET['session']) ? sanitize_text_field($_GET['session']) : ( $conversations ? $conversations[0]->session_id : null );
    ?>

    <div class="wrap" style="margin: 0;">
        <h1 class="wp-heading-inline">Live Chat Inbox</h1>
        
        <div style="display: flex; height: 500px; border: 1px solid #ccd0d4; background: #fff; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
            
            <div style="width: 250px; border-right: 1px solid #ccd0d4; overflow-y: auto; background: #f6f7f7;">
                <ul style="margin: 0; list-style: none;">
                    <?php if ( empty($conversations) ) : ?>
                        <li style="padding: 20px; color: #666;">No chats yet.</li>
                    <?php else : ?>
                        <?php foreach ( $conversations as $conv ) : ?>
                            <?php 
                                // Get the last message preview
                                $last_msg_row = $wpdb->get_row( $wpdb->prepare("SELECT message FROM $table WHERE session_id = %s ORDER BY created_at DESC LIMIT 1", $conv->session_id) );
                                $preview = $last_msg_row ? substr(strip_tags($last_msg_row->message), 0, 30) : '...';
                                $is_active = ($active_session === $conv->session_id);
                            ?>
                            <li style="margin: 0; border-bottom: 1px solid #ccd0d4;">
                                <a href="?page=scw-inbox&session=<?php echo $conv->session_id; ?>" 
                                   style="display: block; padding: 15px; text-decoration: none; color: #3c434a; background: <?php echo $is_active ? '#fff' : 'transparent'; ?>; border-left: 4px solid <?php echo $is_active ? '#8e44ad' : 'transparent'; ?>;">
                                    <strong>Guest (<?php echo substr($conv->session_id, 0, 6); ?>)</strong><br>
                                    <small style="color: #646970;"><?php echo esc_html($preview); ?>...</small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div style="flex: 1; display: flex; flex-direction: column; background: #fff;">
                
                <?php if ( $active_session ) : ?>
                    <div style="flex: 1; padding: 20px; overflow-y: auto; background: #fff;" id="scw-admin-chat-window">
                        <?php 
                        $msgs = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table WHERE session_id = %s ORDER BY created_at ASC", $active_session) );
                        foreach ( $msgs as $msg ) : 
                            $is_admin = ($msg->sender === 'admin');
                        ?>
                            <div style="margin-bottom: 10px; text-align: <?php echo $is_admin ? 'right' : 'left'; ?>;">
                                <div style="display: inline-block; max-width: 70%; padding: 8px 12px; border-radius: 8px; background: <?php echo $is_admin ? '#8e44ad' : '#f0f0f1'; ?>; color: <?php echo $is_admin ? '#fff' : '#3c434a'; ?>; text-align: left;">
                                    <?php echo esc_html($msg->message); ?>
                                </div>
                                <div style="font-size: 10px; color: #999; margin-top: 2px;">
                                    <?php echo $msg->created_at; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="padding: 15px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
                        <form method="post" style="display: flex; gap: 10px;">
                            <input type="hidden" name="session_id" value="<?php echo esc_attr($active_session); ?>">
                            <input type="text" name="message" placeholder="Type a reply..." style="flex: 1; padding: 8px;" required autocomplete="off">
                            <button type="submit" name="scw_admin_reply" class="button button-primary">Send Reply</button>
                        </form>
                    </div>

                    <script>
                        var chatWindow = document.getElementById('scw-admin-chat-window');
                        chatWindow.scrollTop = chatWindow.scrollHeight;
                    </script>

                <?php else : ?>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #999;">
                        Select a conversation to start chatting
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <?php
}