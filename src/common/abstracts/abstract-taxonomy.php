<?php
/**
 * Taxonomy
 *
 * @package   Site_Functionality
 */
namespace Site_Functionality\Common\Abstracts;

use Site_Functionality\Common\Abstracts\Base;

/**
 * Class Taxonomies
 *
 * @package Site_Functionality\Common\Abstracts
 * @since 1.0.0
 */
abstract class Taxonomy extends Base {

	/**
	 * Taxonomy data
	 */
	public const TAXONOMY = self::TAXONOMY;

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		/**
		 * This general class is always being instantiated as requested in the Bootstrap class
		 *
		 * @see Bootstrap::__construct
		 */

		\add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register taxonomy
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() : void {

		$labels = array(
			'name'                       => _x( static::TAXONOMY['name'], 'Taxonomy General Name', 'site-functionality' ),
			'singular_name'              => _x( static::TAXONOMY['singular_name'], 'Taxonomy Singular Name', 'site-functionality' ),
			'menu_name'                  => __( static::TAXONOMY['menu_name'], 'site-functionality' ),
			'all_items'                  => sprintf( /* translators: %s: post type title */ __( 'All %s', 'site-functionality' ), static::TAXONOMY['name'] ),
			'parent_item'                => sprintf( /* translators: %s: post type title */ __( 'Parent %s', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'parent_item_colon'          => sprintf( /* translators: %s: post type title */ __( 'Parent %s:', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'new_item_name'              => sprintf( /* translators: %s: post type singular title */ __( 'New %s Name', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'add_new_item'               => sprintf( /* translators: %s: post type singular title */ __( 'Add New %s', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'edit_item'                  => sprintf( /* translators: %s: post type singular title */ __( 'Edit %s', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'update_item'                => sprintf( /* translators: %s: post type title */ __( 'Update %s', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'view_item'                  => sprintf( /* translators: %s: post type singular title */ __( 'View %s', 'site-functionality' ), static::TAXONOMY['singular_name'] ),
			'search_items'               => sprintf( /* translators: %s: post type title */ __( 'Search %s', 'site-functionality' ), static::TAXONOMY['name'] ),

			'separate_items_with_commas' => sprintf( /* translators: %s: post type title */ __( 'Separate %s with commas', 'site-functionality' ), strtolower( static::TAXONOMY['name'] ) ),
			'add_or_remove_items'        => sprintf( /* translators: %s: post type title */ __( 'Add or remove %s', 'site-functionality' ), strtolower( static::TAXONOMY['name'] ) ),
			'popular_items'              => sprintf( /* translators: %s: post type title */ __( 'Popular %s', 'site-functionality' ), static::TAXONOMY['name'] ),
			'search_items'               => sprintf( /* translators: %s: post type title */ __( 'Search %s', 'site-functionality' ), static::TAXONOMY['name'] ),
			'no_terms'                   => sprintf( /* translators: %s: post type title */ __( 'No %s', 'site-functionality' ), strtolower( static::TAXONOMY['name'] ) ),
			'items_list'                 => sprintf( /* translators: %s: post type title */ __( '%s list', 'site-functionality' ), static::TAXONOMY['name'] ),
			'items_list_navigation'      => sprintf( /* translators: %s: post type title */ __( '%s list navigation', 'site-functionality' ), static::TAXONOMY['name'] ),
		);

		$rewrite = array(
			'slug'         => isset( static::TAXONOMY['slug'] ) ? static::TAXONOMY['slug'] : static::TAXONOMY['id'],
			'with_front'   => isset( static::TAXONOMY['with_front'] ) ? static::TAXONOMY['with_front'] : false,
			'hierarchical' => isset( static::TAXONOMY['hierarchical'] ) ? static::TAXONOMY['hierarchical'] : false,
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => isset( static::TAXONOMY['hierarchical'] ) ? static::TAXONOMY['hierarchical'] : false,
			'public'            => isset( static::TAXONOMY['public'] ) ? static::TAXONOMY['public'] : true,
			'show_ui'           => isset( static::TAXONOMY['show_ui'] ) ? static::TAXONOMY['show_ui'] : true,
			'show_admin_column' => isset( static::TAXONOMY['show_admin_column'] ) ? static::TAXONOMY['show_admin_column'] : true,
			'show_in_menu'      => isset( static::TAXONOMY['show_in_menu'] ) ? static::TAXONOMY['show_in_menu'] : true,
			'show_in_nav_menus' => isset( static::TAXONOMY['show_in_nav_menus'] ) ? static::TAXONOMY['show_in_nav_menus'] : true,
			'show_tagcloud'     => isset( static::TAXONOMY['show_tagcloud'] ) ? static::TAXONOMY['show_tagcloud'] : true,
			'rewrite'           => $rewrite,
			'show_in_rest'      => isset( static::TAXONOMY['show_in_rest'] ) ? static::TAXONOMY['show_in_rest'] : true,
			'rest_base'         => isset( static::TAXONOMY['rest_base'] ) ? static::TAXONOMY['rest_base'] : static::TAXONOMY['id'],
		);
		if ( isset( static::TAXONOMY['query_var'] ) ) {
			$args['query_var'] = static::TAXONOMY['query_var'];
		}
		\register_taxonomy(
			static::TAXONOMY['id'],
			static::TAXONOMY['post_types'],
			\apply_filters( \get_class( $this ) . '\Args', $args )
		);
	}

}
