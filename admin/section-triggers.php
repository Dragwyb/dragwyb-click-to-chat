<?php
/**
 * Section 3: Triggers & Targeting
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// $settings is available from admin-main.php
if ( ! isset( $settings ) ) {
    $settings = get_option( 'scw_settings', array() );
}

$show_on_desktop = isset( $settings['show_on_desktop'] ) ? $settings['show_on_desktop'] : '1';
$show_on_mobile  = isset( $settings['show_on_mobile'] ) ? $settings['show_on_mobile'] : '1';
$time_delay      = isset( $settings['time_delay'] ) ? $settings['time_delay'] : '0';

// Display rules
$display_mode       = isset( $settings['display_mode'] ) ? $settings['display_mode'] : 'all';
$display_post_types = isset( $settings['display_post_types'] ) ? $settings['display_post_types'] : array();

// Get public post types (exclude attachment)
$post_types = get_post_types( array( 'public' => true ), 'objects' );
if ( isset( $post_types['attachment'] ) ) {
    unset( $post_types['attachment'] );
}
?>

<h2 class="scw-section-title">
    <?php esc_html_e( 'Triggers and Targeting', 'social-chat-widget' ); ?>
</h2>

<p style="color:#6b7280; margin-bottom:30px;">
    <?php esc_html_e(
        'Control when and where your chat widget appears on your website.',
        'social-chat-widget'
    ); ?>
</p>

<!-- Display Rules -->
<div class="scw-form-group">
    <label>
        <?php esc_html_e( 'Display Rules', 'social-chat-widget' ); ?>
    </label>

    <p style="color:#9ca3af; font-size:13px; margin-bottom:20px;">
        <?php esc_html_e(
            'Choose where the chat widget should appear.',
            'social-chat-widget'
        ); ?>
    </p>

    <div style="display:flex; flex-direction:column; gap:15px;">

        <!-- All Pages -->
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="radio"
                   name="scw_display_mode"
                   value="all"
                   <?php checked( $display_mode, 'all' ); ?>
                   style="margin-top:4px;">
            <div>
                <span style="display:block; font-weight:500; font-size:14px; color:#374151;">
                    <?php esc_html_e( 'All Pages', 'social-chat-widget' ); ?>
                </span>
                <span style="font-size:13px; color:#6b7280;">
                    <?php esc_html_e(
                        'Show on every page of your website',
                        'social-chat-widget'
                    ); ?>
                </span>
            </div>
        </label>

        <!-- Specific Post Types -->
        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
            <input type="radio"
                   name="scw_display_mode"
                   value="post_types"
                   <?php checked( $display_mode, 'post_types' ); ?>
                   style="margin-top:4px;">
            <div>
                <span style="display:block; font-weight:500; font-size:14px; color:#374151;">
                    <?php esc_html_e( 'Specific Post Types', 'social-chat-widget' ); ?>
                </span>
                <span style="font-size:13px; color:#6b7280;">
                    <?php esc_html_e(
                        'Show only on selected content types',
                        'social-chat-widget'
                    ); ?>
                </span>
            </div>
        </label>

        <!-- Post Types List -->
        <div id="scw-post-types-list"
             style="margin-left:25px; padding:15px; background:#f9fafb; border-radius:6px; border:1px solid #e5e7eb; display:<?php echo $display_mode === 'post_types' ? 'block' : 'none'; ?>;">
            <?php foreach ( $post_types as $pt_slug => $pt_obj ) : ?>
                <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:13px;">
                    <input type="checkbox"
                           name="scw_display_post_types[]"
                           value="<?php echo esc_attr( $pt_slug ); ?>"
                           <?php checked( in_array( $pt_slug, $display_post_types, true ) ); ?>>
                    <?php echo esc_html( $pt_obj->labels->name ); ?>
                </label>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Device Visibility -->
<div class="scw-form-group">
    <label>
        <?php esc_html_e( 'Device Visibility', 'social-chat-widget' ); ?>
    </label>

    <p style="color:#9ca3af; font-size:13px; margin-bottom:15px;">
        <?php esc_html_e(
            'Choose which devices should display the chat widget',
            'social-chat-widget'
        ); ?>
    </p>

    <div style="display:flex; flex-direction:column; gap:15px;">

        <label class="scw-toggle">
            <div class="scw-switch">
                <input type="checkbox"
                       id="scw_show_on_desktop"
                       name="scw_show_on_desktop"
                       value="1"
                       <?php checked( $show_on_desktop, '1' ); ?>>
                <span class="scw-slider"></span>
            </div>
            <span style="margin-left:10px;">
                <?php esc_html_e( 'Show on Desktop', 'social-chat-widget' ); ?>
            </span>
        </label>

        <label class="scw-toggle">
            <div class="scw-switch">
                <input type="checkbox"
                       id="scw_show_on_mobile"
                       name="scw_show_on_mobile"
                       value="1"
                       <?php checked( $show_on_mobile, '1' ); ?>>
                <span class="scw-slider"></span>
            </div>
            <span style="margin-left:10px;">
                <?php esc_html_e( 'Show on Mobile', 'social-chat-widget' ); ?>
            </span>
        </label>

    </div>
</div>

<!-- Time Delay -->
<div class="scw-form-group">
    <label for="scw_time_delay">
        <?php esc_html_e( 'Time Delay', 'social-chat-widget' ); ?>
    </label>

    <p style="color:#9ca3af; font-size:13px; margin-bottom:15px;">
        <?php esc_html_e(
            'Delay widget appearance after page load (in seconds)',
            'social-chat-widget'
        ); ?>
    </p>

    <div style="display:flex; align-items:center; gap:15px;">
        <input type="number"
               id="scw_time_delay"
               name="scw_time_delay"
               min="0"
               max="60"
               value="<?php echo esc_attr( $time_delay ); ?>"
               style="width:100px;">
        <span style="color:#6b7280;">
            <?php esc_html_e( 'seconds', 'social-chat-widget' ); ?>
        </span>
    </div>

    <p style="color:#9ca3af; font-size:12px; margin-top:8px;">
        <?php esc_html_e(
            'Set to 0 for immediate display. Recommended: 2–5 seconds for better user experience.',
            'social-chat-widget'
        ); ?>
    </p>
</div>
