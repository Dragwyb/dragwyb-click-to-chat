/**
 * Dragwyb Click To Chat - Frontend JS
 */

"use strict";

function dctcToggleMenu() {
    var menu = document.getElementById('dctc-menu');
    if (menu) {
        menu.classList.toggle('dctc-open');
    }
}

// Optional: If dctcOpenChat is needed in future or for internal channels
function dctcOpenChat() {
    console.log('Open Chat triggered');
}
