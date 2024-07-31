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

class Think_Tank extends Post_Type {

	/**
	 * Post_Type data
	 */
	public const POST_TYPE = array(
		'id'            => 'think_tank',
		'slug'          => 'think-tank',
		'menu'          => 'Think Tanks',
		'title'         => 'Think Tanks',
		'singular'      => 'Think Tank',
		'menu_icon'     => 'dashicons-bank',
		'taxonomies'    => array(),
		'has_archive'   => 'think-tanks',
		'with_front'    => false,
		'rest_base'     => 'think-tanks',
		'supports'      => array(
			'title',
			'custom-fields',
			// 'editor',
		),
		'capabilities'  => array( 'create_posts' => false ),
		'menu_position' => 20,
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
				'label'        => __( 'Transparency Score', 'site-functionality' ),
				'key'          => 'transparency_score',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Transparency Notes', 'site-functionality' ),
				'key'          => 'transparency_notes',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Limit Information', 'site-functionality' ),
				'key'          => 'limited_info',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Did Not Accept Defense Contractor Donations', 'site-functionality' ),
				'key'          => 'no_defense_accepted',
				'single'       => true,
				'type'         => 'boolean',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Did Not Accept Foreign Interest Donations', 'site-functionality' ),
				'key'          => 'no_foreign_accepted',
				'single'       => true,
				'type'         => 'boolean',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Did Not Accept US Government Donations', 'site-functionality' ),
				'key'          => 'no_domestic_accepted',
				'single'       => true,
				'type'         => 'boolean',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Transactions', 'site-functionality' ),
				'key'          => 'transactions',
				'single'       => true,
				'type'         => 'object',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Amount', 'site-functionality' ),
				'key'          => 'amount_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Min Amount', 'site-functionality' ),
				'key'          => 'amount_min_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Max Amount', 'site-functionality' ),
				'key'          => 'amount_max_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Actual + Min Amount', 'site-functionality' ),
				'key'          => 'amount_calc_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Domestic Funding', 'site-functionality' ),
				'key'          => 'amount_domestic_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Foreign Interest Funding', 'site-functionality' ),
				'key'          => 'amount_foreign_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Defense Contractor Funding', 'site-functionality' ),
				'key'          => 'amount_defense_cumulative',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Data', 'site-functionality' ),
				'key'          => 'cumulative_data',
				'single'       => true,
				'type'         => 'array',
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'amount' => 'integer',
							'amount_min' => 'integer',
							'amount_max' => 'integer',
							'amount_calc' => 'integer',
							'amount_domestic' => 'integer',
							'amount_foreign' => 'integer',
							'amount_defense' => 'integer',
						),
					),
				),
			),
			array(
				'label'        => __( 'Internal Notes', 'site-functionality' ),
				'key'          => 'internal_notes',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Import ID', 'site-functionality' ),
				'key'          => 'import_id',
				'single'       => true,
				'type'         => 'integer',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Press Contact Email', 'site-functionality' ),
				'key'          => 'contact_email',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Press Contact Phone', 'site-functionality' ),
				'key'          => 'contact_phone',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Press Contact Other', 'site-functionality' ),
				'key'          => 'contact_other',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => false,
			),
		);

		\add_action( 'init', array( $this, 'register_meta' ) );
		\add_action( 'acf/init', array( $this, 'register_fields' ) );
		\add_action( 'pre_get_posts', array( $this, 'post_order' ) );
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
					'single'       => $field['single'],
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

	/**
	 * Set Post Order
	 *
	 * @see https://developer.wordpress.org/reference/hooks/pre_get_posts/
	 *
	 * @param  [type] $query
	 * @return void
	 */
	public function post_order( $query ) {
		if ( ! is_admin() && $query->is_main_query() ) {
			if ( is_post_type_archive( self::POST_TYPE['id'] ) ) {
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
			}
		}
	}

}
