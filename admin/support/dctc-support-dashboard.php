<?php
/**
 * Dragwyb Support Center Standalone Admin Container
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Dedicated React mount point for standalone Support Center
echo '<div id="dctc-support-admin-root" class="dctc-support-admin-fullwidth"></div>';
?>
<noscript>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'Support Center requires JavaScript to be enabled in your browser.', 'dragwyb-click-to-chat' ); ?></p>
    </div>
</noscript>
