<?php
/**
 * Integrations
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\Integrations\Data_Tables;

use Site_Functionality\Common\Abstracts\Base;
use Site_Functionality\Integrations\Data_Tables\Think_Tank;
use Site_Functionality\Integrations\Data_Tables\Donor;
use Site_Functionality\Integrations\Data_Tables\Data_Filters;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data_Tables extends Base {

	public const TABLE_ID = 'fundingTable';

	public const APP_ID = 'data-tables';

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
		$data_filters = new Data_Filters();
		$think_tank   = new Think_Tank();
		$donor        = new Donor();
	}

}
