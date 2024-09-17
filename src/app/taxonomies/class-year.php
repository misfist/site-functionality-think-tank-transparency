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
class Year extends Taxonomy {

	/**
	 * Taxonomy data
	 */
	public const TAXONOMY = array(
		'id'                => 'donation_year',
		'singular_name'     => 'Year',
		'name'              => 'Years',
		'menu_name'         => 'Years',
		'plural'            => 'Years',
		'slug'              => 'donation-year',
		'post_types'        => array(
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
		'rest_base'         => 'donation-years',
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		// \add_action( 'pre_get_posts', array( $this, 'post_order' ) );
		\add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
	}

	/**
	 * Register query vars
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/query_vars/
	 *
	 * @param  array $query_vars
	 * @return array
	 */
	public function register_query_vars( array $query_vars ) : array {
		$query_vars[] = 'donation-year';
		return $query_vars;
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
	public function post_order( $query ) {}
	
}
