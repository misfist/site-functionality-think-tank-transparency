<?php
/**
 * WP Import Actions
 *
 * @package site-functionality
 */
namespace Site_Functionality\Integrations\WP_Import;

use Site_Functionality\Common\Abstracts\Base;

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
		$this->data['parent_name_meta']       = 'donor_parent_name';
		$this->data['transactions_import_id'] = 8;
		$this->data['donors_import_id']       = 6;
		$this->data['taxonomies']             = array( 'donor_type', 'donor', 'think_tank', 'year' );

		// \add_action( 'pmxi_saved_post', array( $this, 'set_donor_parent' ), 10, 1 );
		// \add_action( 'pmxi_saved_post', array( $this, 'set_transaction_donor_parent' ), 10, 1 );

		// \add_action( 'pmxi_saved_post', array( $this, 'set_transaction_donor' ), 10, 1 );
		// \add_action( 'pmxi_after_xml_import', array( $this, 'set_transaction_data_after_import' ), 10, 2 );

		// add_action( 'pmxi_saved_post', array( $this, 'set_transaction_think_tank' ), 10, 1 );
	}

	/**
	 * Set donor parents
	 *
	 * @param  int $post_id
	 * @return void
	 */
	// function set_donor_parent( $post_id ) {
	// $post_type = 'donor';
	// if ( $post_type !== get_post_type( $post_id ) ) {
	// return;
	// }

	// **
	// * Get parent donor name
	// */
	// $meta_key           = $this->data['parent_name_meta'];
	// $parent_name_meta   = get_post_meta( $post_id, $meta_key, true );
	// $current_donor_name = get_post_field( 'post_title', $post_id );

	// **
	// * If post_title is same as parent name, it is top-level donor
	// */
	// if ( trim( strtolower( $parent_name_meta ) ) !== trim( strtolower( $current_donor_name ) ) ) {

	// **
	// * Get donor by name
	// */
	// $donors = get_posts(
	// array(
	// 'post_type'      => $post_type,
	// 'posts_per_page' => 1,
	// 'title'          => trim( $parent_name_meta ),
	// )
	// );

	// if ( ! empty( $donors ) ) {
	// $donor_parent      = $donors[0];
	// $donor_parent_id   = $donor_parent->ID;
	// $donor_parent_name = $donor_parent->post_title;

	// \wp_update_post(
	// array(
	// 'ID'          => $post_id,
	// 'post_parent' => $donor_parent_id,
	// 'meta_input'  => array(
	// 'assigned_parent_using_name' => $donor_parent_id,
	// 'assigned_donor_parent_name' => $donor_parent_name,
	// 'donor_parent_id'            => $donor_parent_id,
	// 'run_from'                   => __FUNCTION__,
	// 'donors'                     => $donor_parent,
	// ),
	// )
	// );

	// return;
	// }
	// } else {

	// \wp_update_post(
	// array(
	// 'ID'          => $post_id,
	// 'post_parent' => 0,
	// 'meta_input'  => array(
	// 'assigned_parent_using_name' => 0,
	// 'assigned_donor_parent_name' => $parent_name_meta,
	// 'donor_parent_id'            => 0,
	// 'run_from'                   => __FUNCTION__,
	// 'donors'                     => array(),
	// ),
	// )
	// );

	// }

	// }

	/**
	 * Updates the donor post meta after a donor post is imported.
	 *
	 * This function is hooked to the `pmxi_saved_post` action, which is triggered
	 * after a post is saved by WP All Import.
	 *
	 * @link https://www.wpallimport.com/documentation/developers/action-hooks/pmxi_saved_post/ Documentation for `pmxi_saved_post` action hook.
	 * @param int $post_id The ID of the imported post.
	 * @return void This function does not return any value.
	 */
	function set_donor_parent( int $post_id ): void {
		$post_type = 'donor';
		if ( $post_type !== get_post_type( $post_id ) ) {
			return;
		}

		$donor_parent_name = get_post_meta( $post_id, $this->data['parent_name_meta'], true );
		if ( ! $donor_parent_name ) {
			return;
		}

		$donor_parent_name = trim( $donor_parent_name );
		$post_title        = get_the_title( $post_id );

		if ( strcasecmp( $donor_parent_name, $post_title ) === 0 ) {
			return;
		}

		$args = array(
			'post_type'      => $post_type,
			'title'          => $donor_parent_name,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);

		$donor_parent_posts = get_posts( $args );

		if ( ! empty( $donor_parent_posts ) ) {
			$donor_parent_id    = $donor_parent_posts[0];
			$donor_parent_title = get_the_title( $donor_parent_id );

			update_post_meta( $post_id, 'donor_parent_name', $donor_parent_title );
			update_post_meta( $post_id, 'donor_parent_id', $donor_parent_id );
		}
	}

	/**
	 * Updates the transaction donor post meta, sets the post parent, and copies parent post's taxonomy terms after a transaction post is imported.
	 *
	 * This function is hooked to the `pmxi_saved_post` action, which is triggered
	 * after a post is saved by WP All Import.
	 *
	 * @link https://www.wpallimport.com/documentation/developers/action-hooks/pmxi_saved_post/ Documentation for `pmxi_saved_post` action hook.
	 * @param int $post_id The ID of the imported post.
	 * @return void This function does not return any value.
	 */
	function set_transaction_donor_parent( int $post_id ): void {
		$transaction_post_type = 'transaction';
		$donor_post_type       = 'donor';
		$donor_type_taxonomy   = 'donor_type';

		if ( $transaction_post_type !== get_post_type( $post_id ) ) {
			return;
		}

		$donor_name = get_post_meta( $post_id, 'donor', true );
		if ( ! $donor_name ) {
			return;
		}

		$args = array(
			'post_type'      => $donor_post_type,
			'title'          => $donor_name,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);

		$donor_posts = get_posts( $args );

		if ( ! empty( $donor_posts ) ) {
			$donor_post_id   = $donor_posts[0];
			$donor_parent_id = wp_get_post_parent_id( $donor_post_id );

			if ( $donor_parent_id ) {
				$parent_post = get_post( $donor_parent_id );
				if ( $parent_post ) {
					$donor_parent_name = get_post_meta( $post_id, 'donor_parent_name', true );
					if ( ! $donor_parent_name ) {
						$donor_parent_name = get_post_meta( $donor_parent_id, 'donor_parent_name', true );
					}

					$donor_parent_name = trim( $donor_parent_name );
					$post_title        = get_the_title( $post_id );

					if ( strcasecmp( $donor_parent_name, $post_title ) === 0 ) {
						return;
					}

					$parent_terms = wp_get_post_terms( $donor_parent_id, $donor_type_taxonomy, array( 'fields' => 'ids' ) );

					$post_data = array(
						'ID'          => $post_id,
						'post_parent' => $donor_parent_id,
						'meta_input'  => array(
							'donor_parent_name' => $donor_parent_name,
							'donor_parent_id'   => $donor_parent_id,
							'donor_id'          => $donor_post_id,
						),
						'tax_input'   => array(
							$donor_type_taxonomy => $parent_terms,
						),
					);

					wp_update_post( $post_data );
				}
			}
		}
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
	 * Assign transaction data
	 *
	 * @param  int    $import_id
	 * @param  object $import
	 * @return void
	 */
	// public function set_transaction_data_after_import( $import_id, $import ) : void {
	// if ( 8 !== $import_id ) {
	// return;
	// }
	// $this->assign_transaction_data();
	// }

	/**
	 * Sets additional data for 'transaction' posts after XML import.
	 *
	 * @param int $import_id The ID of the import process.
	 */
	public function set_transaction_data_after_import( $import_id ) {
		if ( $this->data['transactions_import_id'] !== $import_id ) {
			return;
		}

		$transaction_ids = $this->get_transaction_ids();

		foreach ( $transaction_ids as $transaction_id ) {
			$this->process_transaction( $transaction_id );
		}
	}

	/**
	 * Retrieves the IDs of all 'transaction' posts.
	 *
	 * @return array An array of 'transaction' post IDs.
	 */
	private function get_transaction_ids() {
		$args = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		return get_posts( $args );
	}

	/**
	 * Processes a single 'transaction' post by updating post meta and copying taxonomy terms.
	 *
	 * @param int $transaction_id The ID of the 'transaction' post to process.
	 */
	private function process_transaction( $transaction_id ) {
		$donor_name = get_post_meta( $transaction_id, 'donor', true );

		if ( ! empty( $donor_name ) ) {
			$donor = get_donor_by_title( $donor_name, $post_type );

			if ( $donor ) {
				$donor_parent_id = $donor->post_parent;
				$this->update_post_meta_and_terms( $transaction_id, $donor, $donor_parent_id );
			}
		}
	}

	/**
	 * Updates post meta and copies taxonomy terms for a 'transaction' post.
	 *
	 * @param int     $transaction_id The ID of the 'transaction' post.
	 * @param WP_Post $donor The 'donor' post object.
	 * @param int     $donor_parent_id The ID of the 'donor' post parent.
	 */
	private function update_post_meta_and_terms( $transaction_id, $donor, $donor_parent_id ) {
		if ( 0 === $donor_parent_id ) {
			update_post_meta( $transaction_id, 'donor_parent_name', $donor->post_title );
			update_post_meta( $transaction_id, 'donor_parent_id', $donor->ID );
		} else {
			$donor_parent = get_post( $donor_parent_id );

			if ( $donor_parent && 'donor' === $donor_parent->post_type ) {
				update_post_meta( $transaction_id, 'donor_parent_name', $donor_parent->post_title );
				update_post_meta( $transaction_id, 'donor_parent_id', $donor_parent->ID );
				$this->copy_taxonomy_terms( $transaction_id, $donor_parent->ID );
			}
		}
	}

	/**
	 * Copies taxonomy terms from a 'donor' post parent to a 'transaction' post.
	 *
	 * @param int $transaction_id The ID of the 'transaction' post.
	 * @param int $donor_parent_id The ID of the 'donor' post parent.
	 */
	private function copy_taxonomy_terms( $transaction_id, $donor_parent_id ) {
		foreach ( $this->data['taxonomies'] as $taxonomy ) {
			$terms = wp_get_post_terms( $donor_parent_id, $taxonomy, array( 'fields' => 'ids' ) );
			wp_set_post_terms( $transaction_id, $terms, $taxonomy, false );
		}
	}

	/**
	 * Save transaction data as postmeta
	 *
	 * @return void
	 */
	public function assign_transaction_data( $args = array() ) : void {
		$this->assign_think_tank_data();
		$this->assign_donor_data();
	}

	/**
	 * Save transaction data as postmeta
	 *
	 * @return void
	 */
	public function assign_think_tank_data() : void {
		$think_tanks = $this->get_think_tanks();

		if ( ! empty( $think_tanks ) && ! is_wp_error( $think_tanks ) ) {
			foreach ( $think_tanks as $think_tank_id ) {
				$data = $this->get_single_think_tank_data( $think_tank_id );
				if ( $data ) {
					\update_post_meta( $think_tank_id, 'transactions', $data, true );
					$cumulative_data = $this->calculate_data( $think_tank_id, $data );
					\update_post_meta( $think_tank_id, 'cumulative_data', $cumulative_data, true );
					foreach ( $cumulative_data as $key => $value ) {
						// $raw_value       = $value;
						// $format          = new \NumberFormatter( 'en', \NumberFormatter::CURRENCY );
						// $formatted_value = $format->formatCurrency( (int) $value, 'USD' );
						// update_post_meta( $think_tank_id, $key . '_cumulative', $formatted_value, true );
						\update_post_meta( $think_tank_id, $key . '_cumulative', $value, true );
					}
				}
			}
		}
	}

	/**
	 * Save transaction data as postmeta
	 *
	 * @return void
	 */
	function assign_donor_data() : void {
		$donors = $this->get_donors();

		if ( ! empty( $donors ) && ! is_wp_error( $donors ) ) {
			foreach ( $donors as $donor_id ) {
				$data = $this->get_donor_data( $donor_id );
				if ( $data ) {
					\update_post_meta( $donor_id, 'transactions', $data, true );
					$cumulative_data = $this->calculate_data( $donor_id, $data );
					\update_post_meta( $donor_id, 'cumulative_data', $cumulative_data, true );
					foreach ( $cumulative_data as $key => $value ) {
						// $raw_value       = $value;
						// $format          = new \NumberFormatter( 'en', \NumberFormatter::CURRENCY );
						// $formatted_value = $format->formatCurrency( (int) $value, 'USD' );
						// update_post_meta( $donor_id, $key . '_cumulative', $formatted_value, true );
						\update_post_meta( $donor_id, $key . '_cumulative', $value, true );
					}
				}
			}
		}
	}

	/**
	 * Calculate amounts
	 *
	 * @param  int   $post_id
	 * @param  array $data
	 * @return array
	 */
	function calculate_data( $post_id, $data ) : array {
		$amount      = wp_list_pluck( $data, 'amount' );
		$amount_min  = wp_list_pluck( $data, 'amount_min' );
		$amount_max  = wp_list_pluck( $data, 'amount_max' );
		$amount_calc = wp_list_pluck( $data, 'amount_calc' );
		$years       = array_filter(
			array_unique( wp_list_pluck( $data, 'year' ) ),
			function ( $year ) {
				return 0 != $year;
			}
		);

		$years_array      = array();
		$years_cumulative = array();
		foreach ( $years as $year ) {
			/**
			 * Filter for $year
			 */
			$years_array[ $year ] = array_filter(
				$data,
				function( $var ) use ( $year ) {
					return $year == $var['year'];
				}
			);

			/**
			 * Sum amount_calc for $year
			 */
			$years_cumulative[ $year ] = array_sum( wp_list_pluck( $years_array[ $year ], 'amount_calc' ) );
		}

		$domestic = wp_list_pluck(
			array_filter(
				$data,
				function( $var ) {
					return 'U.S. Government' == $var['type'];
				}
			),
			'amount_calc'
		);

		$foreign = wp_list_pluck(
			array_filter(
				$data,
				function( $var ) {
					return 'Foreign Government' == $var['type'];
				}
			),
			'amount_calc'
		);

		$defense = wp_list_pluck(
			array_filter(
				$data,
				function( $var ) {
					return 'Pentagon Contractor' == $var['type'];
				}
			),
			'amount_calc'
		);

		return array(
			'amount'          => array_sum( $amount ),
			'amount_min'      => array_sum( $amount_min ),
			'amount_max'      => array_sum( $amount_max ),
			'amount_calc'     => array_sum( $amount_calc ),
			'amount_domestic' => array_sum( $domestic ),
			'amount_foreign'  => array_sum( $foreign ),
			'amount_defense'  => array_sum( $defense ),
			'years'           => $years,
			'years_array'     => $years_array,
			'years_amount'    => $years_cumulative,
		);
	}

	/**
	 * Get think tank data
	 *
	 * @param  integer $post_id
	 * @return void
	 */
	function get_single_think_tank_data( $post_id = 0 ) {
		global $post;
		$post_id      = ( $post_id ) ? $post_id : get_the_ID();
		$post_slug    = get_post_field( 'post_name', $post_id );
		$transactions = $this->get_think_tank_transactions_by_slug( $post_slug );
		return $transactions;
	}

	/**
	 * Get think tank data
	 *
	 * @param  integer $post_id
	 * @return void
	 */
	function get_donor_data( $post_id = 0 ) {
		global $post;
		$post_id      = ( $post_id ) ? $post_id : get_the_ID();
		$post_slug    = \get_post_field( 'post_name', $post_id );
		$transactions = $this->get_donor_transactions_by_slug( $post_slug );
		return $transactions;
	}

	/**
	 * Get the transactions
	 *
	 * @param  string $post_slug
	 * @return array
	 */
	function get_think_tank_transactions_by_slug( $post_slug ) : array {
		$args         = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'think_tank',
					'field'    => 'slug',
					'terms'    => $post_slug,
				),
			),
		);
		$query        = new \WP_Query( $args );
		$transactions = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$transactions[] = $this->get_think_tank_transaction( get_the_ID() );
			}
		}
		return $transactions;
	}

	/**
	 * Get transaction
	 *
	 * @param  integer $post_id
	 * @return array
	 */
	function get_think_tank_transaction( $post_id = 0 ) : array {
		global $post;
		$donation_year     = wp_get_post_terms( $post_id, 'year', array( 'number' => 1 ) );
		$post_id           = ( $post_id ) ? $post_id : $post->ID;
		$donor             = get_post_meta( $post_id, 'donor', true );
		$donor_obj         = wp_get_post_terms( $post_id, 'donor', array( 'number' => 1 ) );
		$donor_parent_obj  = $this->get_transaction_donor_parent( $post_id );
		$donor_parent_name = ( $donor_parent_obj ) ? $donor_parent_obj->post_title : $donor;
		$donor_parent_id   = ( $donor_parent_obj ) ? $donor_parent_obj->ID : '';
		$amount            = get_post_meta( $post_id, 'amount', true );
		$amount_min        = get_post_meta( $post_id, 'amount_min', true );
		$amount_max        = get_post_meta( $post_id, 'amount_max', true );
		$amount_calc       = get_post_meta( $post_id, 'amount_calc', true );
		$data_notes        = get_post_meta( $post_id, 'source_notes', true );
		$disclosed         = get_post_meta( $post_id, 'disclosed', true );
		$source            = get_post_meta( $post_id, 'source', true );
		$type              = get_post_meta( $post_id, 'donor_type', true );
		$type_obj          = wp_get_post_terms( $post_id, 'donor_type', array( 'number' => 1 ) );
		$year              = ( ! empty( $donation_year ) && ! is_wp_error( $donation_year ) ) ? $donation_year : (int) get_post_meta( $post_id, 'year', true );
		$transaction       = array(
			'donor'             => trim( esc_attr( $donor ) ),
			'donor_obj'         => $donor_obj,
			'amount'            => (int) $amount,
			'amount_min'        => (int) $amount_min,
			'amount_max'        => (int) $amount_max,
			'amount_calc'       => (int) $amount_calc,
			'type'              => ( ! empty( $type_obj ) && ! is_wp_error( $type_obj ) ) ? $type_obj[0]->name : trim( $type ),
			'type_obj'          => $type_obj,
			'year'              => $year,
			'disclosed'         => $disclosed,
			'data_notes'        => $data_notes,
			'source'            => $source,
			'donor_parent_name' => $donor_parent_name,
			'donor_parent_id'   => $donor_parent_id,
		);
		return $transaction;
	}

	/**
	 * Get the transactions
	 *
	 * @param  string $post_slug
	 * @return array
	 */
	function get_donor_transactions_by_slug( $post_slug ) : array {
		$args         = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'donor',
					'field'    => 'slug',
					'terms'    => $post_slug,
				),
			),
		);
		$query        = new \WP_Query( $args );
		$transactions = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$transactions[] = $this->get_donor_transaction( get_the_ID() );
			}
		}
		return $transactions;
	}

	/**
	 * Get transaction
	 *
	 * @param  int $post_id
	 * @return array
	 */
	function get_donor_transaction( $post_id ) : array {
		global $post;
		$post_id = ( $post_id ) ? $post_id : $post->ID;

		$donation_year  = wp_get_post_terms( $post_id, 'year', array( 'number' => 1 ) );
		$think_tank     = get_post_meta( $post_id, 'think_tank', true );
		$think_tank_obj = wp_get_post_terms( $post_id, 'think_tank', array( 'number' => 1 ) );
		$amount         = get_post_meta( $post_id, 'amount', true );
		$amount_min     = get_post_meta( $post_id, 'amount_min', true );
		$amount_max     = get_post_meta( $post_id, 'amount_max', true );
		$amount_calc    = get_post_meta( $post_id, 'amount_calc', true );
		$data_notes     = get_post_meta( $post_id, 'source_notes', true );
		$disclosed      = get_post_meta( $post_id, 'disclosed', true );
		$source         = get_post_meta( $post_id, 'source', true );
		$type           = get_post_meta( $post_id, 'donor_type', true );
		$type_obj       = wp_get_post_terms( $post_id, 'donor_type', array( 'number' => 1 ) );
		$year           = ( ! empty( $donation_year ) && ! is_wp_error( $donation_year ) ) ? $donation_year : (int) get_post_meta( $post_id, 'year', true );
		$transaction    = array(
			'think_tank'     => trim( $think_tank ),
			'think_tank_obj' => $think_tank_obj,
			'amount'         => (int) $amount,
			'amount_min'     => (int) $amount_min,
			'amount_max'     => (int) $amount_max,
			'amount_calc'    => (int) $amount_calc,
			'type'           => $type,
			'type_obj'       => $type_obj,
			'year'           => trim( $year ),
			'disclosed'      => trim( $disclosed ),
			'data_notes'     => trim( wp_kses_post( $data_notes ) ),
			'source'         => trim( esc_url( $source ) ),
		);
		return $transaction;
	}

	/**
	 * Get all transaction IDs
	 *
	 * @return array
	 */
	function get_transactions() : array {
		$post_type = 'transaction';
		return $this->find_post_ids( $post_type );
	}

	/**
	 * Get all think tank IDs
	 *
	 * @return array
	 */
	function get_think_tanks() : array {
		$post_type = 'think_tank';
		return $this->find_post_ids( $post_type );
	}

	/**
	 * Get all donor IDs
	 *
	 * @return array
	 */
	function get_donors() : array {
		$post_type = 'donor';
		return $this->find_post_ids( $post_type );
	}

	/**
	 * Get Posts
	 *
	 * @param  string $post_type
	 * @return array
	 */
	function find_post_ids( $post_type = 'transaction' ) : array {
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		return \get_posts( $args );
	}

	/**
	 * Get Posts
	 *
	 * @param  string $post_type
	 * @return array
	 */
	function find_posts( $post_type = 'transaction' ) : array {
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
		);
		return \get_posts( $args );
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
	 * @return mixed array || 0
	 */
	public function get_donor_parent( $post_id, $meta_key = 'donor_parent_name' ) {
		$child_post_title  = \get_post_field( 'post_title', $post_id );
		$parent_post_title = \get_post_meta( $post_id, $meta_key, true );

		if ( $parent_post_title && $parent_post_title !== $child_post_title ) {
			$donor_parent = $this->get_donor_by_title( $parent_post_title );
			return $donor_parent[0];
		}
		return 0;
	}

	/**
	 * Get Transaction Donor Parent
	 *
	 * @param  int    $post_id
	 * @param  string $meta_key
	 * @return mixed array || 0
	 */
	function get_transaction_donor_parent( $post_id, $meta_key = 'donor' ) {
		$donors = $this->get_transaction_donor( $post_id, $meta_key );

		if ( ! empty( $donors ) && ! \is_wp_error( $donors ) ) {
			$parent_meta_key   = 'donor_parent_name';
			$parent_post_title = \get_post_meta( $donors[0]->ID, $parent_meta_key, true );

			if ( $parent_post_title ) {
				$donor_parent = $this->get_donor_by_title( $parent_post_title );
				return $donor_parent[0];
			}
		}

		return 0;
	}

	/**
	 * Get Transaction Donor
	 *
	 * @param  int    $post_id
	 * @param  string $meta_key
	 * @return array
	 */
	function get_transaction_donor( $post_id, $meta_key = 'donor' ) {
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
		return $this->get_post_by_title( $post_title, $post_type );
	}

	/**
	 * Get post by post_title and $post_type
	 *
	 * @param string $post_title The title of the post.
	 * @param string $post_type The custom post type to query.
	 * @return WP_Post|false The post object if found, false otherwise.
	 */
	function get_post_by_title( $post_title, $post_type ) {
		$args = array(
			'post_type'      => $post_type,
			'title'          => $post_title,
			'posts_per_page' => 1,
		);

		$query = new \WP_Query( $args );

		return $query->posts ? $query->posts[0] : false;
	}

	/**
	 * Retrieve donor details by post ID.
	 *
	 * This function retrieves the details of a 'donor' post type by matching
	 * the value of a post meta field 'donor' associated with a given post ID.
	 * It returns the donor's post ID, title, parent ID, and parent title.
	 *
	 * @param int $post_id The ID of the post for which to find the donor.
	 * @return array|null An array containing donor details or null if not found.
	 */
	// function get_transaction_donor_details( $post_id ) {
	// global $wpdb;

	// $donor_meta_value = get_post_meta( $post_id, 'donor', true );

	// if ( ! $donor_meta_value ) {
	// return null;
	// }

	// $donor_name = trim( $donor_meta_value );

	// if ( empty( $donor_name ) ) {
	// return null;
	// }

	// $query = $wpdb->prepare(
	// "
	// SELECT p.ID, p.post_title, p.post_parent, parent.post_title as parent_title
	// FROM $wpdb->posts p
	// LEFT JOIN $wpdb->posts parent ON p.post_parent = parent.ID
	// WHERE p.post_type = %s
	// AND p.post_title = %s
	// AND p.post_status = %s
	// LIMIT 1
	// ",
	// 'donor',
	// $donor_name,
	// 'publish'
	// );

	// $donor_data = $wpdb->get_row( $query );

	// if ( ! $donor_data ) {
	// return null;
	// }

	// return array(
	// 'donor_id'          => $donor_data->ID,
	// 'donor_name'        => $donor_data->post_title,
	// 'donor_parent_id'   => $donor_data->post_parent,
	// 'donor_parent_name' => $donor_data->parent_title,
	// );
	// }

	/**
	 * Retrieve donor details by post ID.
	 *
	 * This function retrieves the details of a 'donor' post type by matching
	 * the value of a post meta field 'donor' associated with a given post ID.
	 * It returns the donor's post ID, title, parent ID, and parent title.
	 *
	 * @param int $post_id The ID of the post for which to find the donor.
	 * @return array|null An array containing donor details or null if not found.
	 */
	public function get_transaction_donor_details( $post_id ) {
		$donor_name = get_post_meta( $post_id, 'donor', true );

		if ( empty( $donor_name ) ) {
			return null;
		}

		$donor_name = trim( $donor_name );

		$args = array(
			'post_type'      => 'donor',
			'title'          => $donor_name,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		);

		$donor_posts = get_posts( $args );

		if ( empty( $donor_posts ) ) {
			return null;
		}

		$donor_post = $donor_posts[0];

		if ( $donor_post->post_parent == 0 ) {
			$donor_parent_id   = $donor_post->ID;
			$donor_parent_name = $donor_name;
		} else {
			$parent_post       = get_post( $donor_post->post_parent );
			$donor_parent_id   = $donor_post->post_parent;
			$donor_parent_name = $parent_post ? $parent_post->post_title : '';
		}

		return array(
			'donor_id'          => $donor_post->ID,
			'donor_name'        => $donor_post->post_title,
			'donor_parent_id'   => $donor_parent_id,
			'donor_parent_name' => $donor_parent_name,
		);
	}


}
