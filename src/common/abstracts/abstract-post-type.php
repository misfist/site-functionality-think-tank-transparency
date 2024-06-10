<?php
/**
 * Site Functionality
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
abstract class Post_Type extends Base {

	/**
	 * PostType data
	 */
	public const POST_TYPE = self::POST_TYPE;

	/**
	 * Post Type fields
	 */
	public $fields;

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
	 * Register post type
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() : void {
		$labels  = array(
			'name'                  => _x( static::POST_TYPE['title'], 'Post Type General Name', 'site-functionality' ),
			'singular_name'         => _x( static::POST_TYPE['singular'], 'Post Type Singular Name', 'site-functionality' ),
			'menu_name'             => isset( static::POST_TYPE['menu_name'] ) ? static::POST_TYPE['menu_name'] : static::POST_TYPE['title'],
			'name_admin_bar'        => __( static::POST_TYPE['singular'], 'site-functionality' ),

			'add_new'               => sprintf( /* translators: %s: post type singular title */ __( 'New %s', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'add_new_item'          => sprintf( /* translators: %s: post type singular title */ __( 'Add New %s', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'new_item'              => sprintf( /* translators: %s: post type singular title */ __( 'New %s', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'edit_item'             => sprintf( /* translators: %s: post type singular title */ __( 'Edit %s', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'view_item'             => sprintf( /* translators: %s: post type singular title */ __( 'View %s', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'view_items'            => sprintf( /* translators: %s: post type title */ __( 'View %s', 'site-functionality' ), static::POST_TYPE['title'] ),
			'all_items'             => sprintf( /* translators: %s: post type title */ __( 'All %s', 'site-functionality' ), static::POST_TYPE['title'] ),
			'search_items'          => sprintf( /* translators: %s: post type title */ __( 'Search %s', 'site-functionality' ), static::POST_TYPE['title'] ),

			'archives'              => sprintf( /* translators: %s: post type title */ __( '%s Archives', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'attributes'            => sprintf( /* translators: %s: post type title */ __( '%s Attributes', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'parent_item_colon'     => sprintf( /* translators: %s: post type title */ __( 'Parent %s:', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'update_item'           => sprintf( /* translators: %s: post type title */ __( 'Update %s', 'site-functionality' ), static::POST_TYPE['singular'] ),
			'items_list'            => sprintf( /* translators: %s: post type singular title */ __( '%s List', 'site-functionality' ), static::POST_TYPE['title'] ),
			'items_list_navigation' => sprintf( /* translators: %s: post type singular title */ __( '%s list navigation', 'site-functionality' ), static::POST_TYPE['title'] ),

			'insert_into_item'      => sprintf( /* translators: %s: post type title */ __( 'Insert into %s', 'site-functionality' ), strtolower( static::POST_TYPE['singular'] ) ),
			'uploaded_to_this_item' => sprintf( /* translators: %s: post type title */ __( 'Uploaded to this %s', 'site-functionality' ), strtolower( static::POST_TYPE['singular'] ) ),
			'filter_items_list'     => sprintf( /* translators: %s: post type title */ __( 'Filter %s list', 'site-functionality' ), strtolower( static::POST_TYPE['title'] ) ),
			'featured_image'        => __( 'Featured Image', 'site-functionality' ),
		);
		$rewrite = array(
			'slug'         => isset( static::POST_TYPE['slug'] ) ? static::POST_TYPE['slug'] : static::POST_TYPE['id'],
			'with_front'   => isset( static::POST_TYPE['with_front'] ) ? static::POST_TYPE['with_front'] : false,
			'hierarchical' => isset( static::POST_TYPE['hierarchical'] ) ? static::POST_TYPE['hierarchical'] : false,
		);
		$args    = array(
			'label'               => static::POST_TYPE['title'],
			'labels'              => $labels,
			'supports'            => isset( static::POST_TYPE['supports'] ) ? static::POST_TYPE['supports'] : array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'page-attributes' ),
			'taxonomies'          => isset( static::POST_TYPE['taxonomies'] ) ? static::POST_TYPE['taxonomies'] : array(),
			'hierarchical'        => isset( static::POST_TYPE['hierarchical'] ) ? static::POST_TYPE['hierarchical'] : false,
			'public'              => isset( static::POST_TYPE['public'] ) ? static::POST_TYPE['public'] : true,
			'show_ui'             => isset( static::POST_TYPE['show_ui'] ) ? static::POST_TYPE['show_ui'] : true,
			'show_in_menu'        => isset( static::POST_TYPE['show_in_menu'] ) ? static::POST_TYPE['show_in_menu'] : true,
			'menu_icon'           => isset( static::POST_TYPE['menu_icon'] ) ? static::POST_TYPE['menu_icon'] : 'dashicons-admin-post',
			'menu_position'       => isset( static::POST_TYPE['menu_position'] ) ? static::POST_TYPE['menu_position'] : 9,
			'show_in_admin_bar'   => isset( static::POST_TYPE['show_in_admin_bar'] ) ? static::POST_TYPE['show_in_admin_bar'] : true,
			'show_in_nav_menus'   => isset( static::POST_TYPE['show_in_nav_menus'] ) ? static::POST_TYPE['show_in_nav_menus'] : true,
			'can_export'          => isset( static::POST_TYPE['can_export'] ) ? static::POST_TYPE['can_export'] : true,
			'has_archive'         => isset( static::POST_TYPE['has_archive'] ) ? static::POST_TYPE['has_archive'] : true,
			'rewrite'             => $rewrite,
			'exclude_from_search' => isset( static::POST_TYPE['exclude_from_search'] ) ? static::POST_TYPE['exclude_from_search'] : false,
			'publicly_queryable'  => isset( static::POST_TYPE['publicly_queryable'] ) ? static::POST_TYPE['publicly_queryable'] : true,
			'capability_type'     => isset( static::POST_TYPE['capability'] ) ? static::POST_TYPE['capability'] : 'post',
			'show_in_rest'        => isset( static::POST_TYPE['show_in_rest'] ) ? static::POST_TYPE['show_in_rest'] : true,
			'rest_base'           => isset( static::POST_TYPE['rest_base'] ) ? static::POST_TYPE['rest_base'] : static::POST_TYPE['archive'],
			'template_lock'       => isset( static::POST_TYPE['template_lock'] ) ? static::POST_TYPE['template_lock'] : false,
		);
		if ( isset( static::POST_TYPE['template'] ) && ! empty( static::POST_TYPE['template'] ) ) {
			$args['template'] = static::POST_TYPE['template'];
		}

		\register_post_type(
			static::POST_TYPE['id'],
			\apply_filters( \get_class( $this ) . '\Args', $args )
		);

	}
}
