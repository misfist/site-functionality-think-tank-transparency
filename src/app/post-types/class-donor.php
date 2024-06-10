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
		'menu_position' => 25,
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		$this->data['fields'] = array(
			array(
				'label' => __( 'Parent Donor', 'site-functionality' ),
				'key'   => 'parent_donor',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Donor Type', 'site-functionality' ),
				'key'   => 'donor_type',
				'type'  => 'string',
			),
			array(
				'label'        => __( 'Import ID', 'site-functionality' ),
				'key'          => 'import_id',
				'type'         => 'integer',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Import Parent ID', 'site-functionality' ),
				'key'          => 'import_parent_id',
				'type'         => 'integer',
				'show_in_rest' => false,
			),
		);

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
	public function register_meta(): void {
		foreach ( $this->data['fields'] as $key => $field ) {
			register_post_meta(
				self::POST_TYPE['id'],
				$field['key'],
				array(
					'type'         => $field['type'],
					'description'  => $field['label'],
					'single'       => true,
					'show_in_rest' => ( isset( $field['show_in_rest'] ) ) ? $field['show_in_rest'] : true,
				)
			);
		}
	}

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
