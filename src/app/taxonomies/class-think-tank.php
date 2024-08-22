<?php
/**
 * Taxonomy
 *
 * @since   1.0.0
 *
 * @package   Site_Functionality
 */
namespace Site_Functionality\App\Taxonomies;

use Site_Functionality\Common\Abstracts\Taxonomy;

/**
 * Class Taxonomies
 *
 * @package Site_Functionality\App\Taxonomies
 * @since 1.0.0
 */
class Think_Tank extends Taxonomy {

	/**
	 * Taxonomy data
	 */
	public const TAXONOMY = array(
		'id'                => 'think_tank',
		'singular_name'     => 'Think Tank',
		'name'              => 'Think Tanks',
		'menu_name'         => 'Think Tanks',
		'plural'            => 'Think Tanks',
		'slug'              => 'think-tank',
		'post_types'        => array(
			'think_tank',
			'transaction'
		),
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'show_in_rest'      => true,
		'has_archive'       => true,
		'meta_box_cb'       => 'post_categories_meta_box',
		'rest_base'         => 'think-tanks',
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		\add_action( 'pre_get_posts', array( $this, 'post_order' ) );
	}

	/**
	 * Add rewrite rules
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_rewrite_rule/
	 *
	 * @return void
	 */
	public function rewrite_rules(): void {}

	/**
	 * Set Post Order
	 *
	 * @see https://developer.wordpress.org/reference/hooks/pre_get_posts/
	 *
	 * @param  obj \WP_Query $query
	 * @return void
	 */
	public function post_order( $query ) {
		if ( ! is_admin() && $query->is_main_query() ) {
			if ( is_tax( self::TAXONOMY['id'] ) ) {
				// $query->set( 'orderby', 'title' );
				// $query->set( 'order', 'ASC' );
				$query->set( 'post_type', 'transaction' );
			}
		}
	}
	
}
