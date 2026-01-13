<?php

/**
 * Section 4: Settings
 * Contains current working settings and shortcode information
 */

if (! defined('ABSPATH')) exit;

// $fb_id and $wa_num are set in admin-main.php
// Channels are retrieved dynamically now
?>

<h2 class="dctc-section-title">Settings & Usage</h2>

<!-- Shortcode Info -->
<div class="dctc-info-box" style="background: #f3e8ff; border-left-color: #8e44ad;">
    <p style="color: #5b21b6; font-size: 16px; margin-bottom: 10px;">
        <strong>📋 How to use:</strong>
    </p>
    <p style="color: #5b21b6;">
        To show the chat icons, simply copy and paste this shortcode on any Page or Post:
    </p>
    <p style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">
        <code style="font-size: 16px; padding: 8px 16px; background: #e9d5ff; border-radius: 4px; border: 1px solid #d8b4fe; color: #6b21a8; font-family: monospace;">[social_chat]</code>
        <button type="button" class="dctc-copy-btn" data-clipboard-text="[social_chat]" style="background: white; border: 1px solid #e5e7eb; color: #374151; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500; transition: all 0.2s;">
            <span class="dctc-copy-text">Copy</span>
        </button>
    </p>
</div>

<!-- Current Settings Summary -->
<div style="margin-top: 30px;">
    <h3 style="font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 20px;">
        Current Configuration
    </h3>

    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">

        <?php
        // Get all channels
        $dctc_all_channels = dctc_get_channels();
        $dctc_settings = get_option('dctc_settings', array());

        $dctc_has_configured_channel = false;

        foreach ($dctc_all_channels as $dctc_slug => $dctc_channel) :
            // Check if enabled or has value
            $dctc_is_enabled = isset($dctc_settings[$dctc_slug . '_enabled']) && $dctc_settings[$dctc_slug . '_enabled'] === '1';
            $dctc_value = isset($dctc_settings[$dctc_slug . '_value']) ? $dctc_settings[$dctc_slug . '_value'] : '';

            // Show if enabled OR has value (so user sees what's configured even if disabled)
            if ($dctc_is_enabled || !empty($dctc_value)) :
                $dctc_has_configured_channel = true;
                $dctc_active_color = $dctc_is_enabled ? '#10b981' : '#9ca3af';
                $dctc_status_text = $dctc_is_enabled ? '✓ Active' : '○ Inactive';
        ?>
                <div style="padding: 15px 0; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <!-- Icon -->
                        <div style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="<?php echo esc_attr($dctc_channel['color']); ?>">
                                <?php echo wp_kses($dctc_channel['icon'], array('path' => array('d' => array()))); ?>
                            </svg>
                        </div>

                        <!-- Label & Value -->
                        <div style="flex: 1;">
                            <strong><?php echo esc_html($dctc_channel['label']); ?>:</strong>
                            <span style="color: #6b7280; margin-left: 10px; word-break: break-all;">
                                <?php echo !empty($dctc_value) ? esc_html($dctc_value) : '<em>Not configured</em>'; ?>
                            </span>
                        </div>

                        <!-- Status -->
                        <span style="color: <?php echo esc_attr($dctc_active_color); ?>; font-weight: 600; white-space: nowrap;">
                            <?php echo esc_html($dctc_status_text); ?>
                        </span>
                    </div>
                </div>
            <?php
            endif;
        endforeach;

        if (!$dctc_has_configured_channel) :
            ?>
            <div style="padding: 20px; text-align: center; color: #9ca3af;">
                No channels configured yet. Go to "Select Channels" to get started!
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Additional Settings -->
<div style="margin-top: 30px;">
    <h3 style="font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 20px;">
        Additional Options
    </h3>

    <div class="dctc-info-box">
        <p>🚀 More settings and advanced options will be available in future updates!</p>
    </div>
</div>