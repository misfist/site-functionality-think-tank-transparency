<?php
/**
 * WP Import Actions
 *
 * @package site-functionality
 */
namespace Site_Functionality\Integrations\WP_Import;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions extends Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $settings ) {
		parent::__construct( $settings );
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		\add_action( 'pmxi_saved_post', array( $this, 'set_donor_parent' ), 10, 1 );
		\add_action( 'pmxi_saved_post', array( $this, 'set_transaction_donor' ), 10, 1 );
		// add_action( 'pmxi_saved_post', array( $this, 'set_transaction_think_tank' ), 10, 1 );
	}

	/**
	 * Set donor parents
	 *
	 * @param  int $post_id
	 * @return void
	 */
	public function set_donor_parent( $post_id ) : void {
		$post_type = 'donor';
		if ( $post_type !== get_post_type( $post_id ) ) {
			return;
		}

		/**
		 * Get parent donor name
		 */
		$meta_key  = 'parent_donor_name';
		$parent_donor_name = \get_post_meta( $post_id, $meta_key, true );

		if ( $parent_donor_name ) {
			$donor_parent_posts = $this->get_donor_by_title( $parent_donor_name );

			if( ! empty( $donor_parent_posts ) && ! \is_wp_error( $donor_parent_posts ) ) {
				$donor_parent = $donor_parent_posts[0];
				$donor_parent_id   = $parent_donor->ID;
				$donor_parent_name = $parent_donor->post_title;

				\wp_update_post(
					array(
						'ID'          => $post_id,
						'post_parent' => $donor_parent_id,
						'meta_input'  => array(
							'assigned_parent_using_name' => $donor_parent_id,
							'donor_parent_name'          => $donor_parent_name,
							'donor_parent_id'            => $donor_parent_id,
						),
					)
				);
			}

		}

		return;
	}

	/**
	 * Set donor
	 *
	 * @param  int $post_id
	 * @return void
	 */
	public function set_transaction_donor( $post_id ) : void {
		$post_type = 'transaction';
		if ( $post_type !== \get_post_type( $post_id ) ) {
			return;
		}

		$donor = $this->set_transaction_donor( $post_id );

		if ( $donor ) {
			$taxonomy   = 'donor_type';
			$donor_id   = $donor->ID;
			$donor_name = $donor->post_title;

			$donor_parent = $this->get_transaction_donor_parent( $post_id );

			if ( $donor_parent ) {
				$donor_parent_id   = $donor_parent->ID;
				$donor_parent_name = $donor_parent->post_title;

				$donor_types = \wp_get_post_terms( $donor_parent_id, $taxonomy );
			}

			$tax_input = ( $donor_types ) ? \wp_list_pluck( $donor_types, 'term_id' ) : array();

			\wp_update_post(
				array(
					'ID'         => $post_id,
					'meta_input' => array(
						'donor_id'          => ( isset( $donor_id ) ) ? (int) $donor_id : '',
						'donor_name'        => ( $donor_name ) ? esc_html( $donor_name ) : '',
						'donor_parent_id'   => ( isset( $donor_parent_id ) ) ? (int) $donor_parent_id : '',
						'donor_parent_name' => ( $donor_parent_name ) ? esc_html( $donor_parent_name ) : '',
						'donor_type'        => ( $donor_types ) ? $donor_types[0]->name : '',
					),
					'tax_input'  => array(
						$taxonomy => $tax_input,
					),
				)
			);
		}

	}

	/**
	 * Get post id from meta
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed int post_id || false
	 */
	public function get_post_id_by_meta_key_and_value( $key, $value ) {
		global $wpdb;
		$meta = $wpdb->get_results( 'SELECT * FROM `' . $wpdb->postmeta . "` WHERE meta_key='" . $wpdb->escape( $key ) . "' AND meta_value='" . $wpdb->escape( $value ) . "'" );
		if ( is_array( $meta ) && ! empty( $meta ) && isset( $meta[0] ) ) {
			$meta = $meta[0];
		}
		if ( is_object( $meta ) ) {
			return $meta->post_id;
		} else {
			return false;
		}
	}

	/**
	 * Get post record
	 *
	 * @param  string $post_title
	 * @param  string $post_type
	 * @return array
	 */
	function get_post_id_by_title( $post_title, $post_type ) : array {
		global $wpdb;
		return $wpdb->get_results( "SELECT ID FROM wp_posts where post_type='" . $post_type . "' AND post_title='" . $post_title . "'" );
	}

	/**
	 * Get Donor Parent
	 *
	 * @param  string $post_title
	 * @return mixed array || false
	 */
	public function get_donor_parent( $post_id, $meta_key = 'donor_parent_name' ) {
		$child_post_title  = \get_post_field( 'post_title', $post_id );
		$parent_post_title = \get_post_meta( $post_id, $meta_key, true );

		if ( $parent_post_title && $parent_post_title !== $child_post_title ) {
			$donor_parent = $this->get_donor_by_title( $parent_post_title );
			return $donor_parent[0];
		}
		return false;
	}

	/**
	 * Get Transaction Donor Parent
	 *
	 * @param  int    $post_id
	 * @param  string $meta_key
	 * @return mixed array || false
	 */
	function get_transaction_donor_parent( $post_id, $meta_key = 'specific_donor' ) {
		$donors = $this->get_transaction_donor( $post_id, $meta_key );

		if ( ! empty( $donors ) && ! \is_wp_error( $donors ) ) {
			$parent_meta_key   = 'donor_parent_name';
			$parent_post_title = \get_post_meta( $donors[0]->ID, $parent_meta_key, true );

			if ( $parent_post_title ) {
				$donor_parent = $this->get_donor_by_title( $parent_post_title );
				return $donor_parent[0];
			}
		}

		return false;
	}

	/**
	 * Get Transaction Donor
	 *
	 * @param  int    $post_id
	 * @param  string $meta_key
	 * @return array
	 */
	function get_transaction_donor( $post_id, $meta_key = 'specific_donor' ) {
		$child_post_title = \get_post_meta( $post_id, $meta_key, true );

		$post_type = 'donor';
		$args      = array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'title'          => $child_post_title,
		);
		$query     = new \WP_Query( $args );

		return $query->get_posts();
	}

	/**
	 * Get Donor by post_title
	 *
	 * @param  string $post_title
	 * @return array
	 */
	function get_donor_by_title( $post_title ) {
		$post_type = 'donor';
		$args      = array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'title'          => $post_title,
		);

		$query = new \WP_Query( $args );

		return $query->get_posts();
	}

}

