<?php
/**
 * Content Post_Types
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Post_Types;

use Site_Functionality\Common\Abstracts\Post_Type;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Donor extends Post_Type {

	/**
	 * Post_Type data
	 */
	public const POST_TYPE = array(
		'id'            => 'donor',
		'slug'          => 'donor',
		'menu'          => 'Donors',
		'title'         => 'Donors',
		'singular'      => 'Donor',
		'menu_icon'     => 'dashicons-building',
		'taxonomies'    => array(
			'donor_type',
		),
		'has_archive'   => 'donors',
		'with_front'    => false,
		'rest_base'     => 'donors',
		'hierarchical'  => true,
		'supports'      => array(
			'title',
			'page-attributes',
			'custom-fields',
		),
		'menu_position' => 20,
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		\add_action( 'init', array( $this, 'register_meta' ) );
		\add_action( 'acf/init', array( $this, 'register_fields' ) );
	}

	/**
	 * Register Custom Fields
	 *
	 * @return void
	 */
	public function register_fields(): void {}

	/**
	 * Register Meta
	 *
	 * @return void
	 */
	public function register_meta(): void {}

	/**
	 * Register custom query vars
	 *
	 * @link https://developer.wordpublication.org/reference/hooks/query_vars/
	 *
	 * @param array $vars The array of available query variables
	 */
	public function register_query_vars( $vars ): array {
		return $vars;
	}

}
