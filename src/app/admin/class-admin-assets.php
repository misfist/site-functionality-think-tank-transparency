<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/misfist/site-functionality
 * @since      1.0.0
 *
 * @package    site-functionality
 */

namespace Site_Functionality\App\Admin;

use Site_Functionality\App\Admin\Editor;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Admin_Assets {

	/**
	 * The plugin settings.
	 *
	 * @uses Settings::get_plugin_version() for caching.
	 * @uses Settings::get_plugin_basename() for determining the plugin URL.
	 *
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * Constructor
	 *
	 * @param Settings $settings The plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		$editor = new Editor( $this->settings );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$version = $this->settings->get_plugin_version();

		$plugin_dir = plugin_dir_url( $this->settings->get_plugin_basename() );

		wp_enqueue_style( 'site-functionality', $plugin_dir . 'assets/admin.css', array(), $version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$version = $this->settings->get_plugin_version();

		$plugin_dir = plugin_dir_url( $this->settings->get_plugin_basename() );

		wp_enqueue_script( 'site-functionality', $plugin_dir . 'assets/admin.js', array(), $version, true );

	}

}
