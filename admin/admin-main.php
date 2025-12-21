<?php
/**
 * Social Chat Widget - Main Admin Template
 * Inspired by Chaty plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get current settings
$fb_id = get_option('scw_facebook_page_id', '');
$wa_num = get_option('scw_whatsapp_number', '');

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verification not needed for navigational GET parameter
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 0;
?>

<div class="scw-admin-wrap">
    
    <!-- Success Message -->
    <div class="scw-success-message"></div>

    <!-- Header with Tabs -->
    <div class="scw-header">
        <ul class="scw-tabs">
            <li>
                <a href="#" class="scw-tab <?php echo $current_step === 0 ? 'active' : ''; ?>" data-step="0">
                    <span>1. Select Channels</span>
                </a>
            </li>
            <li>
                <a href="#" class="scw-tab <?php echo $current_step === 1 ? 'active' : ''; ?>" data-step="1">
                    <span>2. Widget Customization</span>
                </a>
            </li>
            <li>
                <a href="#" class="scw-tab <?php echo $current_step === 2 ? 'active' : ''; ?>" data-step="2">
                    <span>3. Triggers & Targeting</span>
                </a>
            </li>
            <li>
                <a href="#" class="scw-tab <?php echo $current_step === 3 ? 'active' : ''; ?>" data-step="3">
                    <span>4. Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="scw-content">
        
        <!-- Settings Panel -->
        <div class="scw-settings-panel">
            
            <!-- Section 1: Select Channels -->
            <div class="scw-section <?php echo $current_step === 0 ? 'active' : ''; ?>" data-section="0">
                <?php include 'section-channels.php'; ?>
            </div>

            <!-- Section 2: Widget Customization -->
            <div class="scw-section <?php echo $current_step === 1 ? 'active' : ''; ?>" data-section="1">
                <?php include 'section-customization.php'; ?>
            </div>

            <!-- Section 3: Triggers & Targeting -->
            <div class="scw-section <?php echo $current_step === 2 ? 'active' : ''; ?>" data-section="2">
                <?php include 'section-triggers.php'; ?>
            </div>

            <!-- Section 4: Settings -->
            <div class="scw-section <?php echo $current_step === 3 ? 'active' : ''; ?>" data-section="3">
                <?php include 'section-settings.php'; ?>
            </div>

        </div>

        <!-- Preview Panel -->
        <div class="scw-preview-panel">
            <div class="scw-preview">
                <h3 class="scw-preview-title">Preview</h3>
                <div class="scw-preview-device">
                    <p style="text-align: center; color: #9ca3af; padding: 20px;">
                        Widget preview will appear here
                    </p>
                    <!-- Preview will be dynamically updated via JavaScript -->
                </div>
            </div>
        </div>

    </div>

    <!-- Navigation Footer -->
    <div class="scw-navigation">
        <button type="button" id="scw-back-btn" class="scw-btn scw-btn-secondary" disabled>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.8333 10H4.16668" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 15.8333L4.16668 9.99996L10 4.16663" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back
        </button>
        
        <div style="display: flex; gap: 10px;">
            <button type="button" id="scw-next-btn" class="scw-btn scw-btn-secondary">
                Next
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.16677 10H15.8334" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10.0001 4.16663L15.8334 9.99996L10.0001 15.8333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <button type="button" id="scw-save-btn" class="scw-btn scw-btn-primary">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15.8333 17.5H4.16667C3.72464 17.5 3.30072 17.3244 2.98816 17.0118C2.67559 16.6993 2.5 16.2754 2.5 15.8333V4.16667C2.5 3.72464 2.67559 3.30072 2.98816 2.98816C3.30072 2.67559 3.72464 2.5 4.16667 2.5H13.3333L17.5 6.66667V15.8333C17.5 16.2754 17.3244 16.6993 17.0118 17.0118C16.6993 17.3244 16.2754 17.5 15.8333 17.5Z" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.1666 17.5V10.8334H5.83331V17.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.83331 2.5V6.66667H12.5" stroke="currentColor" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Save Settings
            </button>
        </div>
    </div>

</div>
