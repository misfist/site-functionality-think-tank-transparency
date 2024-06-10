<?php
/**
 * Content Post_Types
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Post_Types;

use Site_Functionality\Common\Abstracts\Base;
use Site_Functionality\App\Post_Types\Donor;
use Site_Functionality\App\Post_Types\Think_Tank;
use Site_Functionality\App\Post_Types\Transaction;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Post_Types extends Base {

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
		new Think_Tank( $this->settings );
		new Transaction( $this->settings );
	}

}
