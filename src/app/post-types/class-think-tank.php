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
			'editor',
		),
		// 'capabilities'  => array( 'create_posts' => false ),
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
				'label' => __( 'Transparency Score', 'site-functionality' ),
				'key'   => 'transparency_score',
				'type'  => 'integer',
			),
			array(
				'label' => __( 'Transparency Notes', 'site-functionality' ),
				'key'   => 'transparency_notes',
				'type'  => 'string',
			),
			array(
				'label'        => __( 'Internal Notes', 'site-functionality' ),
				'key'          => 'internal_notes',
				'type'         => 'string',
				'show_in_rest' => false,
			),
			array(
				'label'        => __( 'Import ID', 'site-functionality' ),
				'key'          => 'import_id',
				'type'         => 'integer',
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
			if( is_post_type_archive( self::POST_TYPE['id'] ) ) {
				$query->set( 'orderby', 'title' );
				$query->set( 'order', 'ASC' );
			}
		}
	}

}
