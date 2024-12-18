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
		'taxonomies'    => array( 'think_tank' ),
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

		$args                      = array(
			'taxonomy'   => 'donor_type',
			'fields'     => 'slugs',
			'hide_empty' => false,
		);
		$donor_types               = get_terms( $args );
		$this->data['donor_types'] = ( ! is_wp_error( $donor_types ) && ! empty( $donor_types ) ) ? $donor_types : array();
		$donor_array               = array_fill_keys( $this->data['donor_types'], 'string' );

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
				'label'        => __( 'Undisclosed', 'site-functionality' ),
				'key'          => 'undisclosed',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Did Not Accept Pentagon Contractor Donations', 'site-functionality' ),
				'key'          => 'no_defense_accepted',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Did Not Accept Foreign Interest Donations', 'site-functionality' ),
				'key'          => 'no_foreign_accepted',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Did Not Accept US Government Donations', 'site-functionality' ),
				'key'          => 'no_domestic_accepted',
				'single'       => true,
				'type'         => 'string',
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
				'label'        => __( 'Domestic Undisclosed', 'site-functionality' ),
				'key'          => 'undisclosed_u-s-government',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Foreign Interest Undisclosed', 'site-functionality' ),
				'key'          => 'undisclosed_foreign-government',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Pentagon Contractor Undisclosed', 'site-functionality' ),
				'key'          => 'undisclosed_pentagon-contractor',
				'single'       => true,
				'type'         => 'string',
				'show_in_rest' => true,
			),
			array(
				'label'        => __( 'Cumulative Data', 'site-functionality' ),
				'key'          => 'cumulative_amounts',
				'single'       => true,
				'type'         => 'array',
				'show_in_rest' => array(
					'schema' => array(
						'type'  => 'array',
						'items' => $donor_array,
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
			// array(
			// 'label'        => __( 'Press Contact Email', 'site-functionality' ),
			// 'key'          => 'contact_email',
			// 'single'       => true,
			// 'type'         => 'string',
			// 'show_in_rest' => false,
			// ),
			// array(
			// 'label'        => __( 'Press Contact Phone', 'site-functionality' ),
			// 'key'          => 'contact_phone',
			// 'single'       => true,
			// 'type'         => 'string',
			// 'show_in_rest' => false,
			// ),
			// array(
			// 'label'        => __( 'Press Contact Other', 'site-functionality' ),
			// 'key'          => 'contact_other',
			// 'single'       => true,
			// 'type'         => 'string',
			// 'show_in_rest' => false,
			// ),
		);

		\add_action( 'init', array( $this, 'register_meta' ) );
		\add_action( 'acf/init', array( $this, 'register_fields' ) );
		\add_action( 'pre_get_posts', array( $this, 'post_order' ) );
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
		$amount               = get_post_meta( $post_id, 'amount_calc', true );
		$amount_domestic      = get_post_meta( $post_id, 'amount_domestic', true );
		$amount_foreign       = get_post_meta( $post_id, 'amount_foreign', true );
		$amount_defense       = get_post_meta( $post_id, 'amount_defense', true );
		$no_defense_accepted  = get_post_meta( $post_id, 'no_defense_accepted', true );
		$no_domestic_accepted = get_post_meta( $post_id, 'no_domestic_accepted', true );
		$no_foreign_accepted  = get_post_meta( $post_id, 'no_foreign_accepted', true );
		$limited_info         = get_post_meta( $post_id, 'limited_info', true );
		$transparency_score   = get_post_meta( $post_id, 'transparency_score', true );

		?>
		<table class="wp-block-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Min. Amount', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Domestic', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Foreign', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Defense', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'No Defense', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'No Domestic', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'No Foreign', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Limited Info', 'site-functionality' ); ?></th>
					<th><?php esc_html_e( 'Transparency Score', 'site-functionality' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( $amount, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $amount_domestic, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $amount_foreign, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $amount_defense, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $no_defense_accepted, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $no_domestic_accepted, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $no_foreign_accepted, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $limited_info, 'site-functionality' ); ?></td>
					<td><?php esc_html_e( $transparency_score, 'site-functionality' ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

}
