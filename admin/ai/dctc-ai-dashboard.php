<?php
/**
 * Dragwyb AI Chatbot Dashboard
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// React mount point
echo '<div id="dctc-ai-admin-root"></div>';

?>
<noscript>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'AI Assistant requires JavaScript to be enabled in your browser to load the dashboard.', 'dragwyb-click-to-chat' ); ?></p>
    </div>
</noscript>
