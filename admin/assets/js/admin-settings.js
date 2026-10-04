/**
 * Dragwyb Click To Chat - Settings Page JavaScript
 * Handles 2-tab navigation (General vs Import/Export), dynamic badge switches, and AJAX saving.
 */

(function ($) {
    'use strict';

    const DCTC_Settings_App = {
        init: function () {
            this.bindEvents();
            this.initTabFromUrl();
        },

        bindEvents: function () {
            // Tab switching
            $('.dctc-settings-tab-btn').on('click', this.handleTabSwitch.bind(this));

            // Module dynamic badges
            $('#dctc_gen_channels_enabled').on('change', function () {
                const checked = $(this).is(':checked');
                $('#dctc-badge-channels')
                    .text(checked ? 'Active' : 'Disabled')
                    .css({
                        background: checked ? '#ecfdf5' : '#f3f4f6',
                        color: checked ? '#065f46' : '#6b7280',
                        border: checked ? '1px solid #a7f3d0' : '1px solid #e5e7eb'
                    });
            });

            $('#dctc_gen_ai_enabled').on('change', function () {
                const checked = $(this).is(':checked');
                $('#dctc-badge-ai')
                    .text(checked ? 'Active' : 'Disabled')
                    .css({
                        background: checked ? '#ecfdf5' : '#f3f4f6',
                        color: checked ? '#065f46' : '#6b7280',
                        border: checked ? '1px solid #a7f3d0' : '1px solid #e5e7eb'
                    });
            });

            $('#dctc_gen_support_enabled').on('change', function () {
                const checked = $(this).is(':checked');
                $('#dctc-badge-support')
                    .text(checked ? 'Active' : 'Disabled')
                    .css({
                        background: checked ? '#ecfdf5' : '#f3f4f6',
                        color: checked ? '#065f46' : '#6b7280',
                        border: checked ? '1px solid #a7f3d0' : '1px solid #e5e7eb'
                    });
            });

            // Save Settings via AJAX
            $('#dctc-general-save-btn').on('click', this.saveGeneralSettings.bind(this));

            // Copy Shortcode Button
            $('.dctc-copy-btn').on('click', function (e) {
                e.preventDefault();
                const text = $(this).data('clipboard-text') || '[dctc-widget]';
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        const $span = $(this).find('.dctc-copy-text');
                        $span.text('Copied!');
                        setTimeout(() => $span.text('Copy'), 2000);
                    });
                }
            });
        },

        handleTabSwitch: function (e) {
            e.preventDefault();
            const tab = $(e.currentTarget).data('tab');
            this.switchTab(tab);
        },

        switchTab: function (tab) {
            $('.dctc-settings-tab-btn').removeClass('active');
            $(`.dctc-settings-tab-btn[data-tab="${tab}"]`).addClass('active');

            $('.dctc-settings-tab-content').hide().removeClass('active');
            $(`#dctc-tab-${tab}`).show().addClass('active');

            // Update URL hash without jumping
            if (history.pushState) {
                const newUrl = window.location.pathname + window.location.search.replace(/&tab=[^&]*/, '') + `&tab=${tab}`;
                history.pushState(null, null, newUrl);
            }
        },

        initTabFromUrl: function () {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || (window.location.hash ? window.location.hash.replace('#', '') : 'general');
            if (tab === 'import-export') {
                this.switchTab('import-export');
            } else {
                this.switchTab('general');
            }
        },

        saveGeneralSettings: function (e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const originalHtml = $btn.html();

            $btn.prop('disabled', true).addClass('loading');

            const formData = {
                action: 'dctc_save_settings',
                nonce: dctc_admin ? dctc_admin.nonce : '',
                channels_enabled: $('#dctc_gen_channels_enabled').is(':checked') ? '1' : '0',
                ai_assistant_enabled: $('#dctc_gen_ai_enabled').is(':checked') ? '1' : '0',
                support_center_enabled: $('#dctc_gen_support_enabled').is(':checked') ? '1' : '0'
            };

            $.ajax({
                url: dctc_admin ? dctc_admin.ajaxurl : ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        DCTC_Settings_App.showToast('Settings saved successfully!');
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Error saving settings.');
                    }
                },
                error: function () {
                    alert('Error saving settings. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('loading').html(originalHtml);
                }
            });
        },

        showToast: function (message) {
            const $msg = $('.dctc-success-message');
            $msg.text(message).addClass('show');
            setTimeout(function () {
                $msg.removeClass('show');
            }, 3000);
        }
    };

    $(document).ready(function () {
        DCTC_Settings_App.init();
    });

})(jQuery);
