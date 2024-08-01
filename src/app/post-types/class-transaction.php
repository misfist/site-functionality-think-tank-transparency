<?php
/**
 * Content Post_Types
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Post_Types;

use Site_Functionality\Common\Abstracts\Post_Type;
use Site_Functionality\App\Post_Types\Donor;
use Site_Functionality\App\Post_Types\Think_Tank;

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
		// 'capabilities'  => array( 'create_posts' => false ),
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
				'key'   => 'specific_donor',
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
				'type'  => 'string',
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
				'type'  => 'string',
			),
			array(
				'label' => __( 'Disclosed', 'site-functionality' ),
				'key'   => 'disclosed',
				'type'  => 'boolean',
			),
			array(
				'label' => __( 'Donor Heirarchy', 'site-functionality' ),
				'key'   => 'donor_heirarchy',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Donor Parent Name', 'site-functionality' ),
				'key'   => 'donor_parent_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Donor Parent ID', 'site-functionality' ),
				'key'   => 'donor_parent_id',
				'type'  => 'integer',
			),
			// ar
			// array(
			// 	'label'        => __( 'Analyzed By', 'site-functionality' ),
			// 	'key'          => 'analyzed_by',
			// 	'type'         => 'string',
			// 	'show_in_rest' => false,
			// ),
			// array(
			// 	'label'        => __( 'Internal Notes', 'site-functionality' ),
			// 	'key'          => 'internal_notes',
			// 	'type'         => 'string',
			// 	'show_in_rest' => false,
			// ),
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
				'label'        => __( 'Donor ID', 'site-functionality' ),
				'key'          => 'donor_id',
				'type'         => 'integer',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Think Tank ID', 'site-functionality' ),
				'key'          => 'think_tank_id',
				'type'         => 'integer',
				'show_in_rest' => false,
			),
		);

		\add_action( 'init', array( $this, 'register_meta' ) );
		\add_action( 'acf/init', array( $this, 'register_fields' ) );
		\add_action( 'mb_relationships_init', array( $this, 'register_relationships' ) );
		// \add_action( 'pre_get_posts', array( $this, 'post_order' ) );

		\add_filter(
			'wpdatatables_filter_mysql_query',
			function( $query, $tableId ) {
				global $post;
				$title = get_post_field( 'post_title', $post );
				// AND transaction_taxonomy_think_tank_tbl.name = 'Atlantic Council'
				// $query = str_replace( ' LIMIT', " AND transaction_taxonomy_think_tank_tbl.name = 'Atlantic Council' LIMIT", $query );
				// var_dump( $query );
				return $query;
			},
			'',
			2
		);

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
	 * Register Post Relationships
	 *
	 * @see https://docs.metabox.io/extensions/mb-relationships/
	 *
	 * @return void
	 */
	public function register_relationships() : void {
		$transaction_think_tank = array(
			'id'         => self::POST_TYPE['rest_base'] . '_to_' . Think_Tank::POST_TYPE['rest_base'],
			'from'       => array(
				'object_type'  => 'post',
				'post_type'    => self::POST_TYPE['id'],
				'admin_filter' => true,
				'admin_column' => array(
					'position' => 'after title',
					'link'     => 'view',
					'title'    => Think_Tank::POST_TYPE['singular'],
				),
			),
			'to'         => array(
				'object_type'  => 'post',
				'post_type'    => Think_Tank::POST_TYPE['id'],
				'admin_filter' => true,
				'admin_column' => array(
					'position' => 'after title',
					'link'     => 'view',
					'title'    => self::POST_TYPE['title'],
				),
			),
			'reciprocal' => true,
		);
		\MB_Relationships_API::register( $transaction_think_tank );

		$transaction_donor = array(
			'id'         => self::POST_TYPE['rest_base'] . '_to_' . Donor::POST_TYPE['rest_base'],
			'from'       => array(
				'object_type'  => 'post',
				'post_type'    => self::POST_TYPE['id'],
				'admin_filter' => true,
				'admin_column' => array(
					'position' => 'after title',
					'link'     => 'view',
					'title'    => Donor::POST_TYPE['singular'],
				),
			),
			'to'         => array(
				'object_type'  => 'post',
				'post_type'    => Donor::POST_TYPE['id'],
				'admin_filter' => true,
				'admin_column' => array(
					'position' => 'after title',
					'link'     => 'view',
					'title'    => self::POST_TYPE['title'],
				),
			),
			'reciprocal' => true,
		);
		\MB_Relationships_API::register( $transaction_donor );

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
	 * @param  object \WP_Query $query
	 * @return void
	 */
	public function post_order( $query ) {}

}
