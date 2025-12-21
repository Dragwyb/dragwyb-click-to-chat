<?php
/**
 * Section 4: Settings
 * Contains current working settings and shortcode information
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$fb_id = get_option('scw_facebook_page_id', '');
$wa_num = get_option('scw_whatsapp_number', '');

?>

<h2 class="scw-section-title">Settings & Usage</h2>

<!-- Shortcode Info -->
<div class="scw-info-box" style="background: #f3e8ff; border-left-color: #8e44ad;">
    <p style="color: #5b21b6; font-size: 16px; margin-bottom: 10px;">
        <strong>📋 How to use:</strong>
    </p>
    <p style="color: #5b21b6;">
        To show the chat icons, simply copy and paste this shortcode on any Page or Post:
    </p>
    <p style="margin-top: 10px;">
        <code style="font-size: 16px; padding: 8px 16px; display: inline-block;">[social_chat]</code>
    </p>
</div>

<!-- Current Settings Summary -->
<div style="margin-top: 30px;">
    <h3 style="font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 20px;">
        Current Configuration
    </h3>
    
    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
        
        <!-- Facebook -->
        <div style="padding: 15px 0; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 40 40" fill="none">
                    <circle cx="20" cy="20" r="20" fill="#1877F2"/>
                    <path d="M27.5 20.094C27.5 15.617 23.883 12 19.406 12C14.929 12 11.312 15.617 11.312 20.094C11.312 24.094 14.119 27.43 17.875 28.094V22.594H16V20.094H17.875V18.094C17.875 16.219 19.281 14.812 21.156 14.812H23.156V17.312H21.281C20.746 17.312 20.281 17.777 20.281 18.312V20.094H23.156V22.594H20.281V28.219C24.426 27.801 27.5 24.336 27.5 20.094Z" fill="white"/>
                </svg>
                <div style="flex: 1;">
                    <strong>Facebook Page ID:</strong>
                    <span style="color: #6b7280; margin-left: 10px;">
                        <?php echo !empty($fb_id) ? esc_html($fb_id) : '<em>Not configured</em>'; ?>
                    </span>
                </div>
                <span style="color: <?php echo !empty($fb_id) ? '#10b981' : '#9ca3af'; ?>; font-weight: 600;">
                    <?php echo !empty($fb_id) ? '✓ Active' : '○ Inactive'; ?>
                </span>
            </div>
        </div>

        <!-- WhatsApp -->
        <div style="padding: 15px 0; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 40 40" fill="none">
                    <circle cx="20" cy="20" r="20" fill="#25D366"/>
                    <path d="M20 11C15.03 11 11 15.03 11 20C11 21.61 11.46 23.11 12.24 24.39L11.5 28.5L15.74 27.78C16.98 28.47 18.43 28.88 20 28.88C24.97 28.88 29 24.85 29 19.88C29 17.46 28.05 15.18 26.36 13.49C24.67 11.8 22.39 10.88 20 10.88V11Z" fill="white"/>
                </svg>
                <div style="flex: 1;">
                    <strong>WhatsApp Number:</strong>
                    <span style="color: #6b7280; margin-left: 10px;">
                        <?php echo !empty($wa_num) ? esc_html($wa_num) : '<em>Not configured</em>'; ?>
                    </span>
                </div>
                <span style="color: <?php echo !empty($wa_num) ? '#10b981' : '#9ca3af'; ?>; font-weight: 600;">
                    <?php echo !empty($wa_num) ? '✓ Active' : '○ Inactive'; ?>
                </span>
            </div>
        </div>



    </div>
</div>

<!-- Additional Settings -->
<div style="margin-top: 30px;">
    <h3 style="font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 20px;">
        Additional Options
    </h3>
    
    <div class="scw-info-box">
        <p>🚀 More settings and advanced options will be available in future updates!</p>
    </div>
</div>
