<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SCW_Frontend {

    public function __construct() {
        // Auto-add to footer
        add_action( 'wp_footer', array( $this, 'render_widget_html' ) );
        
        // 1. Register Shortcode [social_chat]
        add_shortcode( 'social_chat', array( $this, 'render_widget_shortcode' ) );
    }

    /**
     * Wrapper for Shortcode [social_chat]
     */
    public function render_widget_shortcode() {
        ob_start(); // Start recording output
        $this->render_widget_html(); // Generate HTML/CSS/JS
        return ob_get_clean(); // Return output to the page
    }

    /**
     * The Main Render Function
     */
    /**
     * The Main Render Function
     */
    public function render_widget_html() {
        // Get all settings container
        $settings = get_option( 'scw_settings', array() );

        // Helper to get value
        // $val = isset($settings['key']) ? $settings['key'] : 'default';

        // Get widget customization settings
        $widget_position = isset($settings['widget_position']) ? $settings['widget_position'] : 'right';
        $widget_color = isset($settings['widget_color']) ? $settings['widget_color'] : '#8e44ad';
        $widget_size = isset($settings['widget_size']) ? $settings['widget_size'] : '60';
        $widget_size_unit = isset($settings['widget_size_unit']) ? $settings['widget_size_unit'] : 'px';
        $widget_size_str = $widget_size . $widget_size_unit;
        
        $time_delay = isset($settings['time_delay']) ? $settings['time_delay'] : '0';
        
        // Get device visibility settings
        $show_on_desktop = isset($settings['show_on_desktop']) ? $settings['show_on_desktop'] : '1';
        $show_on_mobile = isset($settings['show_on_mobile']) ? $settings['show_on_mobile'] : '1';
        
        // Check if widget should be displayed based on device
        $is_mobile = wp_is_mobile();
        if ( $is_mobile && $show_on_mobile !== '1' ) {
            return; // Don't show on mobile
        }
        if ( ! $is_mobile && $show_on_desktop !== '1' ) {
            return; // Don't show on desktop
        }
        
        // Determine position styles
        if ($widget_position === 'custom') {
            // Custom position
            $custom_bottom = isset($settings['custom_bottom']) ? $settings['custom_bottom'] : '20';
            $custom_bottom_unit = isset($settings['custom_bottom_unit']) ? $settings['custom_bottom_unit'] : 'px';
            $custom_horizontal = isset($settings['custom_horizontal']) ? $settings['custom_horizontal'] : '20';
            $custom_horizontal_unit = isset($settings['custom_horizontal_unit']) ? $settings['custom_horizontal_unit'] : 'px';
            $custom_side = isset($settings['custom_side']) ? $settings['custom_side'] : 'right';
            $custom_vertical_align = isset($settings['custom_vertical_align']) ? $settings['custom_vertical_align'] : 'bottom';
            
            // CSS Strings
            $bottom_str = $custom_bottom . $custom_bottom_unit;
            $horizontal_str = $custom_horizontal . $custom_horizontal_unit;
            
            // Vertical style
            $vert_style = ($custom_vertical_align === 'top' ? 'top: ' : 'bottom: ') . esc_attr($bottom_str) . ';';
            if ($custom_vertical_align === 'top') $vert_style .= ' bottom: auto;';
            else $vert_style .= ' top: auto;';
            
            $position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . esc_attr($horizontal_str) . ';';
            $position_style .= ' ' . $vert_style;
            
            // Menu position using calc()
            // menu_pos = vertical_dist + widget_size + 10px
            $menu_vert_pos = "calc({$bottom_str} + {$widget_size_str} + 10px)";
            
            $menu_vert_style = ($custom_vertical_align === 'top' ? 'top: ' : 'bottom: ') . $menu_vert_pos . ';';
            if ($custom_vertical_align === 'top') $menu_vert_style .= ' bottom: auto;';
            else $menu_vert_style .= ' top: auto;';
            
            // Menu side position: horizontal_dist + 5px
            $menu_side_pos = "calc({$horizontal_str} + 5px)";
            $menu_position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . $menu_side_pos . ';';
            $menu_position_style .= ' ' . $menu_vert_style;

        } else {
            // Preset position (left or right)
            $position_style = $widget_position === 'left' ? 'left: 20px; bottom: 20px;' : 'right: 20px; bottom: 20px;';
            
            // Preset menu position
            $menu_bottom_dist = "calc(20px + {$widget_size_str} + 10px)";
            $menu_position_style = $widget_position === 'left' ? "left: 25px; bottom: {$menu_bottom_dist};" : "right: 25px; bottom: {$menu_bottom_dist};";
        }

        // Collect enabled channels dynamically
        $all_channels_registry = scw_get_channels();
        $phase1_channels = array('whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin');
        
        $channels = array();
        
        foreach ($phase1_channels as $slug) {
            $enabled = isset($settings[$slug . '_enabled']) ? $settings[$slug . '_enabled'] : '0';
            $value = isset($settings[$slug . '_value']) ? $settings[$slug . '_value'] : '';
            
            // Skip if not enabled or missing required value (except toggle types)
            if ($enabled !== '1') continue;
            if (empty($value) && $all_channels_registry[$slug]['input_type'] !== 'toggle') continue;
            
            // Check device visibility for this specific channel
            $show_on_desktop = isset($settings[$slug . '_desktop']) ? $settings[$slug . '_desktop'] : '1';
            $show_on_mobile = isset($settings[$slug . '_mobile']) ? $settings[$slug . '_mobile'] : '1';
            
            // Detect if mobile
            $is_mobile = wp_is_mobile();
            
            // Skip if device doesn't match visibility settings
            if ($is_mobile && $show_on_mobile !== '1') continue;
            if (!$is_mobile && $show_on_desktop !== '1') continue;
            
            $channel_config = $all_channels_registry[$slug];
            $custom_icon_url = isset($settings[$slug . '_custom_icon']) ? $settings[$slug . '_custom_icon'] : '';
            
            $icon_html = '';
            $bg_color = $channel_config['color'];

            if (!empty($custom_icon_url)) {
                // If custom icon is used, remove background color and make icon fill the button
                $bg_color = 'transparent';
                
                $file_ext = strtolower(pathinfo($custom_icon_url, PATHINFO_EXTENSION));
                if ($file_ext === 'svg') {
                     $svg_content = @file_get_contents($custom_icon_url);
                     if ($svg_content) {
                         $icon_html = '<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">' . $svg_content . '</div>';
                     } else {
                         $icon_html = '<img src="' . esc_url($custom_icon_url) . '" alt="' . esc_attr($channel_config['name']) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
                     }
                } else {
                     $icon_html = '<img src="' . esc_url($custom_icon_url) . '" alt="' . esc_attr($channel_config['name']) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
                }
            } else {
                // Default handling
                $icon_html = '<svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: white;">' . $channel_config['icon'] . '</svg>';
            }
            
            $extra_style = '';
            if (!empty($custom_icon_url)) {
                 $extra_style = 'box-shadow: none;';
            }

            // Build channel array
            $channel_item = array(
                'color' => $bg_color,
                'icon' => $icon_html,
                'title' => $channel_config['name'], // Channel name for tooltip
                'extra_style' => $extra_style
            );
            
            // Determine type and link
            if ($channel_config['url_pattern'] === 'internal') {
                $channel_item['type'] = 'internal';
            } else {
                $channel_item['type'] = 'link';
                // Generate URL from pattern
                $url_pattern = $channel_config['url_pattern'];
                if (strpos($url_pattern, '%s') !== false) {
                    // Replace %s with value
                    $channel_item['link'] = sprintf($url_pattern, rawurlencode($value));
                } else {
                    // Use value directly (for URL-type channels)
                    $channel_item['link'] = esc_url($value);
                }
            }
            
            $channels[] = $channel_item;
        }

        // If NO channels are active, return nothing.
        if ( empty( $channels ) ) return;
        
        // Get icon settings
        $icon_type = isset($settings['icon_type']) ? $settings['icon_type'] : 'chat';
        $custom_icon_url = isset($settings['custom_icon_url']) ? $settings['custom_icon_url'] : '';
        $icon_rotation = isset($settings['icon_rotation']) ? $settings['icon_rotation'] : '0';
        $icon_scale = isset($settings['icon_scale']) ? $settings['icon_scale'] : '1';
        
        // Generate icon HTML based on type
        $icon_html = '';
        $icon_transform = 'transform: rotate(' . esc_attr($icon_rotation) . 'deg) scale(' . esc_attr($icon_scale) . ');';
        
        switch ($icon_type) {
            case 'chat':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
                break;
            case 'message':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
                break;
            case 'support':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
                break;
            case 'phone':
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
                break;
            case 'custom':
                if (!empty($custom_icon_url)) {
                        // For PNG/JPG
                        $icon_html = '<img src="' . esc_url($custom_icon_url) . '" alt="Chat" style="width: 100%; height: 100%; object-fit: contain; ' . $icon_transform . '">';
                } else {
                    // Fallback to default chat icon
                    $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
                }
                break;
            default:
                $icon_html = '<svg viewBox="0 0 24 24" style="' . $icon_transform . '"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
        }
        ?>
        <style>
            /* The Floating Button */
            .scw-widget-btn { position: fixed; <?php echo esc_attr($position_style); ?> width: <?php echo esc_attr($widget_size_str); ?>; height: <?php echo esc_attr($widget_size_str); ?>; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 999999; transition: transform 0.3s; }
            .scw-widget-btn:hover { transform: scale(1.1); }
            .scw-widget-btn svg { width: 100%; height: 100%; fill: <?php echo esc_attr($widget_color); ?>; }
            .scw-widget-btn img { width: 100%; height: 100%; object-fit: contain; }

            /* Sub Menu Items */
            .scw-menu { position: fixed; <?php echo esc_attr($menu_position_style); ?> display: flex; flex-direction: column; gap: 10px; z-index: 999998; opacity: 0; pointer-events: none; transform: translateY(20px); transition: all 0.3s; }
            .scw-menu.scw-open { opacity: 1; pointer-events: auto; transform: translateY(0); }
            .scw-sub-btn { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,0.2); cursor: pointer; border: none; }
            .scw-sub-btn svg { width: 24px; fill: white; }

            /* Initially hide the widget */
            .scw-widget-hidden { opacity: 0; pointer-events: none; }
        </style>

        <div class="scw-widget-btn <?php echo $time_delay > 0 ? 'scw-widget-hidden' : ''; ?>" id="scw-widget-btn" onclick="scwToggleMenu()">
            <?php 
            $allowed_svg = array(
                'path' => array( 'd' => array() ),
                'svg' => array( 'viewbox' => array(), 'style' => array(), 'fill' => array(), 'width' => array(), 'height' => array() ),
                'img' => array( 'src' => array(), 'alt' => array(), 'style' => array() ),
                'div' => array( 'style' => array() )
            );
            echo wp_kses($icon_html, $allowed_svg); 
            ?>
        </div>

        <div class="scw-menu" id="scw-menu">
            <?php foreach ( $channels as $c ) : ?>
                <?php if ( $c['type'] === 'internal' ) : ?>
                    <button class="scw-sub-btn" style="background: <?php echo esc_attr($c['color']); ?>; <?php echo esc_attr($c['extra_style']); ?>" onclick="scwOpenChat()" title="<?php echo esc_attr($c['title']); ?>">
                        <?php echo wp_kses($c['icon'], $allowed_svg); ?>
                    </button>
                <?php else : ?>
                    <a href="<?php echo esc_url($c['link']); ?>" target="_blank" class="scw-sub-btn" style="background: <?php echo esc_attr($c['color']); ?>; <?php echo esc_attr($c['extra_style']); ?>" title="<?php echo esc_attr($c['title']); ?>">
                        <?php echo wp_kses($c['icon'], $allowed_svg); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <script>
            // Time delay for widget appearance
            <?php if ($time_delay > 0) : ?>
            setTimeout(function() {
                const widget = document.getElementById('scw-widget-btn');
                if (widget) {
                    widget.classList.remove('scw-widget-hidden');
                    widget.style.opacity = '1';
                    widget.style.pointerEvents = 'auto';
                    widget.style.transition = 'opacity 0.3s ease';
                }
            }, <?php echo esc_js($time_delay * 1000); ?>);
            <?php endif; ?>
            
            function scwToggleMenu() {
                var menu = document.getElementById('scw-menu');
                menu.classList.toggle('scw-open');
            }
        </script>
        <?php
    }
}

new SCW_Frontend();