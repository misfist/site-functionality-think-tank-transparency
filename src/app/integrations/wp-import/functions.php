<?php
/**
 * Functions
 *
 * @since   1.0.0
 *
 * @package   Site_Functionality
 */

namespace Site_Functionality;

use \WP_CLI as WP_CLI;
use  Site_Functionality\App\Taxonomies\Taxonomies;

add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_donor_parent', 10, 1 );
add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_transaction_data', 10, 1 );
add_action( 'pmxi_after_xml_import', __NAMESPACE__ . '\update_cumulative_values_all', 10, 2 );
// add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_transaction_donor', 10, 1 );
// add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_transaction_think_tank', 10, 1 );

/**
 * Updates the donor post meta, sets the post parent, and copies parent post's taxonomy terms after a donor post is imported.
 *
 * This function is hooked to the `pmxi_saved_post` action, which is triggered
 * after a post is saved by WP All Import.
 *
 * @link https://www.wpallimport.com/documentation/developers/action-hooks/pmxi_saved_post/ Documentation for `pmxi_saved_post` action hook.
 * @param int $post_id The ID of the imported post.
 * @return void
 */
function set_donor_parent( int $post_id ): void {
	$donor_post_type     = 'donor';
	$donor_type_taxonomy = 'donor_type';

	if ( $donor_post_type !== get_post_type( $post_id ) ) {
		return;
	}

	$donor_parent_name = get_post_meta( $post_id, 'donor_parent_name', true );
	if ( ! $donor_parent_name ) {
		return;
	}

	$donor_parent_name = trim( $donor_parent_name );
	$post_title        = get_the_title( $post_id );

	if ( strcasecmp( $donor_parent_name, $post_title ) === 0 ) {
		return;
	}

	$args = array(
		'post_type'      => $donor_post_type,
		'title'          => $donor_parent_name,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);

	$donor_parent_posts = get_posts( $args );

	if ( ! empty( $donor_parent_posts ) ) {
		$donor_parent_id = $donor_parent_posts[0];

		$parent_terms = wp_get_post_terms( $donor_parent_id, $donor_type_taxonomy, array( 'fields' => 'ids' ) );

		$post_data = array(
			'ID'          => $post_id,
			'post_parent' => $donor_parent_id,
			'meta_input'  => array(
				'donor_parent_name' => get_the_title( $donor_parent_id ),
				'donor_parent_id'   => $donor_parent_id,
			),
			'tax_input'   => array(
				$donor_type_taxonomy => $parent_terms,
			),
		);

		wp_update_post( $post_data );
	}
}

/**
 * Set transaction data for all transactions.
 *
 * @return void
 */
function set_transactions_data() : void {
	$post_type = 'transaction';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$transactions = get_posts( $args );

	if ( ! empty( $transactions ) && ! \is_wp_error( $transactions ) ) {
		foreach ( $transactions as $transaction_id ) {
			set_transaction_data( $transaction_id );
		}
	}
}

/**
 * Set transaction data
 *
 * This function is hooked to the `pmxi_saved_post` action, which is triggered
 * after a post is saved by WP All Import.
 *
 * @link https://www.wpallimport.com/documentation/developers/action-hooks/pmxi_saved_post/ Documentation for `pmxi_saved_post` action hook.
 *
 * @param  integer $post_id
 * @return void
 */
function set_transaction_data( int $post_id ) : void {
	$post_type = 'transaction';
	if ( $post_type !== get_post_type( $post_id ) ) {
		return;
	}
	set_transaction_donor_data( $post_id );
	$message = sprintf( '%s run for post ID %d.', esc_attr( __NAMESPACE__ . '\set_transaction_data' ), $post_id );
	error_log( $message );
	// set_transaction_think_tank_data( $post_id );
	// set_transaction_year_data( $post_id );
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
function set_transaction_donor_data( int $post_id ): void {
	$transaction_post_type = 'transaction';
	$donor_post_type       = 'donor';
	$donor_type_taxonomy   = 'donor_type';
	$donor_taxonomy        = $donor_post_type;

	if ( $transaction_post_type !== get_post_type( $post_id ) ) {
		$message = sprintf( 'post ID %d is not .', $post_id, esc_attr( $transaction_post_type ) );
		error_log( $message );
		return;
	}

	$donor_name = get_post_meta( $post_id, 'donor_name', true );
	if ( ! $donor_name ) {
		$message = sprintf( 'No donor name set for post ID %d.', $post_id );
		error_log( $message );
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
		$donor_post_name = get_the_title( $donor_post_id );
		$donor_parent    = get_post_parent( $donor_post_id );

		if ( $donor_parent ) {
			$donor_parent_id   = $donor_parent->ID;
			$donor_parent_name = $donor_parent->post_title;
		} else {
			$donor_parent_id   = $donor_post_id;
			$donor_parent_name = $donor_post_name;
		}

		$donor_type_terms = wp_get_post_terms( $donor_parent_id, $donor_type_taxonomy, array( 'fields' => 'ids' ) );
		$donor_terms      = wp_get_post_terms( $donor_parent_id, $donor_post_type, array( 'fields' => 'ids', 'orderby' => 'parent' ) );

		$post_data = array(
			'ID'         => $post_id,
			'meta_input' => array(
				'donor_parent_name' => $donor_parent_name,
				'donor_parent_id'   => $donor_parent_id,
				'donor_name'        => $donor_post_name,
			),
			'tax_input'  => array(
				$donor_type_taxonomy => $donor_type_terms,
				$donor_post_type     => $donor_terms,
			),
		);

		$return = wp_update_post( $post_data );

		if( $return ) {
			$message = sprintf( '%s set for post ID %d.', json_encode( $post_data ), $post_id );
		} else {
			$message = sprintf( 'Error: data not set for post ID %d.', $post_id );
		}
		error_log( $message );
	} else {
		$message = sprintf( 'No donor post found for donor name %s.', $donor_name );
		error_log( $message );
	}
}

/**
 * Set Transaction Think Tank Data
 *
 * @param  integer $post_id
 * @return void
 */
function set_transaction_think_tank_data( int $post_id ) : void {
	$term_name = get_post_meta( $post_id, 'think_tank', true );
	$taxonomy  = 'think_tank';

	$term = get_term_by( 'name', $term_name, $taxonomy );

	if ( $term ) {
		wp_set_post_terms( $post_id, $term->term_id, $taxonomy );
	}
}

/**
 * Set Transaction Year Data
 *
 * @param  integer $post_id
 * @return void
 */
function set_transaction_year_data( int $post_id ) : void {
	$term_name = get_post_meta( $post_id, 'year', true );
	$taxonomy  = 'donation_year';

	$term = get_term_by( 'name', $term_name, $taxonomy );

	if ( $term ) {
		wp_set_post_terms( $post_id, $term->term_id, $taxonomy );
	}
}

/**
 * Update all think tank and donor posts meta values
 * This function is hooked to the 'pmxi_after_xml_import' action and processes
 * each meta key defined in the array to update cumulative values for both donor
 * and think tank post types.
 *
 * @see https://www.wpallimport.com/documentation/developers/action-reference/#pmxi_after_xml_import
 *
 * @return void
 */
function update_cumulative_values_all( int $import_id, $import ) : void {
	if ( 8 !== $import_id ) {
		return;
	}
	update_cumulative_think_tank_values_all();
	update_cumulative_donor_values_all();
}

/**
 * Update all think tank posts meta values
 *
 * @return void
 */
function update_cumulative_think_tank_values_all() : void {
	$post_type = 'think_tank';
	$args      = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$post_ids = get_posts( $args );

	if ( ! empty( $post_ids ) && ! is_wp_error( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			$think_tank  = get_the_title( $post_id );
			$meta_values = get_cumulative_think_tank_values_single( $think_tank );

			wp_update_post(
				array(
					'ID'         => $post_id,
					'meta_input' => (array) $meta_values,
				)
			);
		}
	}
}

/**
 * Update all donor posts meta values
 *
 * @return void
 */
function update_cumulative_donor_values_all() : void {
	$post_type = 'donor';
	$args      = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$post_ids = get_posts( $args );

	if ( ! empty( $post_ids ) && ! is_wp_error( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			$donor       = get_the_title( $post_id );
			$meta_values = get_cumulative_donor_values_single( $donor );

			wp_update_post(
				array(
					'ID'         => $post_id,
					'meta_input' => (array) $meta_values,
				)
			);
		}
	}
}

/**
 * Get cumulative values for think tank
 *
 * @param  string $think_tank
 * @return array
 */
function get_cumulative_think_tank_values_single( $think_tank ) : array {
	$values   = array();
	$values[] = get_cumulative_think_tank_values( $think_tank );

	$domestic = Taxonomies::$domestic;
	$foreign  = Taxonomies::$foreign;
	$defense  = Taxonomies::$defense;
	$amount_domestic = get_cumulative_think_tank_values_by_type( $think_tank, $domestic, array( 'amount_calc' ) );
	$amount_foreign  = get_cumulative_think_tank_values_by_type( $think_tank, $foreign, array( 'amount_calc' ) );
	$amount_defense  = get_cumulative_think_tank_values_by_type( $think_tank, $defense, array( 'amount_calc' ) );

	$values['amount_domestic'] = ( is_array( $amount_domestic ) && isset( $amount_domestic['amount_calc'] ) ) ? $amount_domestic['amount_calc'] : null;
	$values['amount_foreign']  = ( is_array( $amount_foreign ) && isset( $amount_foreign['amount_calc'] ) ) ? $amount_foreign['amount_calc'] : null;
	$values['amount_defense']  = ( is_array( $amount_defense ) && isset( $amount_defense['amount_calc'] ) ) ? $amount_defense['amount_calc'] : null;

	$values['years'] = get_transaction_years_by_term( 'think_tank', $think_tank );

	foreach ( $values['years'] as $year ) {
		$values['years_amount'][ $year ] = get_cumulative_think_tank_values_by_year( $think_tank, $year );
	}

	return $values;
}

/**
 * Get cumulative values for think tank
 *
 * @param  string $think_tank
 * @return array $values
 */
function get_cumulative_donor_values_single( $donor ) : array {
	$values   = array();
	$values[] = get_cumulative_donor_values( $donor );

	$values['years'] = get_transaction_years_by_term( 'donor', $donor );

	foreach ( $values['years'] as $year ) {
		$values['years_amount'][ $year ] = get_cumulative_donor_values_by_year( $donor, $year );
	}

	return $values;
}

/**
 * Get cumulative amounts for think tank
 *
 * @param  string $think_tank Think Tank name
 * @return mixed array || null
 */
function get_cumulative_think_tank_values( $think_tank, $meta_keys = array() ) {
	$post_type          = 'transaction';
	$post_type_taxonomy = 'think_tank';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $post_type_taxonomy,
				'field'    => 'name',
				'terms'    => $think_tank,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative amounts for think tank by year
 *
 * @param  string $think_tank Think Tank name
 * @param  string $type Donor Type
 * @return mixed array || null
 */
function get_cumulative_think_tank_values_by_type( $think_tank, $type, $meta_keys = array() ) {
	$post_type          = 'transaction';
	$post_type_taxonomy = 'think_tank';
	$type_taxonomy      = 'donor_type';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $post_type_taxonomy,
				'field'    => 'name',
				'terms'    => $think_tank,
			),
			array(
				'taxonomy' => $type_taxonomy,
				'field'    => 'name',
				'terms'    => $type,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative amounts for think tank by year
 *
 * @param  string $think_tank Think Tank name
 * @param  string $type Donor Type
 * @return mixed array || null
 */
function get_cumulative_think_tank_values_by_year( $think_tank, $year, $meta_keys = array() ) {
	$post_type          = 'transaction';
	$post_type_taxonomy = 'think_tank';
	$year_taxonomy      = 'donation_year';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $post_type_taxonomy,
				'field'    => 'name',
				'terms'    => $think_tank,
			),
			array(
				'taxonomy' => $year_taxonomy,
				'field'    => 'name',
				'terms'    => $year,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative amounts for think tank by donor type and year
 *
 * @param  string $think_tank Think Tank name
 * @param  string $type Donor Type
 * @return mixed array || null
 */
function get_cumulative_think_tank_values_by_type_and_year( $think_tank, $type, $year, $meta_keys = array() ) {
	$post_type          = 'transaction';
	$post_type_taxonomy = 'think_tank';
	$type_taxonomy      = 'donor_type';
	$year_taxonomy      = 'donation_year';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $post_type_taxonomy,
				'field'    => 'name',
				'terms'    => $think_tank,
			),
			array(
				'taxonomy' => $type_taxonomy,
				'field'    => 'name',
				'terms'    => $type,
			),
			array(
				'taxonomy' => $year_taxonomy,
				'field'    => 'name',
				'terms'    => $year,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative values for donor
 *
 * @param  string $donor
 * @param  array  $meta_keys
 * @return mixed array || null
 */
function get_cumulative_donor_values( $donor, $meta_keys = array() ) {
	$post_type = 'transaction';
	$taxonomy  = 'donor';

	$term      = get_term_by( 'name', $donor, $taxonomy );
	if ( ! $term ) {
		return;
	}

	$tax_query = array(
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		),
	);

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => $tax_query,
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative values for donor by year
 *
 * @param  string $donor
 * @param  string $year
 * @param  array  $meta_keys
 * @return mixed array || null
 */
function get_cumulative_donor_values_by_year( $donor, $year, $meta_keys = array() ) {
	$post_type          = 'transaction';
	$post_type_taxonomy = 'donor';
	$year_taxonomy      = 'donation_year';

	$post_type_term = get_term_by( 'name', $donor, $post_type_taxonomy );
	if ( ! $post_type_term ) {
		return;
	}

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $post_type_taxonomy,
				'field'    => 'term_id',
				'terms'    => $post_type_term->term_id,
			),
			array(
				'taxonomy' => $year_taxonomy,
				'field'    => 'name',
				'terms'    => $year,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative values by year
 *
 * @param  string $year
 * @param  array  $meta_keys
 * @return mixed array || null
 */
function get_cumulative_values_by_year( $year, $meta_keys = array() ) {
	$post_type = 'transaction';
	$taxonomy  = 'donation_year';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'name',
				'terms'    => $year,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get cumulative values by donor year
 *
 * @param  string $type
 * @param  array  $meta_keys
 * @return mixed array || null
 */
function get_cumulative_values_by_type( $type, $meta_keys = array() ) {
	$post_type = 'transaction';
	$taxonomy  = 'donor_type';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'name',
				'terms'    => $type,
			),
		),
	);

	$transaction_ids = get_posts( $args );

	if ( ! empty( $transaction_ids ) && ! is_wp_error( $transaction_ids ) ) {
		return get_cumulative_values( $transaction_ids, $meta_keys );
	}
	return null;
}

/**
 * Get the cumulative value of a specified meta field for 'transaction' posts filtered by multiple taxonomy terms using term IDs.
 * Uses WP_Query to fetch posts and processes data in PHP.
 *
 * @param array  $taxonomies An associative array where keys are taxonomy names and values are arrays of term IDs to filter by.
 * @param string $meta_key The meta key for which to calculate the cumulative value.
 * @return float The cumulative value of the specified meta field.
 */
function get_cumulative_value_by_taxonomy( array $taxonomies, string $meta_key ): float {
	$tax_query = array( 'relation' => 'AND' );

	foreach ( $taxonomies as $taxonomy => $term_ids ) {
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $term_ids,
		);
	}

	$args = array(
		'post_type'      => 'transaction',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => $tax_query,
	);

	$query    = new WP_Query( $args );
	$post_ids = $query->posts;

	if ( empty( $post_ids ) ) {
		return 0.0;
	}

	$meta_values = array_map(
		static function ( int $post_id ) use ( $meta_key ): float {
			return (float) get_post_meta( $post_id, $meta_key, true );
		},
		$post_ids
	);

	return array_sum( $meta_values );
}

/**
 * Retrieves 'donation_year' taxonomy terms for 'transaction' posts with a specific taxonomy term.
 *
 * @param string $taxonomy The taxonomy to query (e.g., 'donor').
 * @param string $term_name The name of the taxonomy term.
 * @return array List of 'donation_year' taxonomy term names.
 */
// function get_transaction_years_by_term( $taxonomy, $term_name ) {
// 	$year_taxonomy = 'donation_year';

// 	$tax_query = array(
// 		array(
// 			'taxonomy' => $taxonomy,
// 			'field'    => 'name',
// 			'terms'    => $term_name,
// 		),
// 	);

// 	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
// 		$term = get_term_by( 'name', $term_name, $taxonomy );
// 		if ( $term ) {
// 			$tax_query = array(
// 				array(
// 					'taxonomy' => $taxonomy,
// 					'field'    => 'term_id',
// 					'terms'    => $term->term_id,
// 				),
// 			);
// 		} else {
// 			$tax_query = array();
// 		}
// 	}

// 	$args = array(
// 		'post_type'      => 'transaction',
// 		'posts_per_page' => -1,
// 		'tax_query'      => $tax_query,
// 		'fields'         => 'ids',
// 	);

// 	$transaction_posts = get_posts( $args );

// 	$years = array();

// 	if ( ! empty( $transaction_posts ) ) {
// 		$years = array_reduce(
// 			$transaction_posts,
// 			function( $carry, $post_id ) use ( $year_taxonomy ) {
// 				$post_year_terms = wp_get_post_terms( $post_id, $year_taxonomy, array( 'fields' => 'names' ) );
// 				return array_merge( $carry, $post_year_terms );
// 			},
// 			array()
// 		);

// 		$years = array_unique( $years );
// 	}

// 	return $years;
// }
function get_transaction_years_by_term( $taxonomy, $term_name ) {
	$year_taxonomy = 'donation_year';

	$tax_query = array(
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'name',
			'terms'    => $term_name,
		),
	);

	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		$term = get_term_by( 'name', $term_name, $taxonomy );
		if ( $term ) {
			$tax_query = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			);
		} else {
			$tax_query = array();
		}
	}

	$args = array(
		'post_type'      => 'transaction',
		'posts_per_page' => 200, // Process in batches
		'tax_query'      => $tax_query,
		'fields'         => 'ids',
		'paged'          => 1,
	);

	$years = array();
	$query_finished = false;

	while ( ! $query_finished ) {
		$transaction_posts = get_posts( $args );

		if ( empty( $transaction_posts ) ) {
			$query_finished = true;
			break;
		}

		foreach ( $transaction_posts as $post_id ) {
			$post_year_terms = wp_get_post_terms( $post_id, $year_taxonomy, array( 'fields' => 'names' ) );
			foreach ( $post_year_terms as $term ) {
                $years[] = $term; // Append terms directly
            }
		}

		// Move to the next page
		$args['paged']++;
	}

	$years = array_unique( $years );
	sort( $years );

	return $years;
}


/**
 * Get cumulative values for posts
 *
 * @param  array $post_ids
 * @param  array $meta_keys
 * @return mixed array || null
 */
function get_cumulative_values( array $post_ids, $meta_keys = array() ) {
	if ( empty( $meta_keys ) ) {
		$meta_keys = array(
			'amount',
			'amount_min',
			'amount_max',
			'amount_calc',
		);
	}

	$values = array();

	foreach ( $meta_keys as $meta_key ) {
		$values[ $meta_key ] = get_cumulative_value_by_post_ids( $post_ids, $meta_key );
	}

	return $values;
}

/**
 * Calculate the sum of values for a given meta key across an array of post IDs.
 *
 * @param array  $post_ids Array of post IDs to sum meta values for.
 * @param string $meta_key The meta key to sum values for.
 * @return float The total sum of meta values.
 */
function get_cumulative_value_by_post_ids( array $post_ids, string $meta_key ): float {
	$meta_values = array_map(
		function ( $post_id ) use ( $meta_key ) {
			$meta_value = get_post_meta( $post_id, $meta_key, true );
			return is_numeric( $meta_value ) ? (float) $meta_value : 0.0;
		},
		$post_ids
	);

	return array_sum( $meta_values );
}

/**
 * Registers custom WP-CLI commands for transaction data operations.
 *
 * This function registers the WP-CLI commands for handling transaction data,
 * including the cumulative-data subcommand for updating cumulative values.
 */
function register_wp_cli_commands() {
	if ( class_exists( '\WP_CLI' ) ) {
		WP_CLI::add_command( 'transaction-data', __NAMESPACE__ . '\\Transaction_Data_CLI' );
	} else {
		error_log( 'WP-CLI is not available. The transaction-data command will not be registered.' );
	}
}

/**
 * Handles WP-CLI commands related to transaction data.
 */
class Transaction_Data_CLI {

	public $data = array(
		'meta_keys' => array(
			'donors'      => array(),
			'think_tanks' => array(),
		),
	);

	/**
	 * Updates cumulative values for donor and think tank post types.
	 *
	 * This function is called when the 'transaction-data cumulative-data' WP-CLI
	 * command is executed. It runs the post_import_update function to update
	 * cumulative values for both donor and think tank post types. Supports a
	 * dry-run mode to simulate updates without making changes.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Perform a dry-run without making any changes. Outputs the actions that would have been taken.
	 *
	 * ## EXAMPLES
	 *
	 *     wp transaction-data cumulative-data
	 *     wp transaction-data cumulative-data --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function cumulative_data( $args, $assoc_args ) : void {
		$this->cumulative_donor_data( $args, $assoc_args );
		$this->cumulative_think_tank_data( $args, $assoc_args );
	}

	/**
	 * Updates cumulative values for donors.
	 *
	 * This function is called when the 'transaction-data cumulative-donor-data' WP-CLI
	 * command is executed. It runs the post_import_update function to update
	 * cumulative values for donors. Supports a
	 * dry-run mode to simulate updates without making changes.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Perform a dry-run without making any changes. Outputs the actions that would have been taken.
	 *
	 * ## EXAMPLES
	 *
	 *     wp transaction-data cumulative-donor-data
	 *     wp transaction-data cumulative-donor-data --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function cumulative_donor_data( $args, $assoc_args ) : void {
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			WP_CLI::log( 'Dry run mode activated. No changes will be made.' );
		}

		if ( $dry_run ) {
			WP_CLI::log( 'Simulating update for donor cumulative values' );
		} else {
			WP_CLI::log( 'Updating donor cumulative values' );
			update_cumulative_donor_values_all();
		}

		WP_CLI::log( 'Cumulative donor values update process completed.' );
	}

	/**
	 * Updates cumulative values for think tanks.
	 *
	 * This function is called when the 'transaction-data cumulative-think-tank-data' WP-CLI
	 * command is executed. It runs the post_import_update function to update
	 * cumulative values for think tanks. Supports a
	 * dry-run mode to simulate updates without making changes.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Perform a dry-run without making any changes. Outputs the actions that would have been taken.
	 *
	 * ## EXAMPLES
	 *
	 *     wp transaction-data cumulative-think-tank-data
	 *     wp transaction-data cumulative-think-tank-data --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function cumulative_think_tank_data( $args, $assoc_args ) : void {
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			WP_CLI::log( 'Dry run mode activated. No changes will be made.' );
		}

		if ( $dry_run ) {
			WP_CLI::log( 'Simulating update for think tank cumulative values' );
		} else {
			WP_CLI::log( 'Updating think tank cumulative values' );
			update_cumulative_think_tank_values_all();
		}

		WP_CLI::log( 'Cumulative think tank values update process completed.' );
	}

	/**
	 * Updates data for transactions.
	 *
	 * This function is called when the 'transaction-data transactions' WP-CLI
	 * command is executed. It runs the post_import_update function to update
	 * transaction post data. Supports a dry-run mode to simulate updates without making changes.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Perform a dry-run without making any changes. Outputs the actions that would have been taken.
	 *
	 * ## EXAMPLES
	 *
	 *     wp transaction-data transactions
	 *     wp transaction-data transactions --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function transactions_data( $args, $assoc_args ) : void {}
}

register_wp_cli_commands();
