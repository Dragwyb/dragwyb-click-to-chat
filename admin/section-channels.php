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
// Get saved channel data
$settings = get_option('scw_settings', array());
$channel_data = array();
foreach ($phase1_channels as $slug) {
    $channel_data[$slug] = array(
        'enabled' => isset($settings[$slug . '_enabled']) ? $settings[$slug . '_enabled'] : '0',
        'value' => isset($settings[$slug . '_value']) ? $settings[$slug . '_value'] : '',
        'custom_icon' => isset($settings[$slug . '_custom_icon']) ? $settings[$slug . '_custom_icon'] : '',
        'desktop' => isset($settings[$slug . '_desktop']) ? $settings[$slug . '_desktop'] : '1',
        'mobile' => isset($settings[$slug . '_mobile']) ? $settings[$slug . '_mobile'] : '1',
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
        <!-- Selection Toggle Switch -->
        <label class="scw-card-switch">
             <input type="checkbox" class="scw-card-checkbox" <?php checked($is_active); ?>>
             <span class="scw-card-slider"></span>
        </label>
        
        <div class="scw-channel-icon" style="background: <?php echo esc_attr($channel['color']); ?>;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="white">
                <?php 
                $allowed_svg = array(
                    'path' => array( 'd' => array() ),
                    'svg' => array( 'viewbox' => array(), 'fill' => array(), 'width' => array(), 'height' => array() )
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
        $custom_icon = $channel_data[$slug]['custom_icon'];
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
        
        <!-- Custom Icon Upload -->
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; color: #6b7280;">
                Custom Icon (Optional)
            </label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" class="button button-secondary scw-upload-channel-icon" data-target="<?php echo esc_attr($slug); ?>">
                    Choose Icon
                </button>
                <input type="hidden" id="scw_<?php echo esc_attr($slug); ?>_custom_icon" name="scw_<?php echo esc_attr($slug); ?>_custom_icon" value="<?php echo esc_attr($custom_icon); ?>">
                
                <span class="scw-icon-filename-<?php echo esc_attr($slug); ?>" style="font-size: 13px; color: #6b7280;">
                    <?php echo $custom_icon ? esc_html(basename($custom_icon)) : 'Default Icon'; ?>
                </span>
                
                <?php if ($custom_icon): ?>
                    <button type="button" class="button button-secondary scw-remove-channel-icon" data-target="<?php echo esc_attr($slug); ?>" style="color: #dc2626;">Remove</button>
                <?php else: ?>
                    <button type="button" class="button button-secondary scw-remove-channel-icon" data-target="<?php echo esc_attr($slug); ?>" style="color: #dc2626; display: none;">Remove</button>
                <?php endif; ?>
            </div>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #9ca3af;">
                Upload an SVG or Image to replace the default logo.
            </p>
        </div>
        
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
                               $d_val = $channel_data[$slug]['desktop'];
                               // Default to ON if missing (false) or explicitly '1'
                               checked(($d_val === false || $d_val === '1')); 
                               ?>
                        >
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
                               $m_val = $channel_data[$slug]['mobile'];
                               // Default to ON if missing (false) or explicitly '1'
                               checked(($m_val === false || $m_val === '1')); 
                               ?>
                        >
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
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.scw-channel-card {
    position: relative;
    padding: 24px 15px 20px;
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

/* Card Switch */
.scw-card-switch {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 34px;
    height: 20px;
    z-index: 10;
}

.scw-card-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.scw-card-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    -webkit-transition: .4s;
    transition: .4s;
    border-radius: 34px;
}

.scw-card-slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    -webkit-transition: .4s;
    transition: .4s;
    border-radius: 50%;
}

.scw-card-checkbox:checked + .scw-card-slider {
    background-color: #8e44ad;
}

.scw-card-checkbox:focus + .scw-card-slider {
    box-shadow: 0 0 1px #8e44ad;
}

.scw-card-checkbox:checked + .scw-card-slider:before {
    -webkit-transform: translateX(14px);
    -ms-transform: translateX(14px);
    transform: translateX(14px);
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
    
    // 1. Switch Click Handler (Toggle Logic)
    $('.scw-card-switch').on('click', function(e) {
        // Prevent bubbling to card click
        e.stopPropagation();
        
        // The label/input click propagation will handle the checkbox state change automatically.
        // We just need to capture the change event or let it bubble?
        // Actually, we wrapped it in a div, not a label, so we might need to handle click if touching the checkbox directly works.
        // Let's rely on the change event of the input inside.
    });
    
    // Handle the checkbox change specifically
    $('.scw-card-checkbox').on('change', function(e) {
        const $checkbox = $(this);
        const $card = $checkbox.closest('.scw-channel-card');
        const channel = $card.data('channel');
        const isChecked = $checkbox.is(':checked');
        
        // Update Card UI
        if (isChecked) {
            $card.addClass('active');
        } else {
            $card.removeClass('active');
        }
        
        // Update hidden enabled input
        $('#scw_' + channel + '_enabled').val(isChecked ? '1' : '0');
        
        // Show/hide config section
        const $config = $('[data-channel-input="' + channel + '"]');
        if (isChecked) {
            $config.slideDown(300, function() {
                 // Do not focus automatically on toggle, only on card click? 
                 // Or focus if enabling? User said "i click on the switch then it gets unselectd othr wise on the click of chanel it go to te fild"
                 // Implies: Switch = ON/OFF. Card Body = Focus Field.
                 // So we don't strictly need to focus here, but it's fine if we do or don't.
                 // Let's NOT focal to keep distinction clear.
            });
        } else {
            $config.slideUp(300);
        }
        
        // Update preview
        updateChannelPreview();
    });

    // 2. Card Body Click Handler (Focus/Enable Logic)
    $('.scw-channel-card').on('click', function(e) {
        const $card = $(this);
        const channel = $card.data('channel');
        const $checkbox = $card.find('.scw-card-checkbox');
        const isChecked = $checkbox.is(':checked');
        
        if (!isChecked) {
            // If inactive, activate it first (User intent: "I want to configure this")
            $checkbox.prop('checked', true).trigger('change');
        }
        
        // Focus the input field
        const $config = $('[data-channel-input="' + channel + '"]');
        // Scroll to config if needed?
        $('html, body').animate({
            scrollTop: $config.offset().top - 100
        }, 300);
        
        $config.find('input:not([type="hidden"]):first').focus();
    });
    
    // Function to update preview
    function updateChannelPreview() {
        // Trigger the global update in admin-script.js via a simulated event or just call it if available?
        // admin-script.js listens to 'change input'.
        // We triggered 'change' on the checkbox, so admin-script.js MIGHT pick it up if it listens to .scw-card-checkbox?
        // admin-script.js listens to: 'input[name^="scw_"], select[name^="scw_"]'
        // Our checkbox doesn't have a name starting with scw_ maybe? 
        // Wait, line 73: <input type="checkbox" ...> We didn't give it a name. 
        // WE should give it a name so admin-script.js sees it? 
        // Or just forcefully update.
        
        // Let's trigger a custom event or check if admin-script.js picks it up.
        // admin-script.js logic:
        // $('input[name^="scw_"], ...').on('change', ...)
        // We added .scw-card-checkbox with no name.
        // But we DO update the hidden input: $('#scw_' + channel + '_enabled').val(...)
        // We should trigger change on THAT hidden input.
        
        const channel = $('.scw-channel-card.active').first().data('channel');
        if(channel) {
             const $hidden = $('#scw_' + channel + '_enabled');
             $hidden.trigger('change'); // This will notify listeners
        }
        
        // Also manually call the global SCW_Admin if exposed, or just rely on the hidden input trigger which bubbles.
        // Actually, hidden inputs don't bubble change events automatically when changed via JS. We must trigger it.
        // In the checkbox change handler above, we do: .val(...). We should add .trigger('change').
    }
});
</script>
