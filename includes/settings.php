<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'scw_add_settings_page' );
add_action( 'wp_ajax_scw_save_settings', 'scw_save_settings' );

function scw_add_settings_page() {
    add_menu_page( 'Social Chat', 'Social Chat', 'manage_options', 'social-chat-widget', 'scw_settings_page_html', 'dashicons-format-chat', 90 );
}

function scw_settings_page_html() {
    $fb_id = get_option('scw_facebook_page_id');
    $wa_num = get_option('scw_whatsapp_number');
    $live_chat = get_option('scw_enable_live_chat');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Social Chat Settings', 'social-chat-widget' ); ?></h1>
        
        <div style="background: #fff; border-left: 4px solid #8e44ad; padding: 15px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0;"><?php esc_html_e( 'How to use:', 'social-chat-widget' ); ?></h2>
            <p style="font-size: 16px;">
                <?php esc_html_e( 'To show the chat icons, simply copy and paste this shortcode on any Page or Post:', 'social-chat-widget' ); ?>
            </p>
            <code style="font-size: 18px; background: #f0f0f1; padding: 10px; display: block; margin-top: 10px; width: fit-content;">[social_chat]</code>
        </div>

        <div style="background: #fff; padding: 20px; border: 1px solid #c3c4c7;">
            <form id="scw-form">
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Facebook Page ID', 'social-chat-widget' ); ?></th>
                        <td><input type="text" id="fb" value="<?php echo esc_attr($fb_id); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'WhatsApp Number', 'social-chat-widget' ); ?></th>
                        <td><input type="text" id="wa" value="<?php echo esc_attr($wa_num); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Live Chat Window', 'social-chat-widget' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="lc" <?php checked($live_chat, '1'); ?>> 
                                <?php esc_html_e( 'Enable Internal Live Chat', 'social-chat-widget' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="button" id="btn-save" class="button button-primary button-large"><?php esc_html_e( 'Save Changes', 'social-chat-widget' ); ?></button>
                    <span id="msg" style="margin-left: 10px; font-weight: bold;"></span>
                </p>
            </form>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#btn-save').click(function() {
                var btn = $(this);
                var msg = $('#msg');
                btn.prop('disabled', true);
                msg.text('');

                $.post(ajaxurl, {
                    action: 'scw_save_settings',
                    nonce: '<?php echo wp_create_nonce('scw_nonce'); ?>',
                    fb: $('#fb').val(),
                    wa: $('#wa').val(),
                    lc: $('#lc').is(':checked') ? '1' : '0'
                }, function(response) {
                    btn.prop('disabled', false);
                    if (response.success) {
                        msg.css('color', 'green').text('Saved!');
                        setTimeout(function(){ msg.fadeOut(); }, 2000);
                    }
                });
            });
        });
    </script>
    <?php
}

function scw_save_settings() {
    check_ajax_referer('scw_nonce', 'nonce');
    if(!current_user_can('manage_options')) return;
    update_option('scw_facebook_page_id', sanitize_text_field($_POST['fb']));
    update_option('scw_whatsapp_number', sanitize_text_field($_POST['wa']));
    update_option('scw_enable_live_chat', sanitize_text_field($_POST['lc']));
    wp_send_json_success();
}