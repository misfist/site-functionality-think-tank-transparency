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

class Transaction extends Post_Type {

	/**
	 * Post_Type data
	 */
	public const POST_TYPE = array(
		'id'            => 'transaction',
		'slug'          => 'transaction',
		'menu'          => 'Transactions',
		'title'         => 'Transactions',
		'singular'      => 'Transaction',
		'menu_icon'     => 'dashicons-database',
		'taxonomies'    => array(
			'donor_type',
		),
		'has_archive'   => false,
		'with_front'    => false,
		'rest_base'     => 'transactions',
		'hierarchical'  => false,
		'supports'      => array(
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
				'label' => __( 'Donor', 'site-functionality' ),
				'key'   => 'donor',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Think Tank', 'site-functionality' ),
				'key'   => 'think_tank',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Donor Type', 'site-functionality' ),
				'key'   => 'donor_type',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Year', 'site-functionality' ),
				'key'   => 'year',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Actual', 'site-functionality' ),
				'key'   => 'amount',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Min', 'site-functionality' ),
				'key'   => 'amount_min',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Max', 'site-functionality' ),
				'key'   => 'amount_max',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Actual + Min', 'site-functionality' ),
				'key'   => 'amount_calc',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Source', 'site-functionality' ),
				'key'   => 'source',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Source Notes', 'site-functionality' ),
				'key'   => 'source_notes',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Disclosed', 'site-functionality' ),
				'key'   => 'disclosed',
				'type'  => 'boolean',
			),
			array(
				'label'        => __( 'Analyzed By', 'site-functionality' ),
				'key'          => 'analyzed_by',
				'type'         => 'string',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Internal Notes', 'site-functionality' ),
				'key'          => 'internal_notes',
				'type'         => 'string',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Import Donor ID', 'site-functionality' ),
				'key'          => 'import_donor_id',
				'type'         => 'integer',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Import Think ID', 'site-functionality' ),
				'key'          => 'import_think_tank_id',
				'type'         => 'integer',
				'show_in_rest' => false,
			),
			array(
				'label' => __( 'Donor ID', 'site-functionality' ),
				'key'   => 'donor_id',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Think Tank ID', 'site-functionality' ),
				'key'   => 'think_tank_id',
				'type'  => 'integer',
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
	public function register_fields(): void {
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
