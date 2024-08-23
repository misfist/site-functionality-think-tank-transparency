<?php
/**
 * WP-CLI utilities
 *
 * @package site-functionality
 */
namespace Site_Functionality\Integrations\CLI;

use \WP_CLI as WP_CLI;
use function WP_CLI\Utils\make_progress_bar;
use function WP_CLI\Utils\get_flag_value;

/**
 * Typed settings.
 */
class Commands {

	/**
	 * Assign transaction values to think tanks
	 * Assign transaction values to donors, assign parent data, assign parent donor_type and year
	 */

	/**
	 * Assigns parent ID to Donor posts.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Whether or not to implement or just test
	 *
	 * ## EXAMPLES
	 *
	 * 		wp custom assign-donor-parents --dry-run
	 *
	 * @param string[] $args Positional arguments.
	 * @param string[] $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function assign_donor_parents( $args = array(), $assoc_args = array() ) {
		$post_type = 'donor';

		if ( function_exists( 'get_flag_value' ) ) {
			$dry_run = get_flag_value( $assoc_args, 'dry-run' );
		}

		/**
		 * 1. Get Donor posts that have `donor_parent` meta
		 */
		$donors = $this->find_donors();

		if ( ! empty( $donors ) && ! is_wp_error( $donors ) ) {
			$count   = count( $donors );
			$updated = 0;

			if ( class_exists( 'WP_CLI' ) ) {
				WP_CLI::log( sprintf( '%s Donor posts containing `donor_parent` found.', $count ) );
			}

			if ( function_exists( 'make_progress_bar' ) ) {
				$progress = make_progress_bar( 'Add Parents', $count );
			}
			foreach ( $donors as $donor ) {
				$child_post_id = $donor->post_id;
				$parent_id     = $donor->parent_post_id;

				/**
				 * Verify parent post exists
				 */
				$post = get_post( $parent_id );

				if ( $post && ! is_wp_error( $post ) ) {
					$post_id = $post->ID;

					/**
					 * 2.2. Assign post->ID of found post to current Donor post
					 */
					if ( $dry_run ) {

						// WP_CLI::log( sprintf( 'DRY-RUN MODE: Post parent (%s) would be added for post (%s)', $post_id, $child_post_id ) );

					} else {

						$post_data = array(
							'ID'          => (int) $child_post_id,
							'post_parent' => (int) $post_id,
							'meta_input'  => array(
								'wp_parent_id'   => (int) $post_id,
								'wp_parent_name' => $post->post_title,
							),
						);

						$updated_post_id = wp_update_post( $post_data );

						if ( $updated_post_id ) {

							$updated++;

							if ( class_exists( 'WP_CLI' ) ) {
								WP_CLI::success( sprintf( 'Post parent (%s) added for post (%s)', $post_id, $updated_post_id ) );
							}
						} else {

							if ( class_exists( 'WP_CLI' ) ) {
								WP_CLI::warning( sprintf( 'Post parent (%s) was found, but wasn\'t added for post (%s)', $post_id, $child_post_id ) );
							}
						}
					}
				} else {

					if ( class_exists( 'WP_CLI' ) ) {
						WP_CLI::warning( sprintf( 'No parent was added for post (%s)', $child_post_id ) );
					}
				}

				if ( function_exists( 'make_progress_bar' ) ) {
					$progress->tick();
				}
			}

			if ( function_exists( 'make_progress_bar' ) ) {
				$progress->finish();
			}

			if ( ! $dry_run ) {
				WP_CLI::success( sprintf( '%s were updated.', $updated ) );
			}
		} else {

			WP_CLI::error( __( 'No posts found.', 'site-functionality' ) );

		}

	}

	/**
	 * Get Posts
	 * Get donors that have donor_parent_name different from post_title
	 *
	 * @return array \WP_Post
	 */
	public function find_donors() : array {
		global $wpdb;
		$post_type = 'donor';
		$meta_key  = 'donor_parent_name';

		$query = $wpdb->prepare(
			"
			SELECT post_child.ID AS post_id, post_parent.ID AS parent_post_id
			FROM {$wpdb->posts} post_child
			JOIN {$wpdb->postmeta} pm ON post_child.ID = pm.post_id
			JOIN {$wpdb->posts} post_parent ON pm.meta_value = post_parent.post_title
			WHERE post_child.post_type = %s
			AND pm.meta_key = %s
			AND pm.meta_value != post_child.post_title
			AND post_parent.post_type = %s
			",
			$post_type,
			$meta_key,
			$post_type
		);

		$results = $wpdb->get_results( $query, OBJECT );

		return $results;
	}

	/**
	 * Get Donor Post ID
	 *
	 * @param  int $child_post_id
	 * @return mixed int $post->ID || false
	 */
	public function find_donor_parent_post_id( $child_post_id ) {
		$post_type        = 'donor';
		$meta_key         = 'donor_parent_name';
		$child_post_title = get_post_field( 'post_title', $child_post_id );
		$parent_title     = get_post_meta( $child_post_id, $meta_key, true );

		if ( $child_post_title === $parent_title ) {
			return false;
		} else {
			$args = array(
				'post_type'      => $post_type,
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'title'          => $parent_title,
			);

			$query = new \WP_Query( $args );

			$posts = $query->get_posts();

			if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
				return $posts[0];
			} else {
				return false;
			}
		}
	}

	/**
	 * Get Donor Post
	 *
	 * @param  int $child_post_id
	 * @return
	 */
	public function find_donor_parent_post( $child_post_id ) {
		$post_type        = 'donor';
		$meta_key         = 'donor_parent_name';
		$child_post_title = get_post_field( 'post_title', $child_post_id );
		$parent_title     = get_post_meta( $child_post_id, $meta_key, true );

		if ( $child_post_title === $parent_title ) {
			return false;
		} else {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => 1,
				'title'          => $parent_title,
			);

			$query = new \WP_Query( $args );

			$posts = $query->get_posts();

			if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
				return $posts[0];
			} else {
				return false;
			}
		}
	}

}

/**
 * Assign transaction data
 *
 * @param  int    $import_id
 * @param  object $import
 * @return void
 */
function assign_transaction_data_after_import( $import_id, $import ) : void {
	add_option( '_assign_transaction_data_after_import', $import_id );
	
	if ( 8 != $import_id ) {
		return;
	}
	assign_transaction_data();
	
	add_option( '_assign_transaction_data_after_import_run', date( 'c' ) );

	return;
}
// \add_action( 'pmxi_after_xml_import', __NAMESPACE__ . '\assign_transaction_data_after_import', 10, 2 );

/**
 * Assign Donor parents
 *
 * @return void
 */
function assign_donor_parents( $args, $assoc_args ) : void {
	$assign_donor_parents = new Commands();
	$results              = $assign_donor_parents->assign_donor_parents( $args, $assoc_args );
}
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'custom assign-donor-parents', __NAMESPACE__ . '\assign_donor_parents' );
}

/**
 * Save transaction data as postmeta
 *
 * @return void
 */
function assign_transaction_data( $args = array() ) : void {
	assign_think_tank_data();
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::success( __( 'Transactions assigned to think tanks.', 'site-functionality' ) );
	}

	assign_donor_data();
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::success( __( 'Transactions assigned to donors.', 'site-functionality' ) );
	}
}
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'transaction-data all', __NAMESPACE__ . '\assign_transaction_data' );
}

/**
 * Assign Transaction Donor Parent
 *
 * @return void
 */
function assign_transaction_donor_parent() : void {
	$transactions = get_transactions();
	if ( ! empty( $transactions ) && ! is_wp_error( $transactions ) ) {
		foreach ( $transactions as $post_id ) {
			$meta_key   = 'donor';
			$donor_name = get_post_meta( $post_id, $meta_key, true );
			if ( $donor_name ) {
				/**
				 * Get parent
				 */
				$donor_parent = find_donor_parent_post( $post_id );

				if ( $donor_parent ) {
					if ( trim( strtolower( $donor_parent->post_title ) ) !== trim( strtolower( $donor_name ) ) ) {

						// Assign donor parent_id and donor post_id to transaction meta
						\wp_update_post(
							array(
								'ID'          => $post_id,
								'post_parent' => $donor_parent_id,
								'meta_input'  => array(
									'assigned_parent_using_name' => $donor_parent_id,
									'assigned_donor_parent_name' => $donor_parent_name,
									'donor_parent_id' => $donor_parent_id,
									'run_from'        => __FUNCTION__,
									'donors'          => $donor_parent,
								),
							)
						);

						return;
					}
				}
			}
		}
	}
}
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'transaction-data donor-parent', __NAMESPACE__ . '\assign_transaction_donor_parent' );
}

/**
 * Save transaction data as postmeta
 *
 * @return void
 */
function assign_think_tank_data() : void {
	$think_tanks = get_think_tanks();

	if ( ! empty( $think_tanks ) && ! is_wp_error( $think_tanks ) ) {
		foreach ( $think_tanks as $think_tank_id ) {
			$data = get_think_tank_data( $think_tank_id );
			if ( $data ) {
				update_post_meta( $think_tank_id, 'transactions', $data, true );
				$cumulative_data = calculate_data( $think_tank_id, $data );
				update_post_meta( $think_tank_id, 'cumulative_data', $cumulative_data, true );
				foreach ( $cumulative_data as $key => $value ) {
					// $raw_value       = $value;
					// $format          = new \NumberFormatter( 'en', \NumberFormatter::CURRENCY );
					// $formatted_value = $format->formatCurrency( (int) $value, 'USD' );
					// update_post_meta( $think_tank_id, $key . '_cumulative', $formatted_value, true );
					update_post_meta( $think_tank_id, $key . '_cumulative', $value, true );
				}
			}
		}
	}
}
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'transaction-data think-tanks', __NAMESPACE__ . '\assign_think_tank_data' );
}

/**
 * Save transaction data as postmeta
 *
 * @return void
 */
function assign_donor_data() : void {
	$donors = get_donors();

	if ( ! empty( $donors ) && ! is_wp_error( $donors ) ) {
		foreach ( $donors as $donor_id ) {
			$data = get_donor_data( $donor_id );
			if ( $data ) {
				update_post_meta( $donor_id, 'transactions', $data, true );
				$cumulative_data = calculate_data( $donor_id, $data );
				update_post_meta( $donor_id, 'cumulative_data', $cumulative_data, true );
				foreach ( $cumulative_data as $key => $value ) {
					// $raw_value       = $value;
					// $format          = new \NumberFormatter( 'en', \NumberFormatter::CURRENCY );
					// $formatted_value = $format->formatCurrency( (int) $value, 'USD' );
					// update_post_meta( $donor_id, $key . '_cumulative', $formatted_value, true );
					update_post_meta( $donor_id, $key . '_cumulative', $value, true );
				}
			}
		}
	}
}
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'transaction-data donors', __NAMESPACE__ . '\assign_donor_data' );
}

/**
 * Get all transaction IDs
 *
 * @return array
 */
function get_transactions() : array {
	$post_type = 'transaction';
	return find_post_ids( $post_type );
}

/**
 * Get all think tank IDs
 *
 * @return array
 */
function get_think_tanks() : array {
	$post_type = 'think_tank';
	return find_post_ids( $post_type );
}

/**
 * Get all donor IDs
 *
 * @return array
 */
function get_donors() : array {
	$post_type = 'donor';
	return find_post_ids( $post_type );
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
 * Check if range
 *
 * @param  string $item
 * @return boolean
 */
function is_range( $item ) : bool {
	return str_contains( '-', $item );
}

/**
 * Parse range
 *
 * @param  string $item
 * @return mixed
 */
function get_range_values( $item ) {
	$range = explode( '-', $item );
	if ( is_array( $range ) ) {
		return range( $range[0], $range[1] );
	}
	return array();
}

/**
 * Get think tank data output
 *
 * @param  integer $post_id
 * @return string $data output
 */
function get_think_tank_output( $post_id = 0 ) {
	global $post;
	$post_id = ( $post_id ) ? $post_id : get_the_ID();
	$data    = get_think_tank_data( $post_id );

	ob_start();

	if ( ! empty( $data ) ) :
		?>
		<?php
		foreach ( $data as $column ) :
			$donor       = ( $column['donor'] ) ? esc_attr( $column['donor'][0]->name ) : '';
			$donor_link  = ( $donor ) ? sprintf( '<a href="%s">%s</a>', get_term_link( $column['donor'][0]->term_id, 'donor' ), $donor ) : '';
			$amount      = ( $column['amount'] ) ? '$' . $column['amount'] : '-';
			$source      = ( $column['source'] ) ? esc_url( $column['source'] ) : '';
			$source_link = ( $source ) ? sprintf( '<a href="%s" class="tooltip"><i class="dashicons dashicons-admin-links"></i><span class="screen-reader-text">%s</span></a>', $source, $source ) : '';
			?>
			<tr
				data-donor="<?php echo $donor; ?>"
				data-amount="<?php echo esc_attr( $amount ); ?>"
				data-source="<?php echo esc_attr( $source ); ?>"
				data-type="<?php echo esc_attr( $column['type']->name ); ?>"
				data-year="<?php echo esc_attr( $column['year'] ); ?>"
				data-disclosed="<?php echo esc_attr( $column['disclosed'] ); ?>"
			>
				<td><?php echo $donor_link; ?></td>
				<td><?php echo esc_attr( $amount ); ?></td>
				<td><?php echo $source_link; ?></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
			<?php
		endforeach;
		?>
		
		<?php
	endif;

	return ob_get_clean();
}

/**
 * Get think tank data
 *
 * @param  integer $post_id
 * @return void
 */
function get_think_tank_data( $post_id = 0 ) {
	global $post;
	$post_id      = ( $post_id ) ? $post_id : get_the_ID();
	$post_slug    = get_post_field( 'post_name', $post_id );
	$transactions = get_think_tank_transactions_by_slug( $post_slug );
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
	$post_slug    = get_post_field( 'post_name', $post_id );
	$transactions = get_donor_transactions_by_slug( $post_slug );
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
			$transactions[] = get_think_tank_transaction( get_the_ID() );
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
	$donor_parent_obj  = find_donor_parent_post_by_title( $donor );
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
			$transactions[] = get_donor_transaction( get_the_ID() );
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
 * Get the transaction
 *
 * @param  array  $source_args
 * @param  object $block_instance
 * @param  string $attribute_name
 * @return void
 */
function think_tank_transactions( array $source_args, $block_instance, string $attribute_name ) {
	global $post;
	$post_id   = $block_instance->context['postId'];
	$post_type = $block_instance->context['postType'];

	$data   = get_think_tank_data( $block_instance->context['postId'] );
	$output = get_think_tank_output( $block_instance->context['postId'] );

	return $output;
}

/**
 * Get Donor Post
 *
 * @param  int $child_post_id
 * @return
 */
function find_donor_parent_post( $child_post_id ) {
	$post_type        = 'donor';
	$meta_key         = 'donor_parent_name';
	$child_post_title = get_post_field( 'post_title', $child_post_id );
	$parent_title     = get_post_meta( $child_post_id, $meta_key, true );

	if ( trim( strtolower( $child_post_title ) ) === trim( strtolower( $parent_title ) ) ) {
		return 0;
	} else {
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'title'          => $parent_title,
		);

		$query = new \WP_Query( $args );

		$posts = $query->get_posts();

		if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
			return $posts[0];
		} else {
			return 0;
		}
	}
}

/**
 * Get Donor Post
 *
 * @param  int $child_post_id
 * @return
 */
function find_donor_post_by_title( $parent_title ) {
	$post_type = 'donor';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => 1,
		'title'          => trim( $parent_title ),
	);

	$query = new \WP_Query( $args );

	$posts = $query->get_posts();

	if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
		return $posts[0];
	} else {
		return false;
	}
}

/**
 * Get Donor Post
 *
 * @param  int $child_post_id
 * @return
 */
function find_donor_parent_post_by_title( $parent_title ) {
	$post_type = 'donor';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => 1,
		'title'          => $parent_title,
	);

	$query = new \WP_Query( $args );

	$posts = $query->get_posts();

	if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
		$donor = $posts[0];

		return find_donor_parent_post( $donor->ID );
	} else {
		return false;
	}
}
