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
add_action( 'pmxi_before_delete_post', __NAMESPACE__ . '\delete_post_term', 10, 2 );
add_action( 'pmxi_before_xml_import', __NAMESPACE__ . '\delete_posts', 10, 1 );
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
	// error_log( $message );
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
function set_cumulative_values( int $import_id, $import ) : void {
	if ( 8 !== $import_id ) {
		return;
	}
	process_think_tanks();
	process_donors();
}

/**
 * Update all think tank posts meta values
 *
 * @return void
 */
function process_think_tanks() : void {
	$post_type = 'think_tank';
	$args      = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		// 'fields'         => 'ids',
	);

	$posts = get_posts( $args );

	if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
		$count = 0;
		if ( ! method_exists( '\Ttft\Data_Tables\Data', 'get_single_think_tank_total' ) ) {
			error_log( sprintf( 'Method %s does not exist.', 'Ttft\Data_Tables\Data::get_single_think_tank_total' ) );
			return;
		}

		$args = array(
			'taxonomy' => 'donor_type',
			'fields'   => 'slugs',
		);

		$donor_types = get_terms( $args );

		foreach ( $posts as $post ) {
			$post_id = $post->ID;

			$amount_calc = \Ttft\Data_Tables\Data::get_single_think_tank_total( $post->post_name );

			add_post_meta( $post_id, 'amount_calc', $amount_calc, true );

			foreach ( $donor_types as $donor_type ) {
				$total = \Ttft\Data_Tables\Data::get_single_think_tank_total( $post->post_name, '', $donor_type );

				add_post_meta( $post_id, 'amount_' . $donor_type, $total, true );
			}
		}
	}
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
		// 'fields'         => 'ids',
	);

	$posts = get_posts( $args );

	if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
		if ( ! method_exists( '\Ttft\Data_Tables\Data', 'get_single_donor_total' ) ) {
			error_log( sprintf( 'Method %s does not exist.', 'Ttft\Data_Tables\Data::get_single_donor_total' ) );
			return;
		}

		foreach ( $posts as $post ) {
			$post_id = $post->ID;

			$total = \Ttft\Data_Tables\Data::get_single_donor_total( $post->post_name );

			add_post_meta( $post_id, 'amount_calc', $total, true );

		}
	}
}

/**
 * Delete posts and associated terms before import.
 *
 * @link https://www.wpallimport.com/documentation/developers/action-reference/#pmxi_before_xml_import
 *
 * @param int $import_id The ID of the import process.
 * @return void
 */
function delete_posts( int $import_id ): void {
	$taxonomy_map = array(
		8 => 'transaction',
		5 => 'donor',
		4 => 'donor',
		3 => 'think_tank',
	);

	if ( isset( $taxonomy_map[ $import_id ] ) ) {
		$import = new \PMXI_Import_Record();
		$import->getById( $import_id );
		$import->deletePosts( true );

		$taxonomy = $taxonomy_map[ $import_id ];
		$term_ids = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'fields'     => 'ids',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $term_ids ) ) {
			error_log( sprintf( 'Error retrieving terms for taxonomy %s: %s', $taxonomy, $term_ids->get_error_message() ) );
			return;
		}

		foreach ( $term_ids as $term_id ) {
			$deleted = wp_delete_term( $term_id, $taxonomy );
			$message = $deleted
				? sprintf( 'Term %d deleted from taxonomy %s.', $term_id, $taxonomy )
				: sprintf( 'Failed to delete term %d from taxonomy %s.', $term_id, $taxonomy );
			error_log( $message );
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
function delete_post_term( $post_id, $import ): void {
	$post_type = get_post_type( $post_id );

	if ( 'donor' === $post_type || 'think_tank' === $post_type ) {
		$taxonomy = $post_type;
		$term_id  = get_term_id_from_post_id( $post_id, $taxonomy );
		if ( $term_id ) {
			$deleted = wp_delete_term( $term_id, $taxonomy );
			if ( $deleted ) {
				$message = sprintf( 'Term %d deleted for post ID %d.', $term_id, $post_id );
				error_log( $message );
			}
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
	$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids', 'hide_empty' => false ) );
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
	$term = get_term_by( 'name', $donor_name, $taxonomy );

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
