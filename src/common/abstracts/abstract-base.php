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
	public function __construct( $settings = array() ) {
		// $this->settings = $settings;
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

	/**
	 * Write a debug entry.
	 *
	 * @param string      $method   Calling method (use __METHOD__).
	 * @param string      $message  Debug message.
	 * @param string|null $file     Optional absolute log file path.
	 * @return void
	 */
	public static function log( string $method, string $message, ?string $file = null ): void {
		$entry = sprintf(
			"[%s] %s\n\n",
			$method,
			$message
		);

		if ( $file ) {
			$directory = dirname( $file );

			if ( is_dir( $directory ) && is_writable( $directory ) ) {
				file_put_contents( $file, $entry, FILE_APPEND );
				return;
			}
		}

		error_log( $entry );
	}
}
