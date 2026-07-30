<?php
/**
 * Dragwyb AI Elementor Widget
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DCTC_AI_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dctc_ai_widget';
	}

	public function get_title() {
		return __( 'AI Chatbot', 'dragwyb-click-to-chat' );
	}

	public function get_icon() {
		return 'eicon-comments';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	protected function render() {
		$plugin = DCTC_AI_Module::get_instance();
		$plugin->dctc_ai_do_enqueue_frontend_assets();
		$plugin->dctc_ai_render_chatbot_ui( true );
	}
}

class DCTC_AI_Elementor {

	public function __construct() {
		add_action( 'elementor/widgets/register', [ $this, 'dctc_ai_register_widgets' ] );
	}

	public function dctc_ai_register_widgets( $widgets_manager ) {
		$widgets_manager->register( new DCTC_AI_Elementor_Widget() );
	}
}

new DCTC_AI_Elementor();
