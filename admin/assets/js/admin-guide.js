/**
 * Dragwyb Click To Chat - User Guide Page JavaScript
 * Handles 3-tab navigation, one-click feature activations, and shortcode copying.
 */

(function ($) {
    'use strict';

    const DCTC_Guide_App = {
        init: function () {
            this.bindEvents();
            this.initTabFromUrl();
        },

        bindEvents: function () {
            // Tab switching
            $('.dctc-guide-tab-btn').on('click', this.handleTabSwitch.bind(this));

            // Instant Feature Activation Button on Disabled State
            $(document).on('click', '.dctc-guide-activate-btn', this.handleFeatureActivation.bind(this));

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
            $('.dctc-guide-tab-btn').removeClass('active');
            $(`.dctc-guide-tab-btn[data-tab="${tab}"]`).addClass('active');

            $('.dctc-guide-tab-content').hide().removeClass('active');
            $(`#dctc-guide-tab-${tab}`).show().addClass('active');

            // Update URL hash without jumping
            if (history.pushState) {
                const newUrl = window.location.pathname + window.location.search.replace(/&tab=[^&]*/, '') + `&tab=${tab}`;
                history.pushState(null, null, newUrl);
            }
        },

        initTabFromUrl: function () {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || (window.location.hash ? window.location.hash.replace('#', '') : 'channels');
            if (['channels', 'ai', 'support'].includes(tab)) {
                this.switchTab(tab);
            } else {
                this.switchTab('channels');
            }
        },

        handleFeatureActivation: function (e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const feature = $btn.data('feature');
            const originalHtml = $btn.html();

            $btn.prop('disabled', true).html('Activating…');

            const formData = {
                action: 'dctc_save_settings',
                nonce: dctc_admin ? dctc_admin.nonce : ''
            };

            if (feature === 'channels') {
                formData.channels_enabled = '1';
            } else if (feature === 'ai') {
                formData.ai_assistant_enabled = '1';
            } else if (feature === 'support') {
                formData.support_center_enabled = '1';
            }

            $.ajax({
                url: dctc_admin ? dctc_admin.ajaxurl : ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        DCTC_Guide_App.showToast('Feature enabled successfully! Reloading…');
                        setTimeout(function () {
                            window.location.reload();
                        }, 800);
                    } else {
                        alert(response.data && response.data.message ? response.data.message : 'Error activating feature.');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function () {
                    alert('Error activating feature. Please try again.');
                    $btn.prop('disabled', false).html(originalHtml);
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
        DCTC_Guide_App.init();
    });

})(jQuery);
