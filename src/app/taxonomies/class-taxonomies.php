<?php
/**
 * Content Taxonomies
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Taxonomies;

use Site_Functionality\Common\Abstracts\Base;
use Site_Functionality\App\Taxonomies\Donor;
use Site_Functionality\App\Taxonomies\Donor_Type;
use Site_Functionality\App\Taxonomies\Think_Tank;
use Site_Functionality\App\Taxonomies\Year;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Taxonomies extends Base {

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
		new Donor( $this->settings );
		new Donor_Type( $this->settings );
		new Think_Tank( $this->settings );
		new Year( $this->settings );
	}

}
