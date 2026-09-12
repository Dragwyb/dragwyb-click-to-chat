/**
 * Dragwyb Click To Chat - Frontend JS
 */

"use strict";

window.dctcHideGreeting = function () {
    var greeting = document.getElementById('dctc-greeting-message');
    if (greeting) {
        greeting.classList.add('dctc-hidden');
    }
};

window.dctcToggleMenu = function () {
    var menu = document.getElementById('dctc-menu');
    if (menu) {
        menu.classList.toggle('dctc-open');
        window.dctcHideGreeting();
    }
}

// Open specific channel widget
function dctcOpenWidget(slug) {
    window.dctcHideGreeting();
    var widget = document.getElementById('dctc-chat-widget-' + slug);
    if (widget) {
        // Hide other open widgets first
        var allWidgets = document.querySelectorAll('.dctc-chat-widget');
        allWidgets.forEach(function (w) {
            w.style.display = 'none';
        });

        widget.style.display = 'flex';
        // Focus input
        setTimeout(function () {
            var input = document.getElementById('dctc-input-' + slug);
            if (input) input.focus();
        }, 100);
    }
}

// Close widget
window.dctcCloseWidget = function (slug) {
    var widget = document.getElementById('dctc-chat-widget-' + slug);
    if (widget) {
        widget.style.display = 'none';
    }
}

// Send Message
window.dctcSendMessage = function (slug, urlPattern, rawValue) {
    var input = document.getElementById('dctc-input-' + slug);
    var message = input.value.trim();

    if (message === "") return; // Don't send empty

    var finalUrl = '';

    if (urlPattern.indexOf('%s') !== -1) {
        // Encode message
        var encodedMsg = encodeURIComponent(message);

        // For WhatsApp, we need to handle the number and text param differently
        // Standard pattern: https://wa.me/%s
        // If it's WhatsApp, we usually have a number in %s, so we need to append &text=...
        // But the registry defines url_pattern as: https://wa.me/%s. 
        // Wait, the current implementation blindly replaces %s with value.
        // If the user wants to send a message, we need to append it.

        if (slug === 'whatsapp') {
            // rawValue is the number. 
            // Pattern: https://wa.me/%s -> https://wa.me/123456
            // We want: https://wa.me/123456?text=EncodedMsg

            // Clean phone number (remove +, spaces, dashes, etc.)
            var cleanPhone = rawValue.replace(/\D/g, '');

            // Convert to api.whatsapp.com/send/?phone=... which works seamlessly across devices
            finalUrl = 'https://web.whatsapp.com/send/?phone=' + cleanPhone + '&text=' + encodedMsg;
        } else if (slug === 'sms') {
            // sms:number?body=message
            finalUrl = 'sms:' + rawValue + '?body=' + encodedMsg;
        } else if (slug === 'email') {
            // mailto:email?body=message
            finalUrl = 'mailto:' + rawValue + '?body=' + encodedMsg;
        } else if (slug === 'twitter') {
            // https://twitter.com/messages/compose?recipient_id=%s
            // Twitter doesn't easily support pre-filled DM text via simple link with recipient ID in same way, 
            // but let's try standard intent if possible or just open it.
            // For now, simpler to just open the link as is if specific param not known, 
            // OR just append text if supported. 
            // Let's stick to the URL pattern replacement for now, but maybe specialized for knowns.

            // If we can't easily append message to URL for some channels, we just open the channel.
            // But for WhatsApp/SMS/Email it is critical.

            finalUrl = urlPattern.replace('%s', encodeURIComponent(rawValue)); // Default fallback
        } else {
            // Generic fallback: try to append ?text= or &text= depending on url
            // This is risky.
            // For now, let's just stick to WhatsApp which is the primary use case requested.
            finalUrl = urlPattern.replace('%s', encodeURIComponent(rawValue));
        }

    } else {
        finalUrl = urlPattern;
    }

    window.open(finalUrl, '_blank');

    // Optional: Clear input or close widget?
    // dctcCloseWidget(slug);
}

// Check Enter Key
window.dctcCheckEnter = function (event, slug, urlPattern, rawValue) {
    if (event.key === 'Enter') {
        window.dctcSendMessage(slug, urlPattern, rawValue);
    }
}

// Optional: If dctcOpenChat is needed in future or for internal channels
window.dctcOpenChat = function () {
    console.log('Open Chat triggered');
}

// Close widget when clicking outside
document.addEventListener('click', function (event) {
    var isClickInsideWidget = event.target.closest('.dctc-chat-widget');
    var isClickOnTrigger = event.target.closest('.dctc-sub-btn');
    var isClickOnMainTrigger = event.target.closest('#dctc-widget-btn'); // Also ignore main trigger

    if (!isClickInsideWidget && !isClickOnTrigger && !isClickOnMainTrigger) {
        var allWidgets = document.querySelectorAll('.dctc-chat-widget');
        allWidgets.forEach(function (w) {
            w.style.display = 'none';
        });

        // Also hide emoji pickers
        document.querySelectorAll('.dctc-emoji-picker').forEach(function (p) {
            p.style.display = 'none';
        });
    }
});

window.dctcToggleEmoji = function (slug) {
    var pickerContainer = document.getElementById('dctc-emoji-picker-' + slug);

    if (pickerContainer) {
        // Lazy Load: Create picker if not exists
        if (!pickerContainer.querySelector('emoji-picker')) {
            var dataSource = pickerContainer.getAttribute('data-emoji-source');
            var picker = document.createElement('emoji-picker');
            picker.classList.add('dctc-emoji-picker-el');
            picker.dataset.slug = slug; // logical equivalent to setAttribute('data-slug', slug)
            picker.setAttribute('data-slug', slug); // explicit attribute for selector matching if needed
            if (dataSource) {
                picker.setAttribute('data-source', dataSource);
            }
            pickerContainer.appendChild(picker);
        }

        var isHidden = (pickerContainer.style.display === 'none' || pickerContainer.style.display === '');
        pickerContainer.style.display = isHidden ? 'block' : 'none'; // Use block for container
    } else {
        console.error('Emoji picker container not found for slug:', slug);
    }
};

// Initialize listeners for the custom emoji-picker elements
document.addEventListener('DOMContentLoaded', function () {
    // We delegate the event or attach to all existing pickers
    // Since the script might load after DOM, and elements are in DOM.
    // However, custom elements fire their own events.

    // We can use event delegation on the document body if the event bubbles.
    // 'emoji-click' bubbles: true (checked docs).

    document.body.addEventListener('emoji-click', function (event) {
        // Check if the event came from one of our pickers
        if (event.target.classList.contains('dctc-emoji-picker-el')) {
            var slug = event.target.getAttribute('data-slug');
            if (slug) {
                var input = document.getElementById('dctc-input-' + slug);
                if (input) {
                    input.value += event.detail.unicode;
                    input.focus();
                }
            }
        }
    });
});

// Animate Greeting Message after 2 seconds
setTimeout(function () {
    var greeting = document.getElementById('dctc-greeting-message');
    if (greeting && !greeting.classList.contains('dctc-hidden')) {
        greeting.classList.add('dctc-visible');
    }
}, 2000);
