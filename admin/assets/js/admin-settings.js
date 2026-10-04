/**
 * Dragwyb Click To Chat - Settings Page JavaScript
 * Handles 2-tab navigation, dynamic drawer expand/collapse, badge switches, and AJAX saving.
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

            // Module 1: Channels switch & drawer
            $('#dctc_gen_channels_enabled').on('change', function () {
                const checked = $(this).is(':checked');
                const $card = $('#dctc-card-channels');
                const $badge = $('#dctc-badge-channels');
                const $drawer = $('#dctc-drawer-channels');

                if (checked) {
                    $card.removeClass('is-disabled').addClass('is-enabled');
                    $badge.text('Active').removeClass('is-inactive').addClass('is-active');
                    $drawer.slideDown(250);
                } else {
                    $card.removeClass('is-enabled').addClass('is-disabled');
                    $badge.text('Disabled').removeClass('is-active').addClass('is-inactive');
                    $drawer.slideUp(250);
                }
            });

            // Module 2: AI Assistant switch & drawer
            $('#dctc_gen_ai_enabled').on('change', function () {
                const checked = $(this).is(':checked');
                const $card = $('#dctc-card-ai');
                const $badge = $('#dctc-badge-ai');
                const $drawer = $('#dctc-drawer-ai');

                if (checked) {
                    $card.removeClass('is-disabled').addClass('is-enabled');
                    $badge.text('Active').removeClass('is-inactive').addClass('is-active');
                    $drawer.slideDown(250);
                } else {
                    $card.removeClass('is-enabled').addClass('is-disabled');
                    $badge.text('Disabled').removeClass('is-active').addClass('is-inactive');
                    $drawer.slideUp(250);
                }
            });

            // Module 3: Support Center switch & drawer
            $('#dctc_gen_support_enabled').on('change', function () {
                const checked = $(this).is(':checked');
                const $card = $('#dctc-card-support');
                const $badge = $('#dctc-badge-support');
                const $drawer = $('#dctc-drawer-support');

                if (checked) {
                    $card.removeClass('is-disabled').addClass('is-enabled');
                    $badge.text('Active').removeClass('is-inactive').addClass('is-active');
                    $drawer.slideDown(250);
                } else {
                    $card.removeClass('is-enabled').addClass('is-disabled');
                    $badge.text('Disabled').removeClass('is-active').addClass('is-inactive');
                    $drawer.slideUp(250);
                }
            });

            // Save Settings via AJAX
            $('#dctc-general-save-btn').on('click', this.saveGeneralSettings.bind(this));

            // Copy Shortcode Button
            $(document).on('click', '.dctc-copy-btn', function (e) {
                e.preventDefault();
                const text = $(this).data('clipboard-text') || '[dctc-widget]';
                const $btn = $(this);
                const $span = $btn.find('.dctc-copy-text');

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        $span.text('✓ Copied!');
                        $btn.addClass('is-copied');
                        setTimeout(() => {
                            $span.text('Copy');
                            $btn.removeClass('is-copied');
                        }, 2000);
                    });
                } else {
                    // Fallback
                    const temp = $('<input>');
                    $('body').append(temp);
                    temp.val(text).select();
                    document.execCommand('copy');
                    temp.remove();
                    $span.text('✓ Copied!');
                    $btn.addClass('is-copied');
                    setTimeout(() => {
                        $span.text('Copy');
                        $btn.removeClass('is-copied');
                    }, 2000);
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
