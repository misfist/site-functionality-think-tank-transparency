<?php
/**
 * WP Action Network Events
 *
 * @package   Site_Functionality
 */
namespace Site_Functionality\Common\Abstracts;

use Site_Functionality\Settings;

/**
 * The Base class which can be extended by other classes to load in default methods
 *
 * @package Site_Functionality\Common\Abstracts
 * @since 1.0.0
 */
abstract class Base {

	/**
	 * The plugin settings.
	 *
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * The data.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $data
	 */
	public $data;

	/**
	 * The errors.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $errors
	 */
	protected $errors;

	/**
	 * Base constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		$this->init();
	}

	/**
	 * Initialize stuff
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Handle Errors
	 *
	 * @return void
	 */
	protected function handle_error( $exception ): void {
		$this->errors[] = $exception;
	}

	/**
	 * Set processing data
	 *
	 * @param string $prop
	 * @param mixed  $value
	 * @return void
	 */
	public function set_data( $prop, $value ): void {
		$this->data[ $prop ] = $value;
	}

	/**
	 * Get processing data
	 *
	 * @param string $prop
	 * @return array $this->data
	 */
	public function get_data( $prop ) {
		return $this->data[ $prop ];
	}
}
