<?php
/**
 * A plain object abstracting settings.
 *
 * @package site-functionality
 */

namespace Site_Functionality;

/**
 * Typed settings.
 */
class Settings {

	/**
	 * The current plugin version, as defined in the root plugin file, or a string hopefully in sync with the root file.
	 *
	 * @used-by Admin_Assets::enqueue_scripts()
	 * @used-by Admin_Assets::enqueue_styles()
	 * @used-by Frontend_Assets::enqueue_scripts()
	 * @used-by Frontend_Assets::enqueue_styles()
	 *
	 * @return string
	 */
	public function get_plugin_version(): string {
		return defined( 'SITE_FUNCTIONALITY_VERSION' )
			? SITE_FUNCTIONALITY_VERSION
			: '1.0.2';
	}

	/**
	 * The plugin basename, as defined in the root plugin file, or a string hopefully in sync with the true basename.
	 *
	 * @used-by Admin_Assets::enqueue_scripts()
	 * @used-by Admin_Assets::enqueue_styles()
	 * @used-by Frontend_Assets::enqueue_scripts()
	 * @used-by Frontend_Assets::enqueue_styles()
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return defined( 'SITE_FUNCTIONALITY_BASENAME' )
			? SITE_FUNCTIONALITY_BASENAME
			: 'site-functionality/site-functionality.php';
	}
}
