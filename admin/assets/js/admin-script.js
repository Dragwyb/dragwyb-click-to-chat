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

            // Channel card selection
            $('.scw-channel-card').on('click', this.toggleChannel.bind(this));
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

            // Save current step to user meta
            this.saveCurrentStep();
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

        toggleChannel: function (e) {
            const $card = $(e.currentTarget);
            $card.toggleClass('active');

            // Update the corresponding input field visibility
            const channel = $card.data('channel');
            const $inputGroup = $('[data-channel-input="' + channel + '"]');
            const isActive = $card.hasClass('active');

            // Update hidden enabled flags for Facebook and WhatsApp
            if (channel === 'facebook') {
                $('#scw_facebook_enabled').val(isActive ? '1' : '0');
            } else if (channel === 'whatsapp') {
                $('#scw_whatsapp_enabled').val(isActive ? '1' : '0');
            }

            if (isActive) {
                $inputGroup.slideDown(300, function () {
                    // Focus on the first input field
                    $inputGroup.find('input:first').focus();
                });
            } else {
                $inputGroup.slideUp(300);
            }

            // Update preview
            this.updatePreview();
        },

        updatePreview: function () {
            const $preview = $('.scw-preview-device');
            let activeChannels = [];

            // Collect active channels
            $('.scw-channel-card.active').each(function () {
                const channel = $(this).data('channel');
                const name = $(this).find('.scw-channel-name').text();
                activeChannels.push(name);
            });

            // Update preview content
            if (activeChannels.length > 0) {
                let previewHTML = '<div style="text-align: center; padding: 20px;">';
                previewHTML += '<p style="color: #6b7280; margin-bottom: 15px;">Active Channels:</p>';
                previewHTML += '<div style="display: flex; flex-direction: column; gap: 10px; align-items: center;">';

                activeChannels.forEach(function (channel) {
                    previewHTML += '<div style="background: #f3f4f6; padding: 8px 16px; border-radius: 20px; font-size: 14px; color: #374151;">' + channel + '</div>';
                });

                previewHTML += '</div></div>';
                $preview.html(previewHTML);
            } else {
                $preview.html('<p style="text-align: center; color: #9ca3af; padding: 20px;">Select channels to see preview</p>');
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
                nonce: scw_admin.nonce,
                fb: $('#scw_facebook_page_id').val(),
                wa: $('#scw_whatsapp_number').val(),
                lc: $('#scw_enable_live_chat').is(':checked') ? '1' : '0',
                // Channel enabled states
                fb_enabled: $('#scw_facebook_enabled').val(),
                wa_enabled: $('#scw_whatsapp_enabled').val(),
                // Widget customization settings
                widget_position: $('input[name="scw_widget_position"]:checked').val(),
                widget_color: $('#scw_widget_color').val(),
                widget_size: $('#scw_widget_size').val(),
                // Custom position settings
                custom_bottom: $('#scw_custom_bottom').val(),
                custom_horizontal: $('#scw_custom_horizontal').val(),
                custom_side: $('#scw_custom_side').val(),
                // Icon settings
                icon_type: $('input[name="scw_icon_type"]:checked').val(),
                custom_icon_url: $('#scw_custom_icon_url').val(),
                icon_rotation: $('#scw_icon_rotation').val(),
                icon_scale: $('#scw_icon_scale').val(),
                // Triggers and targeting settings
                show_on_desktop: $('#scw_show_on_desktop').is(':checked') ? '1' : '0',
                show_on_mobile: $('#scw_show_on_mobile').is(':checked') ? '1' : '0',
                time_delay: $('#scw_time_delay').val()
            };

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
        },

        saveCurrentStep: function () {
            // Save to user meta via AJAX (optional - for persistence)
            $.post(ajaxurl, {
                action: 'scw_save_current_step',
                nonce: scw_admin.nonce,
                step: currentStep
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        SCW_Admin.init();
    });

})(jQuery);
