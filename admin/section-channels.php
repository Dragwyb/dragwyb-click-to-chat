<?php
/**
 * Section 1: Select Channels
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get channel data
$fb_id = get_option('scw_facebook_page_id', '');
$wa_num = get_option('scw_whatsapp_number', '');
$live_chat = get_option('scw_enable_live_chat', '0');

// Get channel enabled states (separate from data)
$fb_enabled = get_option('scw_facebook_enabled', !empty($fb_id) ? '1' : '0');
$wa_enabled = get_option('scw_whatsapp_enabled', !empty($wa_num) ? '1' : '0');
?>

<h2 class="scw-section-title">Choose Your Channels</h2>

<p style="color: #6b7280; margin-bottom: 30px;">
    Select which social media and chat channels you want to display on your website.
</p>

<!-- Channel Selection Cards -->
<div class="scw-channels">
    
    <!-- Facebook Card -->
    <div class="scw-channel-card <?php echo $fb_enabled === '1' ? 'active' : ''; ?>" data-channel="facebook">
        <div class="scw-channel-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                <circle cx="20" cy="20" r="20" fill="#1877F2"/>
                <path d="M27.5 20.094C27.5 15.617 23.883 12 19.406 12C14.929 12 11.312 15.617 11.312 20.094C11.312 24.094 14.119 27.43 17.875 28.094V22.594H16V20.094H17.875V18.094C17.875 16.219 19.281 14.812 21.156 14.812H23.156V17.312H21.281C20.746 17.312 20.281 17.777 20.281 18.312V20.094H23.156V22.594H20.281V28.219C24.426 27.801 27.5 24.336 27.5 20.094Z" fill="white"/>
            </svg>
        </div>
        <div class="scw-channel-name">Facebook</div>
    </div>

    <!-- WhatsApp Card -->
    <div class="scw-channel-card <?php echo $wa_enabled === '1' ? 'active' : ''; ?>" data-channel="whatsapp">
        <div class="scw-channel-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                <circle cx="20" cy="20" r="20" fill="#25D366"/>
                <path d="M20 11C15.03 11 11 15.03 11 20C11 21.61 11.46 23.11 12.24 24.39L11.5 28.5L15.74 27.78C16.98 28.47 18.43 28.88 20 28.88C24.97 28.88 29 24.85 29 19.88C29 17.46 28.05 15.18 26.36 13.49C24.67 11.8 22.39 10.88 20 10.88V11ZM20 12.67C24.03 12.67 27.33 15.97 27.33 20C27.33 24.03 24.03 27.33 20 27.33C18.68 27.33 17.42 26.98 16.32 26.36L15.94 26.14L13.14 26.83L13.84 24.09L13.6 23.69C12.92 22.56 12.55 21.25 12.55 19.88C12.55 15.85 15.85 12.55 19.88 12.55L20 12.67ZM17.91 16.08C17.76 16.08 17.5 16.14 17.28 16.43C17.07 16.72 16.44 17.31 16.44 18.5C16.44 19.69 17.3 20.84 17.43 21C17.56 21.16 19.17 23.69 21.69 24.72C23.82 25.59 24.22 25.42 24.64 25.39C25.06 25.36 26.06 24.8 26.27 24.21C26.48 23.62 26.48 23.12 26.42 23.01C26.36 22.9 26.2 22.84 25.96 22.72C25.72 22.6 24.54 22.01 24.32 21.93C24.1 21.85 23.94 21.81 23.78 22.05C23.62 22.29 23.18 22.84 23.04 23C22.9 23.16 22.76 23.18 22.52 23.06C22.28 22.94 21.53 22.69 20.64 21.91C19.95 21.3 19.5 20.55 19.36 20.31C19.22 20.07 19.34 19.94 19.46 19.82C19.57 19.71 19.7 19.54 19.82 19.4C19.94 19.26 19.98 19.16 20.06 19C20.14 18.84 20.1 18.7 20.04 18.58C19.98 18.46 19.54 17.27 19.34 16.79C19.14 16.31 18.94 16.38 18.8 16.37C18.66 16.36 18.5 16.36 18.34 16.36C18.18 16.36 17.91 16.42 17.69 16.66C17.47 16.9 16.85 17.49 16.85 18.68C16.85 19.87 17.71 21.02 17.83 21.18C17.95 21.34 19.54 23.87 22.02 24.9C24.5 25.93 24.5 25.59 25 25.55C25.5 25.51 26.5 24.95 26.71 24.36C26.92 23.77 26.92 23.27 26.86 23.16C26.8 23.05 26.64 22.99 26.4 22.87L17.91 16.08Z" fill="white"/>
            </svg>
        </div>
        <div class="scw-channel-name">WhatsApp</div>
    </div>

    <!-- Live Chat Card -->
    <div class="scw-channel-card <?php echo $live_chat === '1' ? 'active' : ''; ?>" data-channel="livechat">
        <div class="scw-channel-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                <circle cx="20" cy="20" r="20" fill="#8e44ad"/>
                <path d="M28 14C28 12.9 27.1 12 26 12H14C12.9 12 12 12.9 12 14V22C12 23.1 12.9 24 14 24H24L28 28V14Z" fill="white"/>
            </svg>
        </div>
        <div class="scw-channel-name">Live Chat</div>
    </div>

</div>

<!-- Channel Settings -->
<div style="margin-top: 40px;">
    
    <!-- Hidden inputs to track channel enabled states -->
    <input type="hidden" id="scw_facebook_enabled" name="scw_facebook_enabled" value="<?php echo esc_attr($fb_enabled); ?>">
    <input type="hidden" id="scw_whatsapp_enabled" name="scw_whatsapp_enabled" value="<?php echo esc_attr($wa_enabled); ?>">
    
    <!-- Facebook Settings -->
    <div class="scw-form-group" data-channel-input="facebook" style="<?php echo $fb_enabled !== '1' ? 'display:none;' : ''; ?>">
        <label for="scw_facebook_page_id">
            Facebook Page ID
            <span style="color: #9ca3af; font-weight: normal;">(e.g., YourPageName or 123456789)</span>
        </label>
        <input type="text" 
               id="scw_facebook_page_id" 
               name="scw_facebook_page_id" 
               value="<?php echo esc_attr($fb_id); ?>" 
               placeholder="Enter your Facebook Page ID">
    </div>

    <!-- WhatsApp Settings -->
    <div class="scw-form-group" data-channel-input="whatsapp" style="<?php echo $wa_enabled !== '1' ? 'display:none;' : ''; ?>">
        <label for="scw_whatsapp_number">
            WhatsApp Number
            <span style="color: #9ca3af; font-weight: normal;">(Include country code, e.g., +1234567890)</span>
        </label>
        <input type="tel" 
               id="scw_whatsapp_number" 
               name="scw_whatsapp_number" 
               value="<?php echo esc_attr($wa_num); ?>" 
               placeholder="+1234567890">
    </div>

    <!-- Live Chat Settings -->
    <div class="scw-form-group" data-channel-input="livechat" style="<?php echo $live_chat !== '1' ? 'display:none;' : ''; ?>">
        <label class="scw-toggle">
            <div class="scw-switch">
                <input type="checkbox" 
                       id="scw_enable_live_chat" 
                       name="scw_enable_live_chat" 
                       value="1" 
                       <?php checked($live_chat, '1'); ?>>
                <span class="scw-slider"></span>
            </div>
            <span style="margin-left: 10px;">Enable Internal Live Chat Window</span>
        </label>
        <p style="color: #9ca3af; font-size: 13px; margin-top: 8px;">
            Enable a popup chat window where visitors can send you messages directly from your website.
        </p>
    </div>

</div>
