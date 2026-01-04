/**
 * Social Chat Widget - Admin JavaScript
 */

(function ($) {
    'use strict';

    let currentStep = 0;
    const totalSteps = 4;

    const SCW_Admin = {

        init: function () {
            this.bindEvents();
            this.loadCurrentStep();
            this.updatePreview(); // Show preview based on current settings
        },

        bindEvents: function () {
            // Tab navigation
            $('.scw-tab').on('click', this.handleTabClick.bind(this));

            // Next/Back buttons
            $('#scw-next-btn').on('click', this.nextStep.bind(this));
            $('#scw-back-btn').on('click', this.prevStep.bind(this));

            // Save button
            $('#scw-save-btn').on('click', this.saveSettings.bind(this));

            // Listen for changes on all inputs that affect the preview
            // Note: section-channels.php has its own click handler for cards, 
            $(document).on('change input',
                'input[name^="scw_"], select[name^="scw_"]',
                this.updatePreview.bind(this)
            );

            // Also listen to clicks on channel cards for immediate preview update
            $('.scw-channel-card').on('click', () => {
                setTimeout(() => this.updatePreview(), 50); // slight delay to allow active class toggle
            });

            // Watch specific widget customization inputs directly for smoother real-time updates
            $('#scw_widget_color, #scw_widget_size, #scw_icon_rotation, #scw_icon_scale').on('input', this.updatePreview.bind(this));
            $('#scw_custom_bottom, #scw_custom_horizontal').on('input', this.updatePreview.bind(this));
            $('input[name="scw_widget_position"], input[name="scw_icon_type"], select[name="scw_custom_side"], select[name="scw_custom_vertical_align"]').on('change', this.updatePreview.bind(this));
            $('#scw_widget_size_unit, #scw_custom_bottom_unit, #scw_custom_horizontal_unit').on('change', this.updatePreview.bind(this));
        },

        handleTabClick: function (e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const step = $tab.data('step');
            this.goToStep(step);
        },

        goToStep: function (step) {
            if (step < 0 || step >= totalSteps) return;

            currentStep = step;

            // Update tabs
            $('.scw-tab').removeClass('active').eq(step).addClass('active');

            // Update sections
            $('.scw-section').removeClass('active').eq(step).addClass('active');

            // Update navigation buttons
            this.updateNavigationButtons();
        },

        nextStep: function (e) {
            e.preventDefault();
            if (currentStep < totalSteps - 1) {
                // Mark current tab as completed
                $('.scw-tab').eq(currentStep).addClass('completed');
                this.goToStep(currentStep + 1);
            }
        },

        prevStep: function (e) {
            e.preventDefault();
            if (currentStep > 0) {
                this.goToStep(currentStep - 1);
            }
        },

        updateNavigationButtons: function () {
            const $backBtn = $('#scw-back-btn');
            const $nextBtn = $('#scw-next-btn');

            // Back button
            if (currentStep === 0) {
                $backBtn.prop('disabled', true);
            } else {
                $backBtn.prop('disabled', false);
            }

            // Next button
            if (currentStep === totalSteps - 1) {
                $nextBtn.hide();
            } else {
                $nextBtn.show();
            }
        },

        // Removed toggleChannel logic as it is handled in section-channels.php 
        // We only need to react to changes.

        updatePreview: function () {
            const $previewContainer = $('.scw-preview-device');

            // 1. Gather Settings
            const widgetColor = $('#scw_widget_color').val() || '#8e44ad';
            const widgetSize = $('#scw_widget_size').val() || 60;
            const widgetSizeUnit = $('#scw_widget_size_unit').val() || 'px';
            const widgetSizeStr = widgetSize + widgetSizeUnit;

            const widgetPosition = $('input[name="scw_widget_position"]:checked').val() || 'right';

            // Custom position details
            let positionStyle = '';

            // Default styling vars
            let vertAlign = 'bottom'; // 'top' or 'bottom'
            let side = 'right'; // 'left' or 'right'

            let vertDistStr = '20px';
            let horizDistStr = '20px';

            if (widgetPosition === 'custom') {
                const vertDist = $('#scw_custom_bottom').val() || 20;
                const vertUnit = $('#scw_custom_bottom_unit').val() || 'px';
                vertDistStr = vertDist + vertUnit;

                const horizDist = $('#scw_custom_horizontal').val() || 20;
                const horizUnit = $('#scw_custom_horizontal_unit').val() || 'px';
                horizDistStr = horizDist + horizUnit;

                side = $('#scw_custom_side').val() || 'right';
                vertAlign = $('#scw_custom_vertical_align').val() || 'bottom';

                // Handle top/auto bottom/auto logic
                // For preview CSS, we just set the specific property
                positionStyle = `${vertAlign}: ${vertDistStr}; ${side}: ${horizDistStr};`;
            } else {
                side = widgetPosition === 'left' ? 'left' : 'right';
                vertAlign = 'bottom';
                positionStyle = `bottom: 20px; ${side}: 20px;`;
                vertDistStr = '20px';
                horizDistStr = '20px';
            }

            // Icon Settings
            const iconType = $('input[name="scw_icon_type"]:checked').val() || 'chat';
            const iconRotation = $('#scw_icon_rotation').val() || 0;
            const iconScale = $('#scw_icon_scale').val() || 1;
            const iconTransform = `transform: rotate(${iconRotation}deg) scale(${iconScale});`;

            // Active Channels
            let activeChannels = [];
            $('.scw-channel-card.active').each(function () {
                const $card = $(this);
                const channelSlug = $card.data('channel');
                const channelName = $card.find('.scw-channel-name').text();

                // Get custom icon if exists
                const customIconUrl = $('#scw_' + channelSlug + '_custom_icon').val();

                let iconHtml = '';
                let colorData = $card.find('.scw-channel-icon').attr('style'); // Default style e.g. "background: #...;"

                if (customIconUrl) {
                    // If custom icon, make background transparent and icon full width
                    colorData = 'background: transparent; box-shadow: none;'; // Remove default shadow/bg
                    iconHtml = `<img src="${customIconUrl}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                } else {
                    // Get the SVG content directly from the card
                    let rawSvg = $card.find('.scw-channel-icon svg').prop('outerHTML');
                    // Ensure default SVG is centered and sized correctly inside the preview bubble
                    // The preview bubble is 50x50. Default SVG is 32x32.
                    // We replace width/height to be safe.
                    rawSvg = rawSvg.replace(/width=".*?"/g, 'width="24"').replace(/height=".*?"/g, 'height="24"');
                    iconHtml = rawSvg;
                }

                activeChannels.push({
                    name: channelName,
                    icon: iconHtml,
                    style: colorData
                });
            });

            // 2. Build Preview HTML
            // Main Button Icon content
            let mainIconHtml = '';
            if (iconType === 'custom') {
                const customUrl = $('#scw_custom_icon_url').val();
                if (customUrl) {
                    mainIconHtml = `<img src="${customUrl}" style="width: 100%; height: 100%; object-fit: contain; ${iconTransform}" />`;
                } else {
                    mainIconHtml = this.getDefaultIconSvg('chat', iconTransform);
                }
            } else {
                mainIconHtml = this.getDefaultIconSvg(iconType, iconTransform);
            }

            const btnCss = `
                position: absolute;
                ${positionStyle}
                width: ${widgetSizeStr}; 
                height: ${widgetSizeStr}; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                z-index: 20;
                cursor: pointer;
                color: ${widgetColor}; 
            `;

            // Sub-menu items logic
            let menuStyle = '';

            // Calculate menu position using CSS calc() to handle mixed units
            // Menu vertical pos = widget_distance + widget_size + 10px
            const menuVertDist = `calc(${vertDistStr} + ${widgetSizeStr} + 10px)`;

            // Menu horizontal pos = widget_distance + 5px (for slight centering adjustment or alignment)
            // Or typically it aligns with center of button? 
            // The frontend logic uses:
            // "right: 25px" for preset (widget is right: 20px, so +5px)
            // "right: calc(horizontal_dist + 5px)" for custom
            // We just stick to that
            const menuHorizDist = `calc(${horizDistStr} + 5px)`;

            menuStyle = `
                position: absolute; 
                ${vertAlign === 'bottom' ? 'bottom' : 'top'}: ${menuVertDist}; 
                ${side}: ${menuHorizDist};
                display: flex; 
                flex-direction: column; 
                gap: 10px;
                align-items: center;
                z-index: 10;
            `;

            // Build Menu HTML
            let menuHtml = `<div class="scw-menu-preview" style="${menuStyle}">`;

            activeChannels.forEach(channel => {
                // Check if it's a custom icon (transparent bg)
                const isCustom = channel.style.includes('transparent');
                const innerSize = isCustom ? 'width: 100%; height: 100%;' : 'width: 24px; height: 24px;';

                menuHtml += `
                    <div class="scw-sub-btn-preview" title="${channel.name}" 
                        style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); ${channel.style}">
                        <div style="${innerSize} display: flex; align-items: center; justify-content: center;">
                             ${channel.icon} 
                        </div>
                    </div>
                `;
            });
            menuHtml += `</div>`;

            const btnHtml = `
                <div class="scw-widget-btn-preview" style="${btnCss}">
                    <div style="width: 100%; height: 100%; display: flex;">
                        ${mainIconHtml} 
                    </div>
                </div>
            `;

            // Combine
            const previewContent = `
                <div class="scw-live-preview-box" style="width: 100%; height: 100%; position: relative;">
                    ${menuHtml}
                    ${btnHtml}
                </div>
                <style>
                    /* Container styles */
                    .scw-preview-device {
                        position: relative;
                        overflow: hidden;
                        background-color: #fff; 
                        border: 1px solid #e5e7eb;
                        height: 400px; /* fixed height for absolute positioning context */
                    }
                    /* Ensure SVG fills use the parent color */
                    .scw-widget-btn-preview svg { fill: currentColor; width: 100%; height: 100%; }
                    .scw-sub-btn-preview svg { fill: white; width: 24px; height: 24px; }
                </style>
            `;

            $previewContainer.html(previewContent);

            // Add click toggle behavior for realism
            $previewContainer.find('.scw-widget-btn-preview').on('click', function () {
                const $menu = $previewContainer.find('.scw-menu-preview');
                if ($menu.css('opacity') === '0' || $menu.is(':hidden')) {
                    $menu.css({ opacity: 1, visibility: 'visible' }).show();
                } else {
                    $menu.css({ opacity: 0, visibility: 'hidden' }).hide();
                }
            });
        },

        getDefaultIconSvg: function (type, transformStyle) {
            const style = `style="${transformStyle}"`;
            switch (type) {
                case 'chat':
                    return `<svg viewBox="0 0 24 24" ${style}><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>`;
                case 'message':
                    return `<svg viewBox="0 0 24 24" ${style}><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>`;
                case 'support':
                    return `<svg viewBox="0 0 24 24" ${style}><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`;
                case 'phone':
                    return `<svg viewBox="0 0 24 24" ${style}><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>`;
                default:
                    return `<svg viewBox="0 0 24 24" ${style}><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>`;
            }
        },

        saveSettings: function (e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const originalText = $btn.html();

            // Disable button and show loading
            $btn.prop('disabled', true).addClass('loading');

            // Collect form data
            const formData = {
                action: 'scw_save_settings',
                nonce: scw_admin.nonce
            };

            // Phase 1: Social channels only
            const phase1Channels = ['whatsapp', 'facebook', 'phone', 'email', 'instagram', 'telegram', 'sms', 'twitter', 'linkedin'];

            phase1Channels.forEach(function (slug) {
                // Collect enabled state
                const enabled = $('#scw_' + slug + '_enabled').val();
                if (enabled !== undefined) {
                    formData[slug + '_enabled'] = enabled;
                }

                // Collect value
                const $valueInput = $('#scw_' + slug + '_value');
                if ($valueInput.length) {
                    if ($valueInput.attr('type') === 'checkbox') {
                        formData[slug + '_value'] = $valueInput.is(':checked') ? '1' : '0';
                    } else {
                        formData[slug + '_value'] = $valueInput.val();
                    }
                }

                // Collect device visibility checkboxes
                formData[slug + '_desktop'] = $('#scw_' + slug + '_desktop').is(':checked') ? '1' : '0';
                formData[slug + '_mobile'] = $('#scw_' + slug + '_mobile').is(':checked') ? '1' : '0';

                // Collect custom icon
                formData[slug + '_custom_icon'] = $('#scw_' + slug + '_custom_icon').val();
            });

            // Add widget customization settings
            formData.widget_position = $('input[name="scw_widget_position"]:checked').val();
            formData.widget_color = $('#scw_widget_color').val();
            formData.widget_size = $('#scw_widget_size').val();
            formData.widget_size_unit = $('#scw_widget_size_unit').val();

            // Custom position settings
            formData.custom_bottom = $('#scw_custom_bottom').val();
            formData.custom_bottom_unit = $('#scw_custom_bottom_unit').val();
            formData.custom_horizontal = $('#scw_custom_horizontal').val();
            formData.custom_horizontal_unit = $('#scw_custom_horizontal_unit').val();
            formData.custom_side = $('#scw_custom_side').val();
            formData.custom_vertical_align = $('#scw_custom_vertical_align').val();
            // Icon settings
            formData.icon_type = $('input[name="scw_icon_type"]:checked').val();
            formData.custom_icon_url = $('#scw_custom_icon_url').val();
            formData.icon_rotation = $('#scw_icon_rotation').val();
            formData.icon_scale = $('#scw_icon_scale').val();
            // Triggers and targeting settings
            formData.show_on_desktop = $('#scw_show_on_desktop').is(':checked') ? '1' : '0';
            formData.show_on_mobile = $('#scw_show_on_mobile').is(':checked') ? '1' : '0';
            formData.time_delay = $('#scw_time_delay').val();

            // AJAX save
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        SCW_Admin.showSuccessMessage('Settings saved successfully!');

                        // Mark current tab as completed
                        $('.scw-tab').eq(currentStep).addClass('completed');
                    } else {
                        alert('Error saving settings. Please try again.');
                    }
                },
                error: function () {
                    alert('Error saving settings. Please try again.');
                },
                complete: function () {
                    // Re-enable button and remove loading state
                    $btn.prop('disabled', false).removeClass('loading').html(originalText);
                }
            });
        },

        showSuccessMessage: function (message) {
            const $msg = $('.scw-success-message');
            $msg.text(message).addClass('show');

            setTimeout(function () {
                $msg.removeClass('show');
            }, 3000);
        },

        loadCurrentStep: function () {
            // Get step from URL or default to 0
            const urlParams = new URLSearchParams(window.location.search);
            const step = parseInt(urlParams.get('step')) || 0;
            this.goToStep(step);
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        SCW_Admin.init();

        // --- Channel Icon Uploader Logic ---
        // We attach this to document to ensure it works for dynamically generated elements if any, 
        // though these inputs are static.

        // Open Media Uploader
        $(document).on('click', '.scw-upload-channel-icon', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var target = $btn.data('target');

            // Create a new media frame every time to avoid scope/state issues
            var frame = wp.media({
                title: 'Choose Channel Icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();

                // Update Inputs and UI
                $('#scw_' + target + '_custom_icon').val(attachment.url);
                $('.scw-icon-filename-' + target).text(attachment.filename || 'Icon selected');

                // Show remove button
                $('.scw-remove-channel-icon[data-target="' + target + '"]').show();

                // Trigger preview update
                SCW_Admin.updatePreview();
            });

            frame.open();
        });

        // Remove Icon
        $(document).on('click', '.scw-remove-channel-icon', function (e) {
            e.preventDefault();
            var target = $(this).data('target');

            $('#scw_' + target + '_custom_icon').val('');
            $('.scw-icon-filename-' + target).text('Default Icon');
            $(this).hide();

            // Trigger preview update
            SCW_Admin.updatePreview();
        });
    });

})(jQuery);

