<?php
/**
 * Admin Settings
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Admin;

use Site_Functionality\Common\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Settings extends Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $settings ) {
		parent::__construct( $settings );
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'disable_comments' ) );
		add_action( 'admin_menu', array( $this, 'disable_admin_menu_comments' ) );
		add_action( 'init', array( $this, 'disable_admin_bar_menu_comments' ) );
	}

	/**
	 * Disable comments
	 *
	 * @return void
	 */
	public function disable_comments() : void {
		add_filter( 'comments_open', '\__return_false', 20, 2 );
		add_filter( 'pings_open', '\__return_false', 20, 2 );
		add_filter( 'comments_array', '\__return_empty_array', 10, 2 );
	}

	/**
	 * Remove comments menu admin men
	 *
	 * @return void
	 */
	public function disable_admin_menu_comments() : void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Remove comments menu from admin bar
	 *
	 * @return void
	 */
	public function disable_admin_bar_menu_comments() : void {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}

}
