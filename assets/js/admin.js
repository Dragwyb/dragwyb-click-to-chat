(
    function ($) {
        class DCTC_Review_Form {
            constructor() {
                this.init();
            }

            init() {
                const $notice = $('.dragwyb-ctc-review-notice .dragwyb-ctc-review-notice-buttons button');
                $notice.on('click', this.dragwyb_ctc_review_dismiss);
            }

            dragwyb_ctc_review_dismiss(e) {
                const nonce = dragwyb_ctc_review_obj.nonce;
                const noticeWrp = jQuery(this).closest('.dragwyb-ctc-review-notice');
                jQuery.ajax({
                    url: dragwyb_ctc_review_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dragwyb_ctc_review_dismiss',
                        dragwyb_ctc_review_dismiss: true,
                        nonce: nonce,
                    },
                    success: function (response) {
                        noticeWrp.fadeOut(500);
                    },
                })
            }
        }

        new DCTC_Review_Form();
    }
)(jQuery)