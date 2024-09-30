<?php
/**
 * Admin Settings
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Admin;

use Site_Functionality\Common\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Editor extends Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $settings = array() ) {
		parent::__construct( $settings );
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		$this->data['meta_keys'] = array();
		$this->data['post_types'] = array(
			'donor',
			'think_tank',
			'transaction'
		);

		add_action( 'add_meta_boxes', array( $this, 'add_all_post_meta_meta_box' ) );
	}

	 /**
	  * Registers a meta box to display all post meta.
	  *
	  * Adds a meta box to the post edit screen that displays meta key-value pairs.
	  *
	  * @see https://developer.wordpress.org/reference/functions/add_meta_box/
	  */
	public function add_all_post_meta_meta_box() {
		foreach( $this->data['post_types'] as $post_type ) {
			add_meta_box(
				'site_functionality',
				__( 'Data', 'site-functionality' ),
				array( $this, 'display_post_meta_in_editor' ),
				$post_type,
				'normal',
				'high',
				$this->data
			);
		}
	}

	/**
	 * Callback function to display all post meta.
	 *
	 * Outputs a table of meta key-value pairs for the given post.
	 *
	 * @param WP_Post $post The post object.
	 *                      @see https://developer.wordpress.org/reference/classes/wp_post/
	 * @param array   $args Array of arguments passed to the meta box.
	 *                      - 'meta_keys': Array of meta keys to display.
	 *                      @see https://developer.wordpress.org/reference/functions/add_meta_box/
	 *
	 * @return void
	 */
	public function display_post_meta_in_editor( $post, $args ) {
		$post_meta            = get_post_meta( $post->ID );
		$meta_keys_to_display = isset( $args['args']['meta_keys'] ) ? $args['args']['meta_keys'] : array();

		echo '<table class="widefat fixed" style="margin-top: 10px;">';
		echo '<thead><tr><th>' . esc_html__( 'Meta Key', 'textdomain' ) . '</th><th>' . esc_html__( 'Meta Value', 'textdomain' ) . '</th></tr></thead>';
		echo '<tbody>';

		if ( ! empty( $post_meta ) ) {
			foreach ( $post_meta as $meta_key => $meta_values ) {
				if ( in_array( $meta_key, $meta_keys_to_display, true ) ) {
					foreach ( $meta_values as $meta_value ) {
						echo '<tr>';
						echo '<td>' . esc_html( $meta_key ) . '</td>';
						echo '<td>' . esc_html( $meta_value ) . '</td>';
						echo '</tr>';
					}
				}
			}
		} else {
			echo '<tr><td colspan="2">' . esc_html__( 'No post meta found.', 'textdomain' ) . '</td></tr>';
		}

		echo '</tbody></table>';
	}

}
