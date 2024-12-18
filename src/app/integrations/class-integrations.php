<?php
/**
 * Integrations
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\Integrations;

use Site_Functionality\Common\Abstracts\Base;
use Site_Functionality\Integrations\CLI\Commands;
use Site_Functionality\Integrations\WP_Import\Import;
// use Site_Functionality\Integrations\WP_Import\Actions;
// use Site_Functionality\Integrations\API\API;
// use Site_Functionality\Integrations\Data_Tables\Data_Tables;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Integrations extends Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $settings = array() ) {
		parent::__construct( $settings );
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		// new Commands();
		Import::get_instance();
		// new Import();
	}

}
