<?php
/**
 * Section 1: Select Channels (Dynamic Multi-Channel System)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get all channels from registry
$all_channels = scw_get_channels();

// Phase 1: Top 9 social channels (Live Chat is separate)
$phase1_channels = array('whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin');

// Get saved channel data
$channel_data = array();
foreach ($phase1_channels as $slug) {
    $channel_data[$slug] = array(
        'enabled' => get_option('scw_' . $slug . '_enabled', '0'),
        'value' => get_option('scw_' . $slug . '_value', '')
    );
}
?>

<h2 class="scw-section-title">Choose Your Channels</h2>

<p style="color: #6b7280; margin-bottom: 30px;">
    Select which communication channels you want to display on your website.
</p>

<!-- Channel Selection Grid -->
<div class="scw-channels-grid">
    <?php foreach ($phase1_channels as $slug): 
        $channel = $all_channels[$slug];
        $is_active = $channel_data[$slug]['enabled'] === '1';
    ?>
    <div class="scw-channel-card <?php echo $is_active ? 'active' : ''; ?>" data-channel="<?php echo esc_attr($slug); ?>">
        <div class="scw-channel-icon" style="background: <?php echo esc_attr($channel['color']); ?>;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="white">
                <?php 
                $allowed_svg = array(
                    'path' => array( 'd' => array() ),
                    'svg' => array( 'viewBox' => array(), 'fill' => array(), 'width' => array(), 'height' => array() )
                );
                echo wp_kses($channel['icon'], $allowed_svg); 
                ?>
            </svg>
        </div>
        <div class="scw-channel-name"><?php echo esc_html($channel['name']); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Channel Configuration Section -->
<div style="margin-top: 40px;">
    <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 20px;">Channel Configuration</h3>
    
    <?php foreach ($phase1_channels as $slug): 
        $channel = $all_channels[$slug];
        $is_active = $channel_data[$slug]['enabled'] === '1';
        $value = $channel_data[$slug]['value'];
    ?>
    
    <!-- <?php echo esc_html($channel['name']); ?> Settings -->
    <div class="scw-form-group scw-channel-config" data-channel-input="<?php echo esc_attr($slug); ?>" style="<?php echo !$is_active ? 'display:none;' : ''; ?>">
        <input type="hidden" name="scw_<?php echo esc_attr($slug); ?>_enabled" id="scw_<?php echo esc_attr($slug); ?>_enabled" value="<?php echo esc_attr($channel_data[$slug]['enabled']); ?>">
        
        <?php if ($channel['input_type'] === 'toggle'): ?>
            <!-- Toggle for Live Chat -->
            <label class="scw-toggle">
                <div class="scw-switch">
                    <input type="checkbox" 
                           id="scw_<?php echo esc_attr($slug); ?>_value" 
                           name="scw_<?php echo esc_attr($slug); ?>_value" 
                           value="1" 
                           <?php checked($value, '1'); ?>>
                    <span class="scw-slider"></span>
                </div>
                <span style="margin-left: 10px;"><?php echo esc_html($channel['label']); ?></span>
            </label>
            <p style="color: #9ca3af; font-size: 13px; margin-top: 8px;">
                Enable a popup chat window where visitors can send you messages directly.
            </p>
        <?php else: ?>
            <!-- Input field for other channels -->
            <label for="scw_<?php echo esc_attr($slug); ?>_value" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo esc_attr($channel['color']); ?>">
                    <?php echo wp_kses($channel['icon'], $allowed_svg); ?>
                </svg>
                <span style="font-weight: 500; color: #374151;"><?php echo esc_html($channel['label']); ?></span>
            </label>
            <input type="<?php echo esc_attr($channel['input_type']); ?>" 
                   id="scw_<?php echo esc_attr($slug); ?>_value" 
                   name="scw_<?php echo esc_attr($slug); ?>_value" 
                   value="<?php echo esc_attr($value); ?>" 
                   placeholder="<?php echo esc_attr($channel['placeholder']); ?>"
                   style="width: 100%; max-width: 400px; margin-bottom: 15px;">
        <?php endif; ?>
        
        <!-- Device Visibility Settings -->
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
            <p style="font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 10px;">Device Visibility</p>
            <div style="display: flex; gap: 20px;">
                <label class="scw-toggle">
                    <div class="scw-switch">
                        <input type="checkbox" 
                               id="scw_<?php echo esc_attr($slug); ?>_desktop" 
                               name="scw_<?php echo esc_attr($slug); ?>_desktop" 
                               value="1" 
                               <?php 
                               $d_val = get_option('scw_' . $slug . '_desktop');
                               // Default to ON if missing (false) or explicitly '1'
                               checked(($d_val === false || $d_val === '1')); 
                               ?>>
                        <span class="scw-slider"></span>
                    </div>
                    <span style="margin-left: 10px; font-size: 14px;">Show on Desktop</span>
                </label>
                <label class="scw-toggle">
                    <div class="scw-switch">
                        <input type="checkbox" 
                               id="scw_<?php echo esc_attr($slug); ?>_mobile" 
                               name="scw_<?php echo esc_attr($slug); ?>_mobile" 
                               value="1" 
                               <?php 
                               $m_val = get_option('scw_' . $slug . '_mobile');
                               // Default to ON if missing (false) or explicitly '1'
                               checked(($m_val === false || $m_val === '1')); 
                               ?>>
                        <span class="scw-slider"></span>
                    </div>
                    <span style="margin-left: 10px; font-size: 14px;">Show on Mobile</span>
                </label>
            </div>
        </div>
    </div>
    
    <?php endforeach; ?>
</div>

<style>
.scw-channels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.scw-channel-card {
    padding: 20px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.scw-channel-card:hover {
    border-color: #8e44ad;
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.scw-channel-card.active {
    border-color: #8e44ad;
    background: #f9fafb;
    box-shadow: 0 0 0 1px #8e44ad;
}

.scw-channel-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 12px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.scw-channel-card:hover .scw-channel-icon {
    transform: scale(1.1);
}

.scw-channel-name {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.scw-channel-config {
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    margin-bottom: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Channel card toggle
    $('.scw-channel-card').on('click', function() {
        const $card = $(this);
        const channel = $card.data('channel');
        const isCurrentlyActive = $card.hasClass('active');
        
        // Toggle active state
        $card.toggleClass('active');
        const isNowActive = $card.hasClass('active');
        
        // Update hidden enabled input - important for persistence
        $('#scw_' + channel + '_enabled').val(isNowActive ? '1' : '0');
        
        // Show/hide config section
        const $config = $('[data-channel-input="' + channel + '"]');
        if (isNowActive) {
            $config.slideDown(300, function() {
                // Focus first input
                $config.find('input:not([type="hidden"]):first').focus();
            });
        } else {
            $config.slideUp(300);
        }
        
        // Update live preview
        updateChannelPreview();
    });
    
    // Function to update preview
    function updateChannelPreview() {
        const $preview = $('.scw-preview-device');
        let activeChannels = [];
        
        // Collect active channels
        $('.scw-channel-card.active').each(function() {
            const name = $(this).find('.scw-channel-name').text();
            activeChannels.push(name);
        });
        
        // Update preview content
        if (activeChannels.length > 0) {
            let previewHTML = '<div style="text-align: center; padding: 20px;">';
            previewHTML += '<p style="color: #6b7280; margin-bottom: 15px;">Active Channels:</p>';
            previewHTML += '<div style="display: flex; flex-direction: column; gap: 10px; align-items: center;">';
            
            activeChannels.forEach(function(channel) {
                previewHTML += '<div style="background: #f3f4f6; padding: 8px 16px; border-radius: 20px; font-size: 14px; color: #374151;">' + channel + '</div>';
            });
            
            previewHTML += '</div></div>';
            $preview.html(previewHTML);
        } else {
            $preview.html('<p style="text-align: center; color: #9ca3af; padding: 20px;">Select channels to see preview</p>');
        }
    }
});
</script>
