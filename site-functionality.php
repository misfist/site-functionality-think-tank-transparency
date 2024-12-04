<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/misfist/rkc-plugin/
 * @since             1.0.0
 * @package           site-functionality
 *
 * @wordpress-plugin
 * Plugin Name:       Site Functionality
 * Plugin URI:        https://github.com/misfist/rkc-plugin/
 * Description:       Custom WordPress functionality.
 * Version:           1.0.2
 * Requires PHP:      8.0
 * Requires at least: 6.5
 * Author:            Pea
 * Author URI:        https://pealutz.me
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       site-functionality
 * Domain Path:       /languages
 *
 * GitHub Plugin URI: https://github.com/misfist/rkc-plugin/
 * Release Asset:     true
 */

namespace Site_Functionality;

use Site_Functionality\Common\WP_Includes\Activator;
use Site_Functionality\Common\WP_Includes\Deactivator;


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	throw new \Exception( 'WordPress required but not loaded.' );
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SITE_FUNCTIONALITY_VERSION', '1.0.2' );
define( 'SITE_FUNCTIONALITY_BASENAME', plugin_basename( __FILE__ ) );
define( 'SITE_FUNCTIONALITY_PATH', plugin_dir_path( __FILE__ ) );
define( 'SITE_FUNCTIONALITY_URL', trailingslashit( plugins_url( plugin_basename( __DIR__ ) ) ) );

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function instantiate_site_functionality(): Site_Functionality {

	$settings = new Settings();

	$plugin = new Site_Functionality( $settings );

	return $plugin;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and frontend-facing site hooks.
 */
$GLOBALS['site_functionality'] = instantiate_site_functionality();
