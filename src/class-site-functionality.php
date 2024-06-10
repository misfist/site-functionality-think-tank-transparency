<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @link       https://github.com/misfist/site-functionality
 * @since      1.0.0
 *
 * @package    site-functionality
 */

namespace Site_Functionality;

use Site_Functionality\App\Admin\Admin_Assets;
use Site_Functionality\App\Admin\Admin_Settings;
use Site_Functionality\App\Frontend\Frontend_Assets;
use Site_Functionality\Common\WP_Includes\I18n;
use Site_Functionality\App\Custom_Fields\Custom_Fields;
use Site_Functionality\App\Post_Types\Post_Types;
use Site_Functionality\App\Taxonomies\Taxonomies;
use Site_Functionality\Integrations\Integrations;

/**
 * Hooks the plugin's classes to WordPress's actions and filters.
 */
class Site_Functionality {

	/**
	 * The plugin settings.
	 *
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param Settings $settings The plugin settings, to pass to classes as they are instantiated.
	 */
	public function __construct( Settings $settings ) {

		$this->settings = $settings;

		$this->set_locale();
		// $this->define_admin_hooks();
		// $this->define_frontend_hooks();
		$this->load_dependencies();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks(): void {

		$admin_assets = new Admin_Assets( $this->settings );

		add_action( 'admin_enqueue_scripts', array( $admin_assets, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_assets, 'enqueue_scripts' ) );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_frontend_hooks(): void {

		$frontend_assets = new Frontend_Assets( $this->settings );

		add_action( 'wp_enqueue_scripts', array( $frontend_assets, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $frontend_assets, 'enqueue_scripts' ) );

	}

	/**
	 * Load dependent classes
	 *
	 * @since    1.0.0
	 */
	protected function load_dependencies(): void {
		$post_types     = new Post_Types( $this->settings );
		$taxonomies     = new Taxonomies( $this->settings );
		$custom_fields  = new Custom_Fields( $this->settings );
		$admin_settings = new Admin_Settings( $this->settings );
		$integrations   = new Integrations( $this->settings );
	}

}
