<?php
/**
 * Functions
 *
 * @since   1.0.0
 *
 * @package   Site_Functionality
 */

namespace Site_Functionality\Integrations;

use \WP_CLI as WP_CLI;
use  Site_Functionality\App\Taxonomies\Taxonomies;

add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_donor_parent', 10, 1 );
add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_transaction_donor_data', 10, 1 );
add_action( 'pmxi_after_xml_import', __NAMESPACE__ . '\set_cumulative_values', 10, 2 );
add_action( 'pmxi_before_xml_import', __NAMESPACE__ . '\before_import', 10, 1 );

// add_action( 'pmxi_before_delete_post', __NAMESPACE__ . '\delete_post_term', 10, 1 );

// add_action( 'pmxi_before_xml_import', __NAMESPACE__ . '\delete_posts', 10, 1 );
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
	$post_type = 'transaction';

	if ( $post_type !== get_post_type( $post_id ) ) {
		error_log( sprintf( 'Post ID %d is not of type %s.', $post_id, $post_type ) );
		return;
	}

	$taxonomy   = 'donor';
	$donor_name = get_post_meta( $post_id, 'donor_name', true );

	if ( empty( $donor_name ) ) {
		error_log( sprintf( 'No donor name found for post ID %d.', $post_id ) );
		return;
	}

	$donor_term = get_term_by( 'name', $donor_name, $taxonomy );

	if ( empty( $donor_term ) || is_wp_error( $donor_term ) ) {
		error_log( sprintf( 'Donor term %s does not exist.', $donor_name ) );
		return;
	}

	$terms     = array( $donor_term->term_id );
	$parent_id = $donor_term->parent;
	if ( $parent_id ) {
		array_unshift( $terms, $parent_id );
	}

	$current_donor_terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

	if ( $current_donor_terms === $terms ) {
		error_log( sprintf( 'Terms are already correctly assigned for post ID %d.', $post_id ) );
		return;
	}

	$result = wp_set_post_terms( $post_id, $terms, $taxonomy );

	if ( is_wp_error( $result ) ) {
		error_log( sprintf( 'Failed to assign donor terms to post ID %d: %s', $post_id, $result->get_error_message() ) );
	} else {
		error_log( sprintf( 'Donor terms assigned to post ID %d: %s', $post_id, implode( ', ', $terms ) ) );
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
function set_cumulative_values( int $import_id, $import ) : void {
	if ( 8 !== $import_id ) {
		return;
	}
	process_think_tanks();
	process_donors();
}

/**
 * Run before import
 * 
 * @link https://www.wpallimport.com/documentation/developers/action-reference/#pmxi_before_xml_import
 *
 * @param  integer $import_id
 * @return void
 */
function before_import( int $import_id ): void {
	$batch_size = 100;
	delete_posts( $import_id, $batch_size );
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
	// error_log( $message );
	// set_transaction_think_tank_data( $post_id );
	// set_transaction_year_data( $post_id );
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
 * Process think tank data after transactions are imported.
 *
 * @return void
 */
function process_think_tanks(): void {
	$post_type = 'think_tank';

	$args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$think_tanks = get_posts( $args );

	if ( empty( $think_tanks ) || is_wp_error( $think_tanks ) ) {
		return;
	}

	$donor_types = get_terms(
		array(
			'taxonomy' => 'donor_type',
			'fields'   => 'slugs',
		)
	);

	$processed_count = 0;

	foreach ( $think_tanks as $post_id ) {
		$think_tank = get_post_field( 'post_name', $post_id );

		$sums = get_think_tank_sums( $think_tank );

		update_post_meta( $post_id, 'amount_calc', $sums['amount_calc'] );
		update_post_meta( $post_id, 'undisclosed', $sums['undisclosed'] );

		if ( ! empty( $donor_types ) ) {
			foreach ( $donor_types as $donor_type ) {
				$donor_type_sums = get_think_tank_sums( $think_tank, $donor_type );

				update_post_meta( $post_id, 'amount_' . $donor_type, $donor_type_sums['amount_calc'] );
				update_post_meta( $post_id, 'undisclosed_' . $donor_type, $donor_type_sums['undisclosed'] );
			}
		}

		$processed_count++;
	}

	error_log( "Processed $processed_count think tank posts." );
}

/**
 * Update all donor posts meta values
 *
 * @return void
 */
function process_donors() : void {
	$post_type = 'donor';
	$args      = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	$posts = get_posts( $args );

	if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
		foreach ( $posts as $post_id ) {
			$donor = get_post_field( 'post_name', $post_id );

			$sums = get_donor_sums( $donor );

			add_post_meta( $post_id, 'amount_calc', $sums['amount_calc'], true );
			add_post_meta( $post_id, 'undisclosed', $sums['undisclosed'], true );
		}
	}
}

/**
 * Delete posts and associated terms before import.
 *
 * @link https://www.wpallimport.com/documentation/developers/action-reference/#pmxi_before_xml_import
 *
 * @param int $import_id The ID of the import process.
 * @param int $batch_size The number of posts to delete in each batch. Default is 100.
 * @return void
 */
function delete_posts( int $import_id, int $batch_size = 100 ): void {
	$taxonomy_map = array(
		8 => 'transaction',
		5 => 'donor',
		4 => 'donor',
		3 => 'think_tank',
	);

	if ( isset( $taxonomy_map[ $import_id ] ) ) {

		global $wpdb;
		$table = $wpdb->prefix . 'pmxi_posts';

		$query    = $wpdb->prepare( "SELECT `post_id` FROM `{$table}` WHERE `import_id` = %d", $import_id );
		$post_ids = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $post_ids ) && ! is_wp_error( $post_ids ) ) {
			error_log( sprintf( 'There are %d posts to be deleted.', count( $post_ids ) ) );

			$post_batches = array_chunk( wp_list_pluck( $post_ids, 'post_id' ), $batch_size );

			foreach ( $post_batches as $batch ) {
				foreach ( $batch as $post_id ) {
					delete_post_term( $post_id );

					$deleted = wp_delete_post( $post_id, true );
					if ( $deleted ) {
						$message = sprintf( 'Deleted post ID %d.', $post_id );
						log_progress_message( $message );
						error_log( $message );
					} else {
						error_log( "Failed to delete post ID {$post_id}" );
					}
				}
			}
		}
	}
}

/**
 * Delete post term
 *
 * @uses get_term_id_from_post_id()
 *
 * @param  integer $post_id
 * @param  array   $import
 * @return void
 */
function delete_post_term( int $post_id ): void {
	$post_type = get_post_type( $post_id );
	$taxonomy  = $post_type;
	$term_id   = get_term_id_from_post_id( $post_id, $taxonomy );
	if ( false !== $term_id ) {
		$deleted = wp_delete_term( $term_id, $taxonomy );
		if ( $deleted ) {
			$message = sprintf( 'Term %d deleted for post ID %d.', $term_id, $post_id );
			log_progress_message( $message );
            error_log( $message );
		} else {
			error_log( sprintf( 'Failed to delete term %d for post ID %d.', $term_id, $post_id ) );
		}
	}
}

/**
 * Get term ID from post ID
 *
 * @param  integer $post_id
 * @param  string  $taxonomy
 * @return integer|null
 */
function get_term_id_from_post_id( int $post_id, string $taxonomy ): ?int {
	$terms = wp_get_post_terms(
		$post_id,
		$taxonomy,
		array(
			'fields'     => 'ids',
			'hide_empty' => false,
		)
	);
	return ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Get the parent ID of a donor term from its name.
 *
 * @param string $donor_name The name of the donor term.
 * @return int|null The parent term ID, or null if the term does not exist.
 */
function get_donor_parent_id_from_name( string $donor_name ): ?int {
	$taxonomy = 'donor';
	$term     = get_term_by( 'name', $donor_name, $taxonomy );

	return ( ! empty( $term ) ) ? $term[0]->term_id : null;
}

/**
 * Get the hierarchy of a taxonomy term as a string.
 *
 * @param string $term_name The name of the term.
 * @param string $taxonomy  The taxonomy name. Default is 'donor'.
 * @return string The term hierarchy as a formatted string, or an empty string if the term does not exist.
 */
function get_term_hierarchy( string $term_name, string $taxonomy = 'donor' ): string {
	$term = get_term_by( 'name', $term_name, $taxonomy );

	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$hierarchy = array();

	while ( $term ) {
		$hierarchy[] = $term->name;
		$term        = ( $term->parent ) ? get_term( $term->parent, $taxonomy ) : null;
	}

	$hierarchy = array_reverse( $hierarchy );

	return implode( '|', $hierarchy );
}

/**
 * Get the sum of `amount_calc` for a given array of post IDs.
 *
 * @param array $post_ids Array of post IDs.
 * @return int The summed value of `amount_calc`.
 */
function get_total( array $post_ids ): int {
	if ( empty( $post_ids ) ) {
		return 0;
	}

	$total_amount = 0;

	foreach ( $post_ids as $post_id ) {
		$amount_calc   = (int) get_post_meta( $post_id, 'amount_calc', true );
		$total_amount += $amount_calc;
	}

	return $total_amount;
}

/**
 * Check if all posts in a given array of post IDs have `disclosed` set to 'no'.
 *
 * @param array $post_ids Array of post IDs.
 * @return bool True if all posts have `disclosed` set to 'no', false otherwise.
 */
function is_undisclosed( array $post_ids ): bool {
	if ( empty( $post_ids ) ) {
		return false;
	}

	foreach ( $post_ids as $post_id ) {
		$disclosed = get_post_meta( $post_id, 'disclosed', true );
		if ( strtolower( $disclosed ) !== 'no' ) {
			return false;
		}
	}

	return true;
}

/**
 * Get the total `amount_calc` and check if all transactions are undisclosed by terms.
 *
 * @param string $think_tank The slug of the think_tank taxonomy term.
 * @param string $donor_type The slug of the donor_type taxonomy term.
 * @return array {
 *     @type int  $amount_calc The summed value of `amount_calc`.
 *     @type bool $undisclosed True if all transactions are undisclosed, false otherwise.
 * }
 */
function get_think_tank_sums( string $think_tank = '', string $donor_type = '' ): array {
	$post_ids = get_think_tank_post_ids( $think_tank, $donor_type );

	return array(
		'amount_calc' => get_total( $post_ids ),
		'undisclosed' => is_undisclosed( $post_ids ),
	);
}

/**
 * Fetch transactions by taxonomy terms and return post IDs.
 *
 * @param string $think_tank The slug of the think_tank taxonomy term.
 * @param string $donor_type The slug of the donor_type taxonomy term.
 * @return array Array of post IDs.
 */
function get_think_tank_post_ids( string $think_tank = '', string $donor_type = '' ): array {
	$args = array(
		'post_type'      => 'transaction',
		'posts_per_page' => -1,
		'tax_query'      => array(),
		'fields'         => 'ids',
	);

	if ( ! empty( $think_tank ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'think_tank',
			'field'    => 'slug',
			'terms'    => $think_tank,
		);
	}

	if ( ! empty( $donor_type ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'donor_type',
			'field'    => 'slug',
			'terms'    => $donor_type,
		);
	}

	$query = new \WP_Query( $args );

	return $query->have_posts() ? $query->posts : array();
}

/**
 * Get the total `amount_calc` and check if all transactions are undisclosed by terms.
 *
 * @param string $donor The slug of the donor taxonomy term.
 * @return array {
 *     @type int  $amount_calc The summed value of `amount_calc`.
 *     @type bool $undisclosed True if all transactions are undisclosed, false otherwise.
 * }
 */
function get_donor_sums( string $donor = '' ): array {
	$post_ids = get_donor_post_ids( $donor );

	return array(
		'amount_calc' => get_total( $post_ids ),
		'undisclosed' => is_undisclosed( $post_ids ),
	);
}

/**
 * Fetch transactions by taxonomy terms and return post IDs.
 *
 * @param string $donor The slug of the donor taxonomy term.
 * @return array Array of post IDs.
 */
function get_donor_post_ids( string $donor = '' ): array {
	$args = array(
		'post_type'      => 'transaction',
		'posts_per_page' => -1,
		'tax_query'      => array(),
		'fields'         => 'ids',
	);

	if ( ! empty( $donor ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'donor',
			'field'    => 'slug',
			'terms'    => $donor,
		);
	}

	$query = new \WP_Query( $args );

	return $query->have_posts() ? $query->posts : array();
}

/**
 * Log progress message.
 *
 * @param string $message Message to log.
 * @return void
 */
function log_progress_message( string $message ): void {
    $time = esc_html( date( 'H:i:s' ) );
    echo "<div class='progress-msg'>[{$time}] " . esc_html( $message ) . "</div>";
    flush();
}

/**
 * Registers custom WP-CLI commands for transaction data operations.
 *
 * This function registers the WP-CLI commands for handling transaction data,
 * including the cumulative-data subcommand for updating cumulative values.
 */
function register_wp_cli_commands() {
	// if ( class_exists( '\WP_CLI' ) ) {
	// WP_CLI::add_command( 'transaction-data', __NAMESPACE__ . '\\Transaction_Data_CLI' );
	// } else {
	// error_log( 'WP-CLI is not available. The transaction-data command will not be registered.' );
	// }
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
			process_donors();
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
			process_think_tanks();
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
