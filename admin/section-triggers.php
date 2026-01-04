<?php
/**
 * Section 3: Triggers & Targeting
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get current trigger settings
// Get current trigger settings
// $settings is available from admin-main.php
if (!isset($settings)) {
    $settings = get_option('scw_settings', array());
}

$show_on_desktop = isset($settings['show_on_desktop']) ? $settings['show_on_desktop'] : '1';
$show_on_mobile = isset($settings['show_on_mobile']) ? $settings['show_on_mobile'] : '1';
$time_delay = isset($settings['time_delay']) ? $settings['time_delay'] : '0';
?>

<h2 class="scw-section-title">Triggers and Targeting</h2>

<p style="color: #6b7280; margin-bottom: 30px;">
    Control when and where your chat widget appears on your website.
</p>

<!-- Device Visibility -->
<div class="scw-form-group">
    <label>Device Visibility</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose which devices should display the chat widget
    </p>
    
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <label class="scw-toggle">
            <div class="scw-switch">
                <input type="checkbox" 
                       id="scw_show_on_desktop" 
                       name="scw_show_on_desktop" 
                       value="1" 
                       <?php checked($show_on_desktop, '1'); ?>>
                <span class="scw-slider"></span>
            </div>
            <span style="margin-left: 10px; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M16.667 15c.916 0 1.666-.75 1.666-1.667V5c0-.917-.75-1.667-1.666-1.667H3.333c-.916 0-1.666.75-1.666 1.667v8.333c0 .917.75 1.667 1.666 1.667h-2.5a.836.836 0 00-.833.833c0 .459.375.834.833.834h18.334a.836.836 0 00.833-.834.836.836 0 00-.833-.833h-2.5z" fill="currentColor"/>
                </svg>
                Show on Desktop
            </span>
        </label>
        
        <label class="scw-toggle">
            <div class="scw-switch">
                <input type="checkbox" 
                       id="scw_show_on_mobile" 
                       name="scw_show_on_mobile" 
                       value="1" 
                       <?php checked($show_on_mobile, '1'); ?>>
                <span class="scw-slider"></span>
            </div>
            <span style="margin-left: 10px; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M12.916.833H6.25c-1.15 0-2.083.934-2.083 2.084v14.166c0 1.15.933 2.084 2.083 2.084h6.666c1.15 0 2.084-.934 2.084-2.084V2.917c0-1.15-.934-2.084-2.084-2.084zm-3.333 17.5c-.691 0-1.25-.558-1.25-1.25 0-.691.559-1.25 1.25-1.25.692 0 1.25.559 1.25 1.25 0 .692-.558 1.25-1.25 1.25z" fill="currentColor"/>
                </svg>
                Show on Mobile
            </span>
        </label>
    </div>
</div>

<!-- Time Delay -->
<div class="scw-form-group">
    <label for="scw_time_delay">Time Delay</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Delay widget appearance after page load (in seconds)
    </p>
    
    <div style="display: flex; align-items: center; gap: 15px;">
        <input type="number" 
               id="scw_time_delay" 
               name="scw_time_delay" 
               min="0" 
               max="60" 
               value="<?php echo esc_attr($time_delay); ?>"
               style="width: 100px;">
        <span style="color: #6b7280;">seconds</span>
    </div>
    
    <p style="color: #9ca3af; font-size: 12px; margin-top: 8px;">
        Set to 0 for immediate display. Recommended: 2-5 seconds for better user experience.
    </p>
</div>

