<?php
/**
 * Admin Settings Page Logic
 *
 * @package Social_Chat_Widget
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'scw_add_admin_menu' );
add_action( 'wp_ajax_scw_save_settings', 'scw_save_settings_ajax' );

function scw_add_admin_menu() {
    add_options_page( 'Social Chat Settings', 'Social Chat', 'manage_options', 'social-chat-widget', 'scw_render_settings_page' );
}

function scw_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Get Values
    $fb_id    = get_option( 'scw_facebook_page_id', '' );
    $wa_num   = get_option( 'scw_whatsapp_number', '' );
    $tg_user  = get_option( 'scw_telegram_username', '' );
    
    // Visibility Values
    $vis_mode = get_option( 'scw_visibility_mode', 'all' ); 
    $vis_home = get_option( 'scw_vis_home', '0' );
    $vis_post = get_option( 'scw_vis_post', '0' );
    
    // Get Saved Specific Pages (Array of IDs)
    $vis_specific_pages = get_option( 'scw_vis_specific_pages', array() );
    if( ! is_array( $vis_specific_pages ) ) $vis_specific_pages = array();

    // Fetch All WordPress Pages for the list
    $all_pages = get_pages();
    ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Social Chat Widget Settings', 'social-chat-widget' ); ?></h1>

        <form id="scw-settings-form">
            
            <div class="scw-card">
                <h2><?php esc_html_e( '1. Contact Channels', 'social-chat-widget' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label>Facebook Page ID</label></th>
                        <td><input name="scw_facebook_page_id" type="text" id="scw_facebook_page_id" value="<?php echo esc_attr( $fb_id ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>WhatsApp Number</label></th>
                        <td><input name="scw_whatsapp_number" type="text" id="scw_whatsapp_number" value="<?php echo esc_attr( $wa_num ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Telegram Username</label></th>
                        <td><input name="scw_telegram_username" type="text" id="scw_telegram_username" value="<?php echo esc_attr( $tg_user ); ?>" class="regular-text"></td>
                    </tr>
                </table>
            </div>

            <div class="scw-card">
                <h2><?php esc_html_e( '2. Visibility Settings', 'social-chat-widget' ); ?></h2>
                <p><?php esc_html_e( 'Where do you want the chat widget to appear?', 'social-chat-widget' ); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th><label>Display Mode</label></th>
                        <td>
                            <select id="scw_visibility_mode">
                                <option value="all" <?php selected( $vis_mode, 'all' ); ?>>Show on All Pages</option>
                                <option value="custom" <?php selected( $vis_mode, 'custom' ); ?>>Custom Settings</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div id="scw-custom-options" style="margin-left: 220px; display: <?php echo ($vis_mode === 'custom') ? 'block' : 'none'; ?>;">
                    <fieldset>
                        <legend class="screen-reader-text"><span>Custom Settings</span></legend>
                        
                        <label style="font-weight: bold;">
                            <input type="checkbox" id="scw_vis_home" value="1" <?php checked( $vis_home, '1' ); ?>> 
                            Homepage
                        </label><br>
                        
                        <label style="font-weight: bold;">
                            <input type="checkbox" id="scw_vis_post" value="1" <?php checked( $vis_post, '1' ); ?>> 
                            Single Blog Posts
                        </label><br><br>

                        <label style="font-weight: bold;">Select Specific Pages:</label>
                        <div class="scw-scroll-box">
                            <?php if ( $all_pages ) : ?>
                                <?php foreach ( $all_pages as $page ) : ?>
                                    <label>
                                        <input type="checkbox" class="scw-page-checkbox" value="<?php echo $page->ID; ?>" <?php checked( in_array( $page->ID, $vis_specific_pages ) ); ?>> 
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <p>No pages found.</p>
                            <?php endif; ?>
                        </div>

                    </fieldset>
                </div>
            </div>

            <div class="scw-submit-section">
                <button type="button" id="scw-save-btn" class="button button-primary button-large">Save Changes</button>
                <span id="scw-spinner" class="spinner"></span>
                <span id="scw-message" style="font-weight: bold; margin-left: 10px; display: none;"></span>
            </div>

        </form>
    </div>

    <style>
        .scw-card { background: #fff; border: 1px solid #c3c4c7; padding: 20px; max-width: 800px; margin-top: 20px; }
        .scw-scroll-box { max-height: 150px; overflow-y: scroll; border: 1px solid #ddd; padding: 10px; margin-top: 5px; background: #f9f9f9; }
        .scw-submit-section { margin-top: 20px; display: flex; align-items: center; }
        .scw-spinner.is-active { visibility: visible; }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Toggle Custom Options
        $('#scw_visibility_mode').change(function() {
            if($(this).val() === 'custom') {
                $('#scw-custom-options').slideDown();
            } else {
                $('#scw-custom-options').slideUp();
            }
        });

        // Save Logic
        $('#scw-save-btn').on('click', function(e) {
            e.preventDefault();
            var btn = $(this);
            var spinner = $('#scw-spinner');
            var msg = $('#scw-message');

            // Collect Selected Pages
            var selectedPages = [];
            $('.scw-page-checkbox:checked').each(function() {
                selectedPages.push($(this).val());
            });

            btn.prop('disabled', true);
            spinner.addClass('is-active');
            msg.hide();

            $.post(ajaxurl, {
                action: 'scw_save_settings',
                nonce: '<?php echo wp_create_nonce( 'scw_settings_nonce' ); ?>',
                // Data
                facebook_page_id: $('#scw_facebook_page_id').val(),
                whatsapp_number: $('#scw_whatsapp_number').val(),
                telegram_username: $('#scw_telegram_username').val(),
                // Visibility Data
                visibility_mode: $('#scw_visibility_mode').val(),
                vis_home: $('#scw_vis_home').is(':checked') ? '1' : '0',
                vis_post: $('#scw_vis_post').is(':checked') ? '1' : '0',
                vis_specific_pages: selectedPages 

            }, function(response) {
                btn.prop('disabled', false);
                spinner.removeClass('is-active');
                if (response.success) {
                    msg.css('color', 'green').text(response.data).fadeIn();
                } else {
                    msg.css('color', 'red').text(response.data).fadeIn();
                }
            });
        });
    });
    </script>
    <?php
}

function scw_save_settings_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'scw_settings_nonce' ) ) wp_send_json_error( 'Security failed' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

    // Save Channels
    update_option( 'scw_facebook_page_id', sanitize_text_field( $_POST['facebook_page_id'] ) );
    update_option( 'scw_whatsapp_number', sanitize_text_field( $_POST['whatsapp_number'] ) );
    update_option( 'scw_telegram_username', sanitize_text_field( $_POST['telegram_username'] ) );

    // Save Visibility
    update_option( 'scw_visibility_mode', sanitize_text_field( $_POST['visibility_mode'] ) );
    update_option( 'scw_vis_home', sanitize_text_field( $_POST['vis_home'] ) );
    update_option( 'scw_vis_post', sanitize_text_field( $_POST['vis_post'] ) );
    
    // Save Specific Pages (Array)
    $pages = isset($_POST['vis_specific_pages']) ? array_map('intval', $_POST['vis_specific_pages']) : array();
    update_option( 'scw_vis_specific_pages', $pages );

    wp_send_json_success( 'Settings Saved!' );
}