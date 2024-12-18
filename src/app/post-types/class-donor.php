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
			'donor',
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
			// 'editor',
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
				'label' => __( 'Parent Donor', 'site-functionality' ),
				'key'   => 'donor_parent_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Parent Donor ID', 'site-functionality' ),
				'key'   => 'donor_parent_id',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Donor Type', 'site-functionality' ),
				'key'   => 'donor_type',
				'type'  => 'string',
			),
			array(
				'label'        => __( 'Transactions', 'site-functionality' ),
				'key'          => 'transactions',
				'single'       => true,
				'type'         => 'object',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Undisclosed', 'site-functionality' ),
				'key'          => 'undisclosed',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Amount', 'site-functionality' ),
				'key'          => 'amount',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Min Amount', 'site-functionality' ),
				'key'          => 'amount_min',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Max Amount', 'site-functionality' ),
				'key'          => 'amount_max',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Actual + Min Amount', 'site-functionality' ),
				'key'          => 'amount_calc',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Domestic Funding', 'site-functionality' ),
				'key'          => 'amount_u-s-government',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Foreign Interest Funding', 'site-functionality' ),
				'key'          => 'amount_foreign-government',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Pentagon Contractor Funding', 'site-functionality' ),
				'key'          => 'amount_pentagon-contractor',
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
							'amount'          => 'integer',
							'amount_min'      => 'integer',
							'amount_max'      => 'integer',
							'amount_calc'     => 'integer',
							'amount_domestic' => 'integer',
							'amount_foreign'  => 'integer',
							'amount_defense'  => 'integer',
						),
					),
				),
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
		\add_action( 'pre_get_posts', array( $this, 'post_order' ) );
		\add_filter( 'post_type_link', array( $this, 'redirect_to_parent' ), 10, 2 );
		\add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

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

	/**
	 * Add rewrite ruules
	 *
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_rule/
	 *
	 * @return void
	 */
	public function rewrite_rules() : void {}

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

	/**
	 * Modify permalink to parent
	 *
	 * @link https://developer.wordpress.org/reference/hooks/post_type_link/
	 *
	 * @param  string          $permalink
	 * @param  object \WP_Post $post
	 * @return string
	 */
	function redirect_to_parent( $permalink, $post ) : string {
		if ( 'donor' === $post->post_type && $post->post_parent ) {
			$permalink = get_permalink( $post->post_parent );
		}
		return $permalink;
	}

	/**
	 * Add Meta Box
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		$screens = array( self::POST_TYPE['id'] );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'post_meta_table',
				esc_html__( 'Field Data', 'site-functionality' ),
				array( $this, 'render_meta_box' ),
				$screen
			);
		}
	}

	/**
	 * Render table on post edit screen
	 *
	 * @param  \WP_Post $post
	 * @return void
	 */
	public function render_meta_box( $post ): void {
		$post_id              = $post->ID;
		$parent_obj           = get_post_parent( $post_id );
		$amount               = get_post_meta( $post_id, 'amount_calc', true );
		$donor_type           = get_the_term_list( $post_id, 'donor_type' );
		$parent               = ( ! empty( $parent_obj ) && ! is_wp_error( $parent_obj ) ) ? $parent_obj->post_title : '';
	
		?>
		<table class="wp-block-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Min. Amount', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Donor Type', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Parent Donor', 'site-functionality' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo sprintf( '$%s', number_format( $amount ) ); ?></td>
					<td><?php echo $donor_type; ?></td>
					<td><?php echo $parent_obj->post_title; ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

}
