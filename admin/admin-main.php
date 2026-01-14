<?php

/**
 * Dragwyb Click To Chat - Main Admin Template
 * Inspired by Chaty plugin
 */

if (! defined('ABSPATH')) {
    exit;
}

// Get current settings
$dctc_settings = get_option('dctc_settings', array());

// Helper vars for header/summary
$dctc_fb_id  = isset($dctc_settings['facebook_value']) ? $dctc_settings['facebook_value'] : '';
$dctc_wa_num = isset($dctc_settings['whatsapp_value']) ? $dctc_settings['whatsapp_value'] : '';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verification not needed for navigational GET parameter
$dctc_current_step = isset($_GET['step']) ? intval($_GET['step']) : 0;
?>

<div class="dctc-admin-wrap">

    <!-- Success Message -->
    <div class="dctc-success-message"></div>

    <!-- Header with Tabs -->
    <div class="dctc-header">
        <ul class="dctc-tabs">
            <li>
                <a href="#"
                    class="dctc-tab <?php echo $dctc_current_step === 0 ? 'active' : ''; ?>"
                    data-step="0">
                    <span><?php esc_html_e('1. Select Channels', 'dragwyb-click-to-chat'); ?></span>
                </a>
            </li>
            <li>
                <a href="#"
                    class="dctc-tab <?php echo $dctc_current_step === 1 ? 'active' : ''; ?>"
                    data-step="1">
                    <span><?php esc_html_e('2. Widget Customization', 'dragwyb-click-to-chat'); ?></span>
                </a>
            </li>
            <li>
                <a href="#"
                    class="dctc-tab <?php echo $dctc_current_step === 2 ? 'active' : ''; ?>"
                    data-step="2">
                    <span><?php esc_html_e('3. Triggers & Targeting', 'dragwyb-click-to-chat'); ?></span>
                </a>
            </li>
            <li>
                <a href="#"
                    class="dctc-tab <?php echo $dctc_current_step === 3 ? 'active' : ''; ?>"
                    data-step="3">
                    <span><?php esc_html_e('4. Settings', 'dragwyb-click-to-chat'); ?></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="dctc-content">

        <!-- Settings Panel -->
        <div class="dctc-settings-panel">

            <!-- Section 1: Select Channels -->
            <div class="dctc-section <?php echo $dctc_current_step === 0 ? 'active' : ''; ?>" data-section="0">
                <?php include 'section-channels.php'; ?>
            </div>

            <!-- Section 2: Widget Customization -->
            <div class="dctc-section <?php echo $dctc_current_step === 1 ? 'active' : ''; ?>" data-section="1">
                <?php include 'section-customization.php'; ?>
            </div>

            <!-- Section 3: Triggers & Targeting -->
            <div class="dctc-section <?php echo $dctc_current_step === 2 ? 'active' : ''; ?>" data-section="2">
                <?php include 'section-triggers.php'; ?>
            </div>

            <!-- Section 4: Settings -->
            <div class="dctc-section <?php echo $dctc_current_step === 3 ? 'active' : ''; ?>" data-section="3">
                <?php include 'section-settings.php'; ?>
            </div>

        </div>

        <!-- Preview Panel -->
        <div class="dctc-preview-panel">
            <div class="dctc-preview">
                <h3 class="dctc-preview-title">
                    <?php esc_html_e('Preview', 'dragwyb-click-to-chat'); ?>
                </h3>

                <div class="dctc-preview-device">
                    <p style="text-align:center; color:#9ca3af; padding:20px;">
                        <?php esc_html_e('Widget preview will appear here', 'dragwyb-click-to-chat'); ?>
                    </p>
                    <!-- Preview updated via JS -->
                </div>
            </div>
        </div>

    </div>

    <!-- Navigation Footer -->
    <div class="dctc-navigation">

        <button type="button"
            id="dctc-back-btn"
            class="dctc-btn dctc-btn-secondary"
            disabled>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M15.8333 10H4.16668" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M10 15.8333L4.16668 9.99996L10 4.16663"
                    stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <?php esc_html_e('Back', 'dragwyb-click-to-chat'); ?>
        </button>

        <div style="display:flex; gap:10px;">

            <button type="button"
                id="dctc-next-btn"
                class="dctc-btn dctc-btn-secondary">
                <?php esc_html_e('Next', 'dragwyb-click-to-chat'); ?>
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.16677 10H15.8334" stroke="currentColor"
                        stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M10.0001 4.16663L15.8334 9.99996L10.0001 15.8333"
                        stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <button type="button"
                id="dctc-save-btn"
                class="dctc-btn dctc-btn-primary">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333V4.16667C2.5 3.72464 2.67559 3.30072 2.98816 2.98816C3.30072 2.67559 3.72464 2.5 4.16667 2.5H13.3333L17.5 6.66667V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5Z"
                        stroke="currentColor" stroke-width="1.67"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M14.1666 17.5V10.8334H5.83331V17.5"
                        stroke="currentColor" stroke-width="1.67"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M5.83331 2.5V6.66667H12.5"
                        stroke="currentColor" stroke-width="1.67"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <?php esc_html_e('Save Settings', 'dragwyb-click-to-chat'); ?>
            </button>

        </div>
    </div>

</div>