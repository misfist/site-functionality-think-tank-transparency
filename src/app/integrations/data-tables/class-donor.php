<?php
/**
 * Donors
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\Integrations\Data_Tables;

use Site_Functionality\Common\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Donor extends Base {

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
	public function init(): void {}

	/**
	 * Retrieve transaction posts
	 *
	 * @param  array $params array
	 * @return array
	 */
	public function get_the_transations( $params = array() ) : array {
		$args = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$tax_query = array();

		if ( ! empty( $params['donor'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'donor',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $params['donor'] ),
			);
		}

		if ( ! empty( $params['donation_year'] ) && 'all' !== $params['donation_year'] ) {
			$tax_query[] = array(
				'taxonomy' => 'donation_year',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $params['donation_year'] ),
			);
		}

		if ( ! empty( $params['donor_type'] ) && 'all' !== $params['donor_type'] ) {
			$tax_query[] = array(
				'taxonomy' => 'donor_type',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $params['donor_type'] ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			if( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );

		return ( $query->have_posts() ) ? $query->posts : array();
	}

	/**
	 * Retrieve think tank data for individual donor
	 *
	 * @param string $donor Optional. Slug of the donor taxonomy term to filter by.
	 * @param string $donation_year Optional. Slug of the donation_year taxonomy term to filter by.
	 * @return array Array of transaction data.
	 */
	public function get_the_donor_raw_data( $donor = '', $donation_year = '', $donor_type = '' ) {
		$params = array(
			'donor'         => sanitize_text_field( $donor ),
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		$posts = $this->get_the_transations( $params );

		$data = array();

		if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
			foreach ( $posts as $post_id ) {
				$think_tanks = get_the_terms( $post_id, 'think_tank' );

				if ( ! $think_tanks ) {
					continue;
				}

				$think_tank      = $think_tanks[0];
				$think_tank_slug = $think_tank->slug;
				$source          = get_post_meta( $post_id, 'source', true );

				$amount_calc = get_post_meta( $post_id, 'amount_calc', true );
				if ( empty( $amount_calc ) ) {
					$amount_calc = 0;
				}

				$donor_type = get_the_term_list( $post_id, 'donor_type', '', ', ', '' );

				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor       = end( $donor_names );

				$data = array(
					'think_tank'      => $think_tank->name,
					'donor'           => $donor,
					'amount_calc'     => (int) $amount_calc,
					'donor_type'      => $donor_type,
					'source'          => $source,
					'think_tank_slug' => $think_tank_slug,
				);
			}
		}

		return $data;
	}

	/**
	 * Retrieve donor data, optionally filtered by donation year.
	 *
	 * @param string $donation_year The slug of the donation year to filter transactions by (optional).
	 * @return array
	 */
	public function get_the_donors_raw_data( $donation_year = '', $donor_type = '' ): array {
		$params = array(
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		$posts = $this->get_the_transations( $params );

		$data = array();

		if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
			foreach ( $posts as $post_id ) {
				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = implode( ' > ', $donor_names );
				$donor_slug  = implode( '-', $donor_slugs );

				$amount = get_post_meta( $post_id, 'amount_calc', true );
				$amount = intval( $amount );

				$data = array(
					'donor'       => $donor_name,
					'amount_calc' => $amount,
					'donor_type'  => get_the_term_list( $post_id, 'donor_type' ),
					'donor_slug'  => $donor_slug,
					'donor_link'  => get_term_link( $donor_slugs[0], 'donor' ),
					'year'        => get_the_term_list( $post_id, 'donation_year' ),
				);
			}
		}

		return $data;
	}

	/**
	 * Aggregate 'amount_calc' values for individual donor
	 *
	 * @param string $donor Optional. Slug of the donor taxonomy term to filter by.
	 * @param string $donation_year Optional. Slug of the donation_year taxonomy term to filter by.
	 * @return array Aggregated data with summed 'amount_calc' values.
	 */
	public function get_the_donor_data( $donor = '', $donation_year = '', $donor_type = '' ) {
		$raw_data = $this->get_the_donor_raw_data( $donor, $donation_year, $donor_type );

		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array();

		foreach ( $raw_data as $item ) {

			$data[] = $item;

			
			// $think_tank_slug = $item['think_tank_slug'];
	
			// if ( ! isset( $data[ $think_tank_slug ] ) ) {
			// 	$data[ $think_tank_slug ] = array(
			// 		'think_tank'      => $item['think_tank'],
			// 		'donor'           => $item['donor'],
			// 		'amount_calc'     => 0,
			// 		'donor_type'      => $item['donor_type'],
			// 		'source'          => $item['source'],
			// 		'think_tank_slug' => $think_tank_slug,
			// 	);
			// }
	
			// $data[ $think_tank_slug ]['amount_calc'] += $item['amount_calc'];
		}
	
		// ksort( $data );
	
		return $raw_data;

		// $data = array_reduce(
		// 	$raw_data,
		// 	function ( $carry, $item ) {
		// 		$think_tank_slug = $item['think_tank_slug'];
		// 		if ( ! isset( $carry[ $think_tank_slug ] ) ) {
		// 			$carry[ $think_tank_slug ] = array(
		// 				'think_tank'      => $item['think_tank'],
		// 				'donor'           => $item['donor'],
		// 				'amount_calc'     => 0,
		// 				'donor_type'      => $item['donor_type'],
		// 				'source'          => $item['source'],
		// 				'think_tank_slug' => $think_tank_slug,
		// 			);
		// 		}
		// 		$carry[ $think_tank_slug ]['amount_calc'] += $item['amount_calc'];

		// 		return $carry;
		// 	},
		// 	array()
		// );

		// ksort( $data );

		// return $data;
	}

	/**
	 * Get data for donors
	 *
	 * @param  string $donation_year
	 * @return array
	 */
	public function get_the_donors_data( $donation_year = '', $donor_type = '' ) {
		$raw_data = $this->get_the_donors_raw_data( $donation_year, $donor_type );

		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array();

		foreach ( $raw_data as $item ) {
			if( isset( $item['donor_slug'] ) ) {
				$donor_slug  = $item['donor_slug'];
				$amount_calc = $item['amount_calc'];
				$year        = $item['year'];
	
				if ( ! isset( $data[ $donor_slug ] ) ) {
					$data[ $donor_slug ] = array(
						'donor'       => $item['donor'],
						'amount_calc' => $amount_calc,
						'donor_type'  => $item['donor_type'],
						'donor_slug'  => $donor_slug,
						'donor_link'  => $item['donor_link'],
						'year'        => $year,
					);
				} else {
					$data[ $donor_slug ]['amount_calc'] += $amount_calc;
	
					$years = explode( ', ', $data[ $donor_slug ]['year'] );
					if ( ! in_array( $year, $years ) ) {
						$years[] = $year;
						$data[ $donor_slug ]['year'] = implode( ', ', $years );
					}
				}
			}
		}

		ksort( $data );

		return $data;

		// $data = array_reduce(
		// 	$raw_data,
		// 	function ( $carry, $item ) {
		// 		$donor_slug  = $item['donor_slug'];
		// 		$amount_calc = $item['amount_calc'];
		// 		$year        = $item['year'];

		// 		if ( ! isset( $carry[ $donor_slug ] ) ) {
		// 			$carry[ $donor_slug ] = array(
		// 				'donor'       => $item['donor'],
		// 				'amount_calc' => $amount_calc,
		// 				'donor_type'  => $item['donor_type'],
		// 				'donor_slug'  => $donor_slug,
		// 				'donor_link'  => $item['donor_link'],
		// 				'year'        => $year,
		// 			);
		// 		} else {
		// 			$carry[ $donor_slug ]['amount_calc'] += $amount_calc;

		// 			$years = explode( ', ', $carry[ $donor_slug ]['year'] );
		// 			if ( ! in_array( $year, $years ) ) {
		// 				$years                        = $year;
		// 				$carry[ $donor_slug ]['year'] = implode( ', ', $years );
		// 			}
		// 		}

		// 		return $carry;
		// 	},
		// 	array()
		// );

		// ksort( $data );

		// return $data;
	}

	/**
	 * Generate table for individual donor
	 *
	 * @param string $donor    Optional. Slug of the donor.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 */
	public function generate_the_donor_table( $donor = '', $donation_year = '', $donor_type = '' ): string {
		// $year_var      = $_GET['donation_year'];
		// $donor_var     = $_GET['donor_type'];
		// $donation_year = $year_var ? $year_var : $donation_year;
		// $donor_type    = $donor_var ? $donor_var : $donor_type;

		$donor         = sanitize_text_field( $donor );
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		$data = $this->get_the_donor_data( $donor, $donation_year, $donor_type );

		ob_start();
		if ( $data ) :
			$table_type = 'single-donor';

			$params = array(
				'donor'         => $donor,
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			);

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					array(
						'tableType'    => $table_type,
						'data'         => $data,
						'thinkTank'    => sanitize_text_field( $params['donor'] ),
						'donationYear' => sanitize_text_field( $params['donation_year'] ),
						'donorType'    => sanitize_text_field( $params['donor_type'] ),
					)
				);
			}

			echo $this->generate_table_head( $table_type, $params );
			?>
				<?php
				if ( $donation_year ) :
					?>
					<caption data-wp-text="context.donationYear"><?php printf( 'Donations given in <span class="donation-year">%s</span>…', intval( $donation_year ) ); ?></caption>
					<?php
					endif;
				?>
				<thead>
					<tr>
						<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'ttt' ); ?></th>
						<th class="column-donor"><?php esc_html_e( 'Donor', 'ttt' ); ?></th>
						<th class="column-numeric column-min-amount"><?php esc_html_e( 'Min Amount', 'ttt' ); ?></th>
						<th class="column-source"><?php esc_html_e( 'Source', 'ttt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data as $key => $row ) :
						$amount           = $row['amount_calc'];
						$formatted_source = sprintf( '<a href="%1$s" class="source-link" target="_blank"><span class="screen-reader-text">%1$s</span><span class="icon material-symbols-outlined" aria-hidden="true">link</span></a>', esc_url( $row['source'] ) );
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['think_tank_slug'] ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'ttt' ); ?>"><a href="<?php echo esc_url( get_term_link( $row['think_tank_slug'], 'think_tank' ) ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a></td>
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'ttt' ); ?>"><?php echo esc_html( $row['donor'] ); ?></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?>
							<td class="column-source" data-heading="<?php esc_attr_e( 'Source', 'ttt' ); ?>"><?php echo ( $row['source'] ) ? $formatted_source : ''; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			<?php

			echo $this->generate_table_foot();
		endif;

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Generate table for donors
	 *
	 * @param  string $donation_year
	 * @return string HTML table markup.
	 */
	public function generate_the_donors_table( $donation_year = '', $donor_type = '' ): string {
		// $year_var      = $_GET['donation_year'];
		// $donation_year = $year_var ? $year_var : $donation_year;
		// $donor_type    = $donor_var ? $donor_var : $donor_type;

		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		$data = $this->get_the_donors_data( $donation_year, $donor_type );

		ob_start();
		if ( $data ) :
			$table_type = 'donors-archive';

			$params = array(
				'donor'         => '',
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			);

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					array(
						'tableType'    => $table_type,
						'data'         => $data,
						'thinkTank'    => sanitize_text_field( $params['donor'] ),
						'donationYear' => sanitize_text_field( $params['donation_year'] ),
						'donorType'    => sanitize_text_field( $params['donor_type'] ),
					)
				);
			}
			
			echo $this->generate_table_head( $table_type, $params );
			?>
				<?php
				if ( $donation_year ) :
					?>
					<caption"><?php printf( 'Donations given in <span class="donation-year"  data-wp-text="context.donationYear>%s</span>…', $donation_year ); ?></caption>
					<?php
					endif;
				?>
				<thead>
					<tr>
						<th class="column-donor"><?php esc_html_e( 'Donor', 'ttt' ); ?></th>
						<th class="column-numeric column-min-amount"><?php esc_html_e( 'Min Amount', 'ttt' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'ttt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data as $key => $row ) :
						$amount = $row['amount_calc'];
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['donor_slug'] ); ?>">
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'ttt' ); ?>"><a href="<?php echo esc_url( $row['donor_link'] ); ?>"><?php echo esc_html( $row['donor'] ); ?></a></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?>
							<td class="column-donor-type" data-heading="<?php esc_attr_e( 'Type', 'ttt' ); ?>"><?php echo $row['donor_type']; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			<?php
			echo $this->generate_table_foot();

		endif;

		$output = ob_get_clean();

		if ( function_exists( '\wp_interactivity_process_directives' ) ) {
			$output = wp_interactivity_process_directives( $output );
		}

		return $output;
	}

	/**
	 * Output table head
	 *
	 * @param  string $table_type
	 * @param  array  $params
	 * @return string
	 */
	public function generate_table_head( $table_type, $params = array() ): string {
		$table_type            = sanitize_text_field( $type_type );
		$camel_case_table_type = dash_to_camel( $table_type );
		$donor                 = sanitize_text_field( $params['donor'] );
		$donation_year         = sanitize_text_field( $params['donation_year'] );
		$donor_type            = sanitize_text_field( $params['donor_type'] );

		ob_start();
		?>

		<div 
			data-wp-interactive-value="#<?php echo TABLE_ID; ?>"
		>
			<table 
				id="<?php echo TABLE_ID; ?>" 
				class="<?php echo $table_type; ?> dataTable" 
				data-search-label="<?php esc_attr_e( 'Filter by specific donor', 'site-functionality' ); ?>"
				data-table-type="<?php echo $table_type; ?>"
			>

		<?php
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * Output table foot
	 *
	 * @return string
	 */
	public function generate_table_foot(): string {
		ob_start();
		?>
			</table>
		</div>
		
		<?php
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * Static: Retrieve transaction posts
	 *
	 * @param  array $params array
	 * @return array
	 */
	public static function get_transations( $params = array() ) : array {
		$instance = new self([]);
		return $instance->get_the_transations( $params );
	}

	/**
	 * Static: Retrieve think tank data for individual donor
	 *
	 * @param string $donor Optional.
	 * @param string $donation_year Optional.
	 * @param string $donor_type Optional.
	 * @return array Array of transaction data.
	 */
	public static function get_donor_raw_data( $donor = '', $donation_year = '', $donor_type = '' ) {
		$instance = new self([]);
		return $instance->get_the_donor_raw_data( $donor, $donation_year, $donor_type );
	}

	/**
	 * Static: Retrieve donor data, optionally filtered by donation year.
	 *
	 * @param string $donation_year
	 * @return array
	 */
	public static function get_donors_raw_data( $donation_year = '', $donor_type = '' ): array {
		$instance = new self([]);
		return $instance->get_the_donors_raw_data( $donation_year, $donor_type );
	}

	/**
	 * Static: Aggregate 'amount_calc' values for individual donor
	 *
	 * @param string $donor Optional.
	 * @param string $donation_year Optional.
	 * @param string $donor_type Optional.
	 * @return array Aggregated data with summed 'amount_calc' values.
	 */
	public static function get_single_donor_data( $donor = '', $donation_year = '', $donor_type = '' ) {
		$instance = new self([]);
		return $instance->get_the_donor_data( $donor, $donation_year, $donor_type );
	}

	/**
	 * Static: Aggregate 'amount_calc' values for donors archive
	 *
	 * @param string $donation_year Optional.
	 * @param string $donor_type Optional.
	 * @return array Aggregated data with summed 'amount_calc' values.
	 */
	public static function get_donor_archive_data( $donation_year = '', $donor_type = '' ) {
		$instance = new self([]);
		return $instance->get_the_donors_data( $donation_year, $donor_type );
	}

	/**
	 * Generate table for individual donor
	 *
	 * @param string $donor    Optional. Slug of the donor.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 */
	public static function generate_single_donor( $donor = '', $donation_year = '', $donor_type = '' ): string {
		$instance = new self([]);
		return $instance->generate_the_donor_table( $donor, $donation_year, $donor_type );
	}

	/**
	 * Generate table for donors
	 *
	 * @param  string $donation_year
	 * @return string HTML table markup.
	 */
	public static function generate_donor_archive( $donation_year = '', $donor_type = '' ): string {
		$instance = new self([]);
		return $instance->generate_the_donors_table( $donation_year, $donor_type );
	}

	/**
	 * Render table for individual donor
	 *
	 * @param string $donor    Optional. Slug of the donor.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return void
	 */
	public static function render_single_donor( $donor = '', $donation_year = '', $donor_type = '' ): void {
		$instance = new self([]);
		echo $instance->generate_the_donor_table( $donor, $donation_year, $donor_type );
	}

	/**
	 * Render table for donors
	 *
	 * @param  string $donation_year
	 * @return void
	 */
	public static function render_donor_archive( $donation_year = '', $donor_type = '' ): void {
		$instance = new self([]);
		echo $instance->generate_the_donors_table( $donation_year, $donor_type );
	}

}
