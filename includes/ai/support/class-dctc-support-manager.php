<?php
/**
 * DCTC Support Manager
 *
 * Core coordinator and bootstrap for Support Center services.
 *
 * @package Dragwyb_Click_To_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DCTC_Support_Manager
 */
class DCTC_Support_Manager {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Load support service classes.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$dir = plugin_dir_path( __FILE__ );

		require_once $dir . 'class-dctc-support-db.php';
		require_once $dir . 'class-dctc-support-permission-service.php';
		require_once $dir . 'class-dctc-support-event-service.php';
		require_once $dir . 'class-dctc-support-note-service.php';
		require_once $dir . 'class-dctc-support-category-service.php';
		require_once $dir . 'class-dctc-support-tag-service.php';
		require_once $dir . 'class-dctc-support-agent-service.php';
		require_once $dir . 'class-dctc-support-ticket-service.php';
	}
}
