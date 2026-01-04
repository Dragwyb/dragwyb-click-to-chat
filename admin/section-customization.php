<?php
/**
 * Section 2: Widget Customization
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get current widget settings
$settings = get_option('scw_settings', array());

$widget_position = isset($settings['widget_position']) ? $settings['widget_position'] : 'right';
$widget_color = isset($settings['widget_color']) ? $settings['widget_color'] : '#8e44ad';
$widget_size = isset($settings['widget_size']) ? $settings['widget_size'] : '60';
$custom_bottom = isset($settings['custom_bottom']) ? $settings['custom_bottom'] : '20';
$custom_horizontal = isset($settings['custom_horizontal']) ? $settings['custom_horizontal'] : '20';
$custom_side = isset($settings['custom_side']) ? $settings['custom_side'] : 'right'; // 'left' or 'right'
?>

<h2 class="scw-section-title">Customize Your Widget</h2>

<p style="color: #6b7280; margin-bottom: 30px;">
    Customize the appearance and position of your chat widget to match your website design.
</p>

<!-- Widget Position -->
<div class="scw-form-group">
    <label>Widget Position</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose where the chat widget appears on your website
    </p>
    <div class="scw-position-selector">
        <label class="scw-position-option <?php echo $widget_position === 'left' ? 'active' : ''; ?>">
            <input type="radio" name="scw_widget_position" value="left" <?php checked($widget_position, 'left'); ?>>
            <div class="position-card">
                <svg width="60" height="40" viewBox="0 0 60 40" fill="none">
                    <rect width="60" height="40" rx="4" fill="#F3F4F6"/>
                    <circle cx="10" cy="30" r="5" fill="#8e44ad"/>
                </svg>
                <span>Bottom Left</span>
            </div>
        </label>
        
        <label class="scw-position-option <?php echo $widget_position === 'right' ? 'active' : ''; ?>">
            <input type="radio" name="scw_widget_position" value="right" <?php checked($widget_position, 'right'); ?>>
            <div class="position-card">
                <svg width="60" height="40" viewBox="0 0 60 40" fill="none">
                    <rect width="60" height="40" rx="4" fill="#F3F4F6"/>
                    <circle cx="50" cy="30" r="5" fill="#8e44ad"/>
                </svg>
                <span>Bottom Right</span>
            </div>
        </label>
        
        <label class="scw-position-option <?php echo $widget_position === 'custom' ? 'active' : ''; ?>">
            <input type="radio" name="scw_widget_position" value="custom" <?php checked($widget_position, 'custom'); ?>>
            <div class="position-card">
                <svg width="60" height="40" viewBox="0 0 60 40" fill="none">
                    <rect width="60" height="40" rx="4" fill="#F3F4F6"/>
                    <path d="M25 15 L35 15 L30 10 Z" fill="#8e44ad"/>
                    <path d="M25 25 L35 25 L30 30 Z" fill="#8e44ad"/>
                    <path d="M15 20 L20 25 L20 15 Z" fill="#8e44ad"/>
                    <path d="M40 20 L35 25 L35 15 Z" fill="#8e44ad"/>
                </svg>
                <span>Custom</span>
            </div>
        </label>
    </div>
    
    <!-- Custom Position Settings -->
    <div id="custom-position-settings" style="margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; <?php echo $widget_position !== 'custom' ? 'display: none;' : ''; ?>">
        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #374151;">Custom Position Settings</h4>
        
        <?php 
        $custom_vertical_align = isset($settings['custom_vertical_align']) ? $settings['custom_vertical_align'] : 'bottom'; 
        $custom_bottom_unit = isset($settings['custom_bottom_unit']) ? $settings['custom_bottom_unit'] : 'px';
        $custom_horizontal_unit = isset($settings['custom_horizontal_unit']) ? $settings['custom_horizontal_unit'] : 'px';
        ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Vertical Axis -->
            <div>
                 <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Vertical Alignment
                </label>
                <select id="scw_custom_vertical_align" name="scw_custom_vertical_align" style="width: 150px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 15px;">
                    <option value="bottom" <?php selected($custom_vertical_align, 'bottom'); ?>>Bottom</option>
                    <option value="top" <?php selected($custom_vertical_align, 'top'); ?>>Top</option>
                </select>
                
                <label for="scw_custom_bottom" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Vertical Distance
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" 
                           id="scw_custom_bottom" 
                           name="scw_custom_bottom" 
                           value="<?php echo esc_attr($custom_bottom); ?>"
                           min="0"
                           step="0.1"
                           style="width: 80px;">
                    <select id="scw_custom_bottom_unit" name="scw_custom_bottom_unit" style="padding: 0 5px; height: 30px; width: 45px">
                        <option value="px" <?php selected($custom_bottom_unit, 'px'); ?>>px</option>
                        <option value="rem" <?php selected($custom_bottom_unit, 'rem'); ?>>rem</option>
                        <option value="em" <?php selected($custom_bottom_unit, 'em'); ?>>em</option>
                        <option value="%" <?php selected($custom_bottom_unit, '%'); ?>>%</option>
                    </select>
                </div>
            </div>
            
            <!-- Horizontal Axis -->
            <div>
                <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Horizontal Alignment
                </label>
                <select id="scw_custom_side" name="scw_custom_side" style="width: 150px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; margin-bottom: 15px;">
                    <option value="right" <?php selected($custom_side, 'right'); ?>>Right</option>
                    <option value="left" <?php selected($custom_side, 'left'); ?>>Left</option>
                </select>
                
                <label for="scw_custom_horizontal" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                    Horizontal Distance
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" 
                           id="scw_custom_horizontal" 
                           name="scw_custom_horizontal" 
                           value="<?php echo esc_attr($custom_horizontal); ?>"
                           min="0"
                           step="0.1"
                           style="width: 80px;">
                    <select id="scw_custom_horizontal_unit" name="scw_custom_horizontal_unit" style="padding: 0 5px; height: 30px; width: 45px">
                        <option value="px" <?php selected($custom_horizontal_unit, 'px'); ?>>px</option>
                        <option value="rem" <?php selected($custom_horizontal_unit, 'rem'); ?>>rem</option>
                        <option value="em" <?php selected($custom_horizontal_unit, 'em'); ?>>em</option>
                        <option value="%" <?php selected($custom_horizontal_unit, '%'); ?>>%</option>
                    </select>
                </div>
            </div>
        </div>
        
        <p style="margin: 15px 0 0 0; font-size: 12px; color: #9ca3af;">
            💡 Tip: Use custom positioning to place the widget exactly where you want it on your page.
        </p>
    </div>
</div>

<!-- Widget Color -->
<div class="scw-form-group">
    <label for="scw_widget_color">Widget Color</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose a color that matches your brand
    </p>
    <div style="display: flex; align-items: center; gap: 15px;">
        <input type="text" 
               id="scw_widget_color" 
               name="scw_widget_color" 
               value="<?php echo esc_attr($widget_color); ?>" 
               class="scw-color-picker"
               data-default-color="#8e44ad">
        <span class="scw-color-preview" style="width: 40px; height: 40px; border-radius: 6px; border: 2px solid #e5e7eb; background-color: <?php echo esc_attr($widget_color); ?>;"></span>
    </div>
</div>

<!-- Widget Size -->
<?php $widget_size_unit = isset($settings['widget_size_unit']) ? $settings['widget_size_unit'] : 'px'; ?>
<div class="scw-form-group">
    <label for="scw_widget_size">Widget Size</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Adjust the size of the chat button
    </p>
    <div style="display: flex; align-items: center; gap: 15px;">
        <input type="number" 
               id="scw_widget_size" 
               name="scw_widget_size" 
               min="10" 
               step="0.1"
               value="<?php echo esc_attr($widget_size); ?>"
               style="width: 80px;">
        
        <select id="scw_widget_size_unit" name="scw_widget_size_unit" style="padding: 0 5px; height: 30px; width: 45px">
            <option value="px" <?php selected($widget_size_unit, 'px'); ?>>px</option>
            <option value="rem" <?php selected($widget_size_unit, 'rem'); ?>>rem</option>
            <option value="em" <?php selected($widget_size_unit, 'em'); ?>>em</option>
            <option value="%" <?php selected($widget_size_unit, '%'); ?>>%</option>
        </select>
    </div>
</div>

<?php
// Get icon settings
$icon_type = isset($settings['icon_type']) ? $settings['icon_type'] : 'chat';
$custom_icon_url = isset($settings['custom_icon_url']) ? $settings['custom_icon_url'] : '';
$icon_rotation = isset($settings['icon_rotation']) ? $settings['icon_rotation'] : '0';
$icon_scale = isset($settings['icon_scale']) ? $settings['icon_scale'] : '1';
?>

<!-- Widget Icon -->
<div class="scw-form-group">
    <label>Widget Icon</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Choose an icon for your chat button
    </p>
    
    <!-- Icon Type Selector -->
    <div class="scw-icon-selector" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <label class="scw-icon-option <?php echo $icon_type === 'chat' ? 'active' : ''; ?>">
            <input type="radio" name="scw_icon_type" value="chat" <?php checked($icon_type, 'chat'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                </svg>
                <span>Chat</span>
            </div>
        </label>
        
        <label class="scw-icon-option <?php echo $icon_type === 'message' ? 'active' : ''; ?>">
            <input type="radio" name="scw_icon_type" value="message" <?php checked($icon_type, 'message'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <span>Message</span>
            </div>
        </label>
        
        <label class="scw-icon-option <?php echo $icon_type === 'support' ? 'active' : ''; ?>">
            <input type="radio" name="scw_icon_type" value="support" <?php checked($icon_type, 'support'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                <span>Support</span>
            </div>
        </label>
        
        <label class="scw-icon-option <?php echo $icon_type === 'phone' ? 'active' : ''; ?>">
            <input type="radio" name="scw_icon_type" value="phone" <?php checked($icon_type, 'phone'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                </svg>
                <span>Phone</span>
            </div>
        </label>
        
        <label class="scw-icon-option <?php echo $icon_type === 'custom' ? 'active' : ''; ?>">
            <input type="radio" name="scw_icon_type" value="custom" <?php checked($icon_type, 'custom'); ?>>
            <div class="icon-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
                <span>Custom</span>
            </div>
        </label>
    </div>
    
    <!-- Custom Icon Upload -->
    <div id="custom-icon-upload" style="margin-top: 15px; padding: 15px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; <?php echo $icon_type !== 'custom' ? 'display: none;' : ''; ?>">
        <label style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
            Upload Custom Icon (SVG recommended)
        </label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" id="upload-icon-button" class="button button-secondary">
                Choose Icon
            </button>
            <input type="hidden" id="scw_custom_icon_url" name="scw_custom_icon_url" value="<?php echo esc_attr($custom_icon_url); ?>">
            <span id="icon-filename" style="font-size: 13px; color: #6b7280;">
                <?php echo $custom_icon_url ? esc_html(basename($custom_icon_url)) : 'No icon selected'; ?>
            </span>
            <?php if ($custom_icon_url): ?>
                <button type="button" id="remove-icon-button" class="button button-secondary" style="color: #dc2626;">Remove</button>
            <?php endif; ?>
        </div>
        <p style="margin: 10px 0 0 0; font-size: 12px; color: #9ca3af;">
            💡 For best results, use an SVG file. PNG and JPG are also supported.
        </p>
    </div>
</div>

<!-- Icon Style Settings -->
<div class="scw-form-group">
    <label>Icon Style</label>
    <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">
        Adjust the appearance of your icon
    </p>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Icon Rotation -->
        <div>
            <label for="scw_icon_rotation" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                Rotation
            </label>
            <div style="display: flex; align-items: center; gap: 15px;">
                <input type="range" 
                       id="scw_icon_rotation" 
                       name="scw_icon_rotation" 
                       min="0" 
                       max="360" 
                       value="<?php echo esc_attr($icon_rotation); ?>"
                       class="scw-size-slider"
                       style="flex: 1;">
                <span class="scw-rotation-value" style="min-width: 45px; font-weight: 600; color: #374151;">
                    <?php echo esc_html($icon_rotation); ?>°
                </span>
            </div>
        </div>
        
        <!-- Icon Scale -->
        <div>
            <label for="scw_icon_scale" style="display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #6b7280;">
                Size Multiplier
            </label>
            <div style="display: flex; align-items: center; gap: 15px;">
                <input type="range" 
                       id="scw_icon_scale" 
                       name="scw_icon_scale" 
                       min="0.5" 
                       max="2" 
                       step="0.1"
                       value="<?php echo esc_attr($icon_scale); ?>"
                       class="scw-size-slider"
                       style="flex: 1;">
                <span class="scw-scale-value" style="min-width: 50px; font-weight: 600; color: #374151;">
                    <?php echo esc_html($icon_scale); ?>x
                </span>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize WordPress color picker
jQuery(document).ready(function($) {
    $('.scw-color-picker').wpColorPicker({
        change: function(event, ui) {
            $('.scw-color-preview').css('background-color', ui.color.toString());
        }
    });
    
    // Update size value display
    $('#scw_widget_size').on('input', function() {
        $('.scw-size-value').text($(this).val() + 'px');
    });
    
    // Update icon rotation display
    $('#scw_icon_rotation').on('input', function() {
        $('.scw-rotation-value').text($(this).val() + '°');
    });
    
    // Update icon scale display
    $('#scw_icon_scale').on('input', function() {
        $('.scw-scale-value').text($(this).val() + 'x');
    });
    
    // Position selector
    $('.scw-position-option input').on('change', function() {
        $('.scw-position-option').removeClass('active');
        $(this).closest('.scw-position-option').addClass('active');
        
        // Show/hide custom position settings
        if ($(this).val() === 'custom') {
            $('#custom-position-settings').slideDown(300);
        } else {
            $('#custom-position-settings').slideUp(300);
        }
    });
    
    // Icon selector
    $('.scw-icon-option input').on('change', function() {
        $('.scw-icon-option').removeClass('active');
        $(this).closest('.scw-icon-option').addClass('active');
        
        // Show/hide custom icon upload
        if ($(this).val() === 'custom') {
            $('#custom-icon-upload').slideDown(300);
        } else {
            $('#custom-icon-upload').slideUp(300);
        }
    });
    
    // WordPress Media Uploader for Icon
    var iconUploader;
    $('#upload-icon-button').on('click', function(e) {
        e.preventDefault();
        
        if (iconUploader) {
            iconUploader.open();
            return;
        }
        
        iconUploader = wp.media({
            title: 'Choose Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false
        });
        
        iconUploader.on('select', function() {
            var attachment = iconUploader.state().get('selection').first().toJSON();
            $('#scw_custom_icon_url').val(attachment.url);
            $('#icon-filename').text(attachment.filename || 'Icon selected');
            
            // Show remove button if not already visible
            if ($('#remove-icon-button').length === 0) {
                $('#icon-filename').after('<button type="button" id="remove-icon-button" class="button button-secondary" style="color: #dc2626;">Remove</button>');
                $('#remove-icon-button').on('click', function() {
                    $('#scw_custom_icon_url').val('');
                    $('#icon-filename').text('No icon selected');
                    $(this).remove();
                });
            }
        });
        
        iconUploader.open();
    });
    
    // Remove icon button
    $(document).on('click', '#remove-icon-button', function() {
        $('#scw_custom_icon_url').val('');
        $('#icon-filename').text('No icon selected');
        $(this).remove();
    });
});
</script>

<style>
.scw-position-selector {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.scw-position-option {
    cursor: pointer;
}

.scw-position-option input[type="radio"] {
    display: none;
}

.scw-position-option .position-card {
    padding: 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    text-align: center;
    transition: all 0.3s ease;
}

.scw-position-option .position-card:hover {
    border-color: #8e44ad;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.scw-position-option.active .position-card {
    border-color: #8e44ad;
    background: #f9fafb;
}

.scw-position-option .position-card span {
    display: block;
    margin-top: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

/* Icon Selector */
.scw-icon-option {
    cursor: pointer;
}

.scw-icon-option input[type="radio"] {
    display: none;
}

.scw-icon-option .icon-card {
    padding: 15px 10px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    text-align: center;
    transition: all 0.3s ease;
    min-height: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.scw-icon-option .icon-card:hover {
    border-color: #8e44ad;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.scw-icon-option.active .icon-card {
    border-color: #8e44ad;
    background: #f9fafb;
}

.scw-icon-option .icon-card svg {
    color: #6b7280;
    margin-bottom: 8px;
}

.scw-icon-option.active .icon-card svg {
    color: #8e44ad;
}

.scw-icon-option .icon-card span {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
}

.scw-size-slider {
    -webkit-appearance: none;
    appearance: none;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    outline: none;
}

.scw-size-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    background: #8e44ad;
    border-radius: 50%;
    cursor: pointer;
}

.scw-size-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    background: #8e44ad;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}
</style>

