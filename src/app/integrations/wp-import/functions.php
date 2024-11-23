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
add_action( 'pmxi_saved_post', __NAMESPACE__ . '\set_transaction_data', 10, 1 );
add_action( 'pmxi_after_xml_import', __NAMESPACE__ . '\set_cumulative_values', 10, 2 );
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
		$donor_terms      = wp_get_post_terms(
			$donor_parent_id,
			$donor_post_type,
			array(
				'fields'  => 'ids',
				'orderby' => 'parent',
			)
		);

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

		if ( $return ) {
			$message = sprintf( '%s set for post ID %d.', json_encode( $post_data ), $post_id );
		} else {
			$message = sprintf( 'Error: data not set for post ID %d.', $post_id );
		}
		// error_log( $message );
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
