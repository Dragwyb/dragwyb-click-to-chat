<?php
if (! defined('ABSPATH')) exit;

class DCTC_Frontend
{

    public function __construct()
    {
        // Auto-add to footer
        add_action('wp_footer', array($this, 'render_widget_html'));

        add_action('wp_enqueue_scripts', array($this, 'register_styles_scripts'));

        // Add type="module" for emoji picker script
        add_filter('script_loader_tag', array($this, 'add_module_type_attribute'), 10, 3);

        // 1. Register Shortcode [dctc-widget]
        add_shortcode('dctc-widget', array($this, 'render_widget_shortcode'));
    }

    /**
     * Register Styles and Scripts
     */
    public function register_styles_scripts()
    {
        wp_register_style('dctc-frontend-style', DCTC_PLUGIN_URL . 'assets/css/frontend.css', array(), DCTC_VERSION);
        wp_register_script('dctc-frontend-script', DCTC_PLUGIN_URL . 'assets/js/frontend.js', array(), DCTC_VERSION, true);
        wp_register_script('dctc-emoji-picker', DCTC_PLUGIN_URL . 'assets/js/emoji-picker-element.js', array(), DCTC_VERSION, true);
    }

    /**
     * Wrapper for Shortcode [dctc-widget]
     */
    public function render_widget_shortcode()
    {
        ob_start(); // Start recording output
        $this->render_widget_html(true); // Generate HTML/CSS/JS
        return ob_get_clean(); // Return output to the page
    }

    /**
     * Add type="module" to script tag
     */
    public function add_module_type_attribute($tag, $handle, $src)
    {
        if ('dctc-emoji-picker' === $handle) {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }
        return $tag;
    }
    /**
     * The Main Render Function
     * 
     * @param boolean $from_shortcode Whether called via shortcode
     */
    public function render_widget_html($from_shortcode = false)
    {
        // Get all settings container
        $settings = get_option('dctc_settings', array());

        // Display Rules Check (only if auto-injected, not manually via shortcode)
        if (! $from_shortcode) {
            $display_mode = isset($settings['display_mode']) ? $settings['display_mode'] : 'all';

            // Specific Post Types Mode
            if ($display_mode === 'post_types') {
                $allowed_types = isset($settings['display_post_types']) ? $settings['display_post_types'] : array();

                // Safety check: if no types selected, don't show
                if (empty($allowed_types)) {
                    return;
                }

                // If current page is not one of the allowed singular post types, return.
                if (! is_singular($allowed_types)) {
                    return;
                }
            }
        }

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
        if ($is_mobile && $show_on_mobile !== '1') {
            return; // Don't show on mobile
        }
        if (! $is_mobile && $show_on_desktop !== '1') {
            return; // Don't show on desktop
        }

        // Don't show in Elementor Editor
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is not required for preview mode
        if ((class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->preview->is_preview_mode()) || isset($_GET['elementor-preview'])) {
            return;
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

            // Menu sidebar side position: horizontal_dist + 5px
            $menu_side_pos = "calc({$horizontal_str} + 5px)";
            $menu_position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . $menu_side_pos . ';';
            $menu_position_style .= ' ' . $menu_vert_style;

            // Chat Widget Position matches the button's horizontal/vertical alignment but shifted up/down
            $chat_widget_position_style = ($custom_side === 'left' ? 'left: ' : 'right: ') . $horizontal_str . ';';
            $chat_widget_position_style .= ' ' . $menu_vert_style; // Use same vertical space as menu

        } else {
            // Preset position (left or right)
            $position_style = $widget_position === 'left' ? 'left: 20px; bottom: 20px;' : 'right: 20px; bottom: 20px;';

            // Preset menu position
            $menu_bottom_dist = "calc(20px + {$widget_size_str} + 10px)";
            $menu_position_style = $widget_position === 'left' ? "left: 25px; bottom: {$menu_bottom_dist};" : "right: 25px; bottom: {$menu_bottom_dist};";

            // Chat Widget Position
            $chat_widget_position_style = $widget_position === 'left' ? "left: 20px; bottom: {$menu_bottom_dist};" : "right: 20px; bottom: {$menu_bottom_dist};";
        }

        // Collect enabled channels dynamically
        $all_channels_registry = dctc_get_channels();
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
                $icon_html = '<img src="' . esc_url($custom_icon_url) . '" alt="' . esc_attr($channel_config['name']) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
            } else {
                // Default handling
                $icon_html = '<svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: white;">' . $channel_config['icon'] . '</svg>';
            }

            $extra_style = '';
            if (!empty($custom_icon_url)) {
                $extra_style = 'box-shadow: none;';
            }

            // Chat Widget Settings for this channel
            // Only allow 'whatsapp', 'instagram', 'telegram' to have chat widget enabled

            $allowed_widget_channels = array('whatsapp', 'instagram', 'telegram');
            $chat_widget_enabled = '0';

            if (in_array($slug, $allowed_widget_channels)) {
                $chat_widget_enabled = isset($settings[$slug . '_chat_widget_enabled']) ? $settings[$slug . '_chat_widget_enabled'] : '0';
            }

            $default_message = isset($settings[$slug . '_default_message']) ? $settings[$slug . '_default_message'] : __('Hi! How can I help you?', 'dragwyb-click-to-chat');

            // Build channel array
            $channel_item = array(
                'slug' => $slug, // Added slug for identifying channel
                'color' => $bg_color,
                'icon' => $icon_html,
                'title' => $channel_config['name'], // Channel name for tooltip
                'extra_style' => $extra_style,
                'chat_widget_enabled' => $chat_widget_enabled,
                'default_message' => $default_message,
                'url_pattern' => $channel_config['url_pattern']
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
                    $val_to_use = ($slug === 'email') ? sanitize_email($value) : sanitize_text_field($value);
                    $channel_item['link'] = sprintf($url_pattern, $val_to_use);
                    // Store raw value for widget use
                    $channel_item['raw_value'] = $value;
                } else {
                    // Use value directly (for URL-type channels)
                    $channel_item['link'] = esc_url($value);
                    $channel_item['raw_value'] = $value;
                }
            }

            $channels[] = $channel_item;
        }

        // If NO channels are active, return nothing.
        if (empty($channels)) return;

        $is_single_channel = count($channels) === 1;
        $show_widget       = isset($settings['show_widget']) ? $settings['show_widget'] : '1';
        $show_launcher     = ! $is_single_channel && $show_widget === '1';

        // Get icon settings
        $icon_type = isset($settings['icon_type']) ? $settings['icon_type'] : 'chat';
        $custom_icon_url = isset($settings['custom_icon_url']) ? $settings['custom_icon_url'] : '';
        $icon_rotation = isset($settings['icon_rotation']) ? $settings['icon_rotation'] : '0';
        $icon_scale = isset($settings['icon_scale']) ? $settings['icon_scale'] : '1';

        // Greeting Message
        $greeting_message = isset($settings['greeting_message']) ? $settings['greeting_message'] : '';
        $greeting_color   = $widget_color;
        if ($is_single_channel && ! empty($channels[0]['color']) && $channels[0]['color'] !== 'transparent') {
            $greeting_color = $channels[0]['color'];
        }

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

        // Enqueue Assets
        wp_enqueue_style('dctc-frontend-style');
        wp_enqueue_script('dctc-frontend-script');
        wp_enqueue_script('dctc-emoji-picker');

        // Dynamic CSS
        // Dynamic CSS
        $is_left = false;
        $greeting_side = 'right';
        $greeting_vert = 'bottom';
        $greeting_horiz_str = '20px';
        $greeting_vert_str = '20px';

        if ($widget_position === 'custom') {
            $is_left = (isset($custom_side) && $custom_side === 'left');
            $greeting_side = isset($custom_side) ? $custom_side : 'right';
            $greeting_vert = isset($custom_vertical_align) ? $custom_vertical_align : 'bottom';
            $greeting_horiz_str = isset($horizontal_str) ? $horizontal_str : '20px';
            $greeting_vert_str = isset($bottom_str) ? $bottom_str : '20px';
        } else {
            $is_left = ($widget_position === 'left');
            $greeting_side = $is_left ? 'left' : 'right';
        }

        $greeting_position_css = $greeting_side . ': calc(' . esc_attr($greeting_horiz_str) . ' + ' . esc_attr($widget_size_str) . ' + 15px);';
        $greeting_position_css .= ($greeting_side === 'left') ? ' right: auto;' : ' left: auto;';
        $greeting_position_css .= $greeting_vert . ': calc(' . esc_attr($greeting_vert_str) . ' + (' . esc_attr($widget_size_str) . ' - 40px) / 2);';
        $greeting_position_css .= ($greeting_vert === 'top') ? ' bottom: auto;' : ' top: auto;';

        $custom_css = "
            .dctc-widget-btn { 
                " . esc_attr($position_style) . " 
                width: " . esc_attr($widget_size_str) . "; 
                height: " . esc_attr($widget_size_str) . "; 
            }
            .dctc-widget-btn svg { fill: " . esc_attr($widget_color) . "; }
            .dctc-menu { 
                " . esc_attr($show_launcher ? $menu_position_style : $position_style) . " 
            }
            .dctc-sub-btn { 
                width: " . esc_attr($widget_size_str) . "; 
                height: " . esc_attr($widget_size_str) . "; 
            }
            .dctc-greeting-message {
                position: fixed;
                " . $greeting_position_css . "
                background: " . esc_attr($greeting_color) . ";
                color: #fff;
                padding: 10px 15px;
                border-radius: 8px;
                filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
                font-size: 14px;
                font-weight: bold;
                line-height: 1.4;
                z-index: 999998;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                transform: translateY(10px);
                transition: opacity 0.5s ease, transform 0.5s ease, visibility 0.5s ease;
            }
            .dctc-greeting-message::after {
                content: '';
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                " . ($is_left ?
            "left: -8px; border-width: 6px 8px 6px 0; border-style: solid; border-color: transparent " . esc_attr($greeting_color) . " transparent transparent;" :
            "right: -8px; border-width: 6px 0 6px 8px; border-style: solid; border-color: transparent transparent transparent " . esc_attr($greeting_color) . ";"
        ) . "
            }
            .dctc-greeting-message.dctc-visible {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }
            .dctc-greeting-message.dctc-hidden {
                opacity: 0;
                visibility: hidden;
            }
        ";
        wp_add_inline_style('dctc-frontend-style', $custom_css);

        $allowed_svg = array(
            'path' => array('d' => array()),
            'svg' => array('viewbox' => array(), 'style' => array(), 'fill' => array(), 'width' => array(), 'height' => array()),
            'img' => array('src' => array(), 'alt' => array(), 'style' => array()),
            'div' => array('style' => array())
        );

        // Dynamic JS (Time Delay)
        if ($time_delay > 0) {
            $delay_ms = $time_delay * 1000;
            $inline_js = "
                setTimeout(function() {
                    var hidden = document.querySelectorAll('.dctc-widget-hidden');
                    hidden.forEach(function(el) {
                        el.classList.remove('dctc-widget-hidden');
                        el.style.opacity = '1';
                        el.style.pointerEvents = 'auto';
                        el.style.transition = 'opacity 0.3s ease';
                    });
                }, " . esc_js($delay_ms) . ");
            ";
            wp_add_inline_script('dctc-frontend-script', $inline_js);
        }

        $hidden_class = $time_delay > 0 ? 'dctc-widget-hidden' : '';
?>

        <?php if ($is_single_channel) :
            $single = $channels[0];
            $single_style = 'background: ' . esc_attr($single['color']) . '; ' . esc_attr($single['extra_style']);
            $single_href = 'javascript:void(0);';
            $single_target = '';
            $single_onclick = 'dctcHideGreeting()';

            if ($single['chat_widget_enabled'] === '1') {
                $single_onclick = 'dctcHideGreeting(); dctcOpenWidget(\'' . esc_js($single['slug']) . '\')';
            } elseif ($single['type'] === 'internal') {
                $single_onclick = 'dctcHideGreeting(); dctcOpenChat();';
            } elseif (! empty($single['link'])) {
                $single_href = $single['link'];
                $single_target = (strpos($single_href, 'tel:') === 0 || strpos($single_href, 'sms:') === 0) ? '' : '_blank';
            }
        ?>
            <?php if ($single['chat_widget_enabled'] === '1' || $single['type'] === 'internal') : ?>
                <button type="button" class="dctc-widget-btn dctc-single-channel <?php echo esc_attr($hidden_class); ?>" id="dctc-widget-btn" style="<?php echo esc_attr($single_style); ?>" onclick="<?php echo esc_attr($single_onclick); ?>" title="<?php echo esc_attr($single['title']); ?>">
                    <?php echo wp_kses($single['icon'], $allowed_svg); ?>
                </button>
            <?php else : ?>
                <a href="<?php echo esc_url($single_href); ?>" <?php echo $single_target ? 'target="' . esc_attr($single_target) . '" rel="noopener noreferrer"' : ''; ?> class="dctc-widget-btn dctc-single-channel <?php echo esc_attr($hidden_class); ?>" id="dctc-widget-btn" style="<?php echo esc_attr($single_style); ?>" onclick="dctcHideGreeting()" title="<?php echo esc_attr($single['title']); ?>">
                    <?php echo wp_kses($single['icon'], $allowed_svg); ?>
                </a>
            <?php endif; ?>
        <?php elseif ($show_launcher) : ?>
            <div class="dctc-widget-btn <?php echo esc_attr($hidden_class); ?>" id="dctc-widget-btn" onclick="dctcToggleMenu()">
                <?php echo wp_kses($icon_html, $allowed_svg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($greeting_message)): ?>
            <div class="dctc-greeting-message" id="dctc-greeting-message">
                <?php echo esc_html($greeting_message); ?>
            </div>
        <?php endif; ?>

        <?php if (! $is_single_channel) :
            $menu_classes = 'dctc-menu';
            if (! $show_launcher) {
                $menu_classes .= ' dctc-menu-direct';
            }
            if ($time_delay > 0 && ! $show_launcher) {
                $menu_classes .= ' dctc-widget-hidden';
            }
        ?>
        <div class="<?php echo esc_attr($menu_classes); ?>" id="dctc-menu">
            <?php foreach ($channels as $c) : ?>
                <?php
                $onclick = '';
                $href = 'javascript:void(0);';
                $target = '';

                if ($c['chat_widget_enabled'] === '1') {
                    $onclick = 'dctcHideGreeting(); dctcOpenWidget(\'' . esc_js($c['slug']) . '\')';
                } elseif ($c['type'] === 'internal') {
                    $onclick = 'dctcOpenChat()';
                } else {
                    $href = esc_url($c['link']);
                    // Open in new tab unless it's a tel: link (which should open system default app)
                    $target = (strpos($href, 'tel:') === 0) ? '' : '_blank';
                }
                ?>

                <?php if ($onclick): ?>
                    <button class="dctc-sub-btn" style="background: <?php echo esc_attr($c['color']); ?>; <?php echo esc_attr($c['extra_style']); ?>" onclick="<?php echo esc_attr($onclick); ?>" title="<?php echo esc_attr($c['title']); ?>">
                        <?php echo wp_kses($c['icon'], $allowed_svg); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url($href); ?>" <?php echo $target ? 'target="' . esc_attr($target) . '"' : ''; ?> class="dctc-sub-btn" style="background: <?php echo esc_attr($c['color']); ?>; <?php echo esc_attr($c['extra_style']); ?>" title="<?php echo esc_attr($c['title']); ?>">
                        <?php echo wp_kses($c['icon'], $allowed_svg); ?>
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Chat Widget Popups -->
        <?php foreach ($channels as $c) : ?>
            <?php if ($c['chat_widget_enabled'] === '1') : ?>
                <?php
                $widget_class = 'dctc-chat-widget dctc-theme-' . esc_attr($c['slug']);
                $header_bg = $c['slug'] === 'whatsapp' ? '#095e54' : esc_attr($c['color']);
                $body_style = '';

                // Channel specific adjustments
                if ($c['slug'] === 'whatsapp') {
                    $body_style = 'background-image: url(https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png);';
                }
                ?>
                <div class="<?php echo esc_attr($widget_class); ?>" id="dctc-chat-widget-<?php echo esc_attr($c['slug']); ?>" style="display: none; <?php echo esc_attr($chat_widget_position_style); ?>">

                    <div class="dctc-chat-header" style="background: <?php echo esc_attr($header_bg); ?>;">
                        <div class="dctc-chat-agent">
                            <div class="dctc-chat-avatar">
                                <?php echo wp_kses($c['icon'], $allowed_svg); ?>
                            </div>
                            <div class="dctc-chat-info">
                                <span class="dctc-chat-name"><?php echo esc_html($c['title']); ?></span>
                                <!-- <span class="dctc-chat-status"><?php echo $c['slug'] === 'whatsapp' ? 'Typically replies within an hour' : esc_html__('Online', 'dragwyb-click-to-chat'); ?></span> -->
                            </div>
                        </div>
                        <button class="dctc-chat-close" onclick="dctcCloseWidget('<?php echo esc_js($c['slug']); ?>')">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="white">
                                <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z" />
                            </svg>
                        </button>
                    </div>

                    <div class="dctc-chat-body" style="<?php echo esc_attr($body_style); ?>">
                        <!-- Standard Chat Bubble Layout -->
                        <div class="dctc-chat-message-bubble default">
                            <div class="dctc-msg-text"><?php echo esc_html($c['default_message']); ?></div>
                            <div class="dctc-msg-time"><?php echo esc_html(gmdate('H:i')); ?></div>
                        </div>
                    </div>

                    <div class="dctc-chat-footer">
                        <!-- Emoji Picker Container (Shared) -->
                        <div class="dctc-emoji-picker" id="dctc-emoji-picker-<?php echo esc_attr($c['slug']); ?>" style="display: none;" data-emoji-source="<?php echo esc_url(DCTC_PLUGIN_URL . 'assets/js/emoji-data.json'); ?>"></div>

                        <!-- Standard Chat Footer -->
                        <div class="dctc-input-wrapper">
                            <button type="button" class="dctc-emoji-trigger" onclick="dctcToggleEmoji('<?php echo esc_js($c['slug']); ?>')">
                                <svg class="dctc-smiley" viewBox="0 0 24 24" width="24" height="24" fill="#8696a0">
                                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" />
                                </svg>
                            </button>
                            <input type="text" class="dctc-chat-input" id="dctc-input-<?php echo esc_attr($c['slug']); ?>" placeholder="<?php esc_attr_e('Write your message...', 'dragwyb-click-to-chat'); ?>" onkeypress="dctcCheckEnter(event, '<?php echo esc_js($c['slug']); ?>', '<?php echo esc_js($c['url_pattern']); ?>', '<?php echo esc_js($c['raw_value']); ?>')">
                        </div>
                        <button class="dctc-chat-send" onclick="dctcSendMessage('<?php echo esc_js($c['slug']); ?>', '<?php echo esc_js($c['url_pattern']); ?>', '<?php echo esc_js($c['raw_value']); ?>')">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="white" style="margin-left: -2px; margin-top: 2px; transform: rotate(0deg);">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>


<?php
    }
}

new DCTC_Frontend();
