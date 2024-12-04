<?php
/**
 * Integrations
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\Integrations\Data_Tables;

use Site_Functionality\Common\Abstracts\Base;
use Site_Functionality\Integrations\Data_Tables\Think_Tank;
use Site_Functionality\Integrations\Data_Tables\Donor;
use Site_Functionality\Integrations\Data_Tables\Data_Filters;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data extends Base {


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

		if ( ! empty( $params['think_tank'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'think_tank',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $params['think_tank'] ),
			);
		}

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
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}

			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );

		return ( $query->have_posts() ) ? $query->posts : array();
	}

	/**
	 * Retrieves the top ten think tanks based on donation data, optionally filtered by donor type and donation year.
	 *
	 * @param string $donor_type      The donor type to filter by (optional).
	 * @param string $donation_year   The donation year to filter by (optional).
	 * @param int    $number_of_items The number of top think tanks to retrieve (default is 10).
	 *
	 * @return array An array of top think tanks, each with its name, total amount donated, year, and donor type.
	 */
	public function get_top_ten_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
		$args = array(
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		$posts = $this->get_the_transations( $args );

		$result = array();

		if ( $posts ) {
			foreach ( $posts as $post_id ) {
				$think_tanks = wp_get_post_terms( $post_id, 'think_tank' );
				if ( empty( $think_tanks ) || is_wp_error( $think_tanks ) ) {
					continue;
				}

				$think_tank = $think_tanks[0];
				$amount     = get_post_meta( $post_id, 'amount_calc', true );

				$donation_year = wp_get_post_terms( $post_id, 'donation_year', array( 'fields' => 'names' ) );
				$donor_type    = wp_get_post_terms( $post_id, 'donor_type', array( 'fields' => 'names' ) );

				$result[] = array(
					'think_tank'   => $think_tank->name,
					'total_amount' => (int) $amount,
					'year'         => ( $donation_year ) ? implode( ',', $donation_year ) : '',
					'type'         => ( $donor_type ) ? implode( ',', $donor_type ) : '',
				);
			}

			usort(
				$result,
				function ( $a, $b ) {
					return ( strcmp( $a['think_tank'], $b['think_tank'] ) );
				}
			);

			if ( ! empty( $result ) ) {
				$data = $this->aggregate_top_ten_data( $result );
				return $data;
			}
		}

		return $result;
	}

	/**
	 * Retrieves donation data for a single think tank, optionally filtered by donation year and donor type.
	 *
	 * @param string $think_tank      The think tank to retrieve data for (optional).
	 * @param string $donation_year   The donation year to filter by (optional).
	 * @param string $donor_type      The donor type to filter by (optional).
	 *
	 * @return array An array of donation data for the specified think tank, including donor name, amount, donor type, and donor link.
	 */
	public function get_single_think_tank_data( $think_tank = '', $donation_year = '', $donor_type = '' ) : array {
		$args = array(
			'donor'         => sanitize_text_field( $think_tank ),
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		$posts = $this->get_the_transations( $args );

		$results = array();

		if ( $posts ) {
			foreach ( $posts as $post_id ) {
				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = $donor_names ? implode( ' > ', $donor_names ) : '';
				$donor_slug  = $donor_slugs ? implode( ' > ', $donor_slugs ) : '';

				$amount_calc = (int) get_post_meta( $post_id, 'amount_calc', true );
				if ( empty( $amount_calc ) ) {
					$amount_calc = 0;
				}

				$results[] = array(
					'donor'       => $donor_name,
					'amount_calc' => $amount_calc,
					'donor_type'  => get_the_term_list( $post_id, 'donor_type' ),
					'donor_link'  => get_term_link( $donor_slugs[0], 'donor' ),
					'donor_slug'  => $donor_slug,
					'source'      => get_post_meta( $post_id, 'source', true ),
				);
			}
		}

		$data = $this->aggregate_single_think_tank_data( $results );

		return $data;
	}

	/**
	 * Retrieves donation data for a single donor, optionally filtered by donation year and donor type.
	 *
	 * @param string $donor           The donor to retrieve data for (optional).
	 * @param string $donation_year   The donation year to filter by (optional).
	 * @param string $donor_type      The donor type to filter by (optional).
	 *
	 * @return array An array of donation data for the specified donor, including think tank name, amount, donor type, and donor slug.
	 */
	public function get_single_donor_data( $donor = '', $donation_year = '', $donor_type = '' ) : array {
		$args = array(
			'donor'         => sanitize_text_field( $donor ),
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		$posts = $this->get_the_transations( $args );

		$data = array();

		if ( $posts ) {
			foreach ( $posts as $post_id ) {
				$think_tanks = get_the_terms( $post_id, 'think_tank' );

				if ( empty( $think_tanks ) || is_wp_error( $think_tanks ) ) {
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

				$data[] = array(
					'think_tank'      => $think_tank->name,
					'donor'           => $donor,
					'amount_calc'     => (int) $amount_calc,
					'donor_type'      => $donor_type,
					'source'          => $source,
					'think_tank_slug' => $think_tank_slug,
				);
			}
		}

		$results = $this->aggregate_single_donor_data( $data );

		return $results;
	}

	/**
	 * Retrieves archive data for think tanks, optionally filtered by donation year.
	 *
	 * @param string $donation_year   The donation year to filter by (optional).
	 *
	 * @return array An array of think tank data, including donor types and transparency scores.
	 */
	public function get_think_tank_archive_data( $donation_year = '' ) {
		$args = array(
			'donation_year' => sanitize_text_field( $donation_year ),
		);

		$posts = $this->get_the_transations( $args );

		$data            = array();
		$all_donor_types = array();

		if ( $posts ) {
			foreach ( $posts as $post_id ) {
				$think_tank_terms = wp_get_post_terms( $post_id, 'think_tank' );
				if ( empty( $think_tanks ) || is_wp_error( $think_tanks ) ) {
					continue;
				}

				$think_tank      = $think_tank_terms[0]->name;
				$think_tank_slug = $think_tank_terms[0]->slug;

				if ( ! isset( $data[ $think_tank_slug ] ) ) {
					$think_tank_post_id       = get_post_from_term( $think_tank_slug, 'think_tank' );
					$data[ $think_tank_slug ] = array(
						'think_tank'         => $think_tank,
						'donor_types'        => array(),
						'transparency_score' => get_transparency_score( $think_tank_slug ),
					);
				}

				$donor_type_terms = wp_get_post_terms( $post_id, 'donor_type' );
				foreach ( $donor_type_terms as $donor_type_term ) {
					$donor_type = $donor_type_term->name;

					if ( ! isset( $data[ $think_tank_slug ]['donor_types'][ $donor_type ] ) ) {
						$data[ $think_tank_slug ]['donor_types'][ $donor_type ] = 0;
					}

					$amount_calc = get_post_meta( $post_id, 'amount_calc', true );
					$amount_calc = floatval( $amount_calc );

					$data[ $think_tank_slug ]['donor_types'][ $donor_type ] += $amount_calc;

					$all_donor_types[ $donor_type ] = true;
				}
			}
		}

		$aggregate_data = $this->aggregate_think_tank_archive_data( $data, $all_donor_types );

		if ( $aggregate_data ) {
			return $aggregate_data;
		}

		return ! empty( $data ) ? $data : array();
	}

	/**
	 * Retrieves archive data for donors, optionally filtered by donation year and donor type.
	 *
	 * @param string $donation_year   The donation year to filter by (optional).
	 * @param string $donor_type      The donor type to filter by (optional).
	 *
	 * @return array An array of donor data, including donor names, amount donated, and donor slugs.
	 */
	public function get_donor_archive_data( $donation_year = '', $donor_type = '' ) : array {
		$args = array(
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		$posts = $this->get_the_transations( $args );

		$data = array();

		if ( $posts ) {
			foreach ( $posts as $post_id ) {
				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = $donor_names ? implode( ' > ', $donor_names ) : '';
				$donor_slug  = $donor_slugs ? implode( ' > ', $donor_slugs ) : '';

				$amount = get_post_meta( $post_id, 'amount_calc', true );
				$amount = intval( $amount );

				$data[] = array(
					'donor'       => $donor_name,
					'amount_calc' => $amount,
					'donor_type'  => get_the_term_list( $post_id, 'donor_type' ),
					'donor_slug'  => $donor_slug,
					'donor_link'  => get_term_link( $donor_slugs[0], 'donor' ),
					'year'        => get_the_term_list( $post_id, 'donation_year' ),
				);
			}
		}

		$result = $this->aggregate_donor_archive_data( $data );

		return $result;
	}

	public function aggregate_top_ten_data( $raw_data ) {
		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array();

		foreach ( $raw_data as $item ) {
			$data[ $item['think_tank'] ] += (int) $item['total_amount'];
		}

		arsort( $data );

		$data = array_slice( $data, 0, $number_of_items, true );

		$result = array();
		foreach ( $data as $think_tank => $total_amount ) {
			$result[] = array(
				'think_tank'   => $think_tank,
				'total_amount' => $total_amount,
			);
		}

		return $result;
	}

	public function aggregate_single_think_tank_data( $raw_data ) {
		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array_reduce(
			$raw_data,
			function ( $carry, $item ) {
				$donor_slug = $item['donor_slug'];

				if ( ! isset( $carry[ $donor_slug ] ) ) {
					$carry[ $donor_slug ] = array(
						'donor'       => $item['donor'],
						'amount_calc' => 0,
						'donor_type'  => $item['donor_type'],
						'donor_slug'  => $donor_slug,
						'donor_link'  => $item['donor_link'],
						'source'      => $item['source'],
					);
				}

				$carry[ $donor_slug ]['amount_calc'] += $item['amount_calc'];

				return $carry;
			},
			array()
		);

		ksort( $data );

		return $data;
	}

	public function aggregate_single_donor_data( $raw_data ) {
		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array_reduce(
			$raw_data,
			function ( $carry, $item ) {
				$think_tank_slug = $item['think_tank_slug'];
				if ( ! isset( $carry[ $think_tank_slug ] ) ) {
					$carry[ $think_tank_slug ] = array(
						'think_tank'      => $item['think_tank'],
						'donor'           => $item['donor'],
						'amount_calc'     => 0,
						'donor_type'      => $item['donor_type'],
						'source'          => $item['source'],
						'think_tank_slug' => $think_tank_slug,
					);
				}
				$carry[ $think_tank_slug ]['amount_calc'] += $item['amount_calc'];

				return $carry;
			},
			array()
		);

		ksort( $data );

		return $data;
	}

	public function aggregate_think_tank_archive_data( $raw_data ) {
		$data = $raw_data;
		foreach ( $data as &$think_tank_data ) {
			foreach ( $all_donor_types as $donor_type => $value ) {
				if ( ! isset( $think_tank_data['donor_types'][ $donor_type ] ) ) {
					$think_tank_data['donor_types'][ $donor_type ] = 0;
				}
			}
		}

		ksort( $data );

		return $data;
	}

	public function aggregate_donor_archive_data( $raw_data ) {
		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array_reduce(
			$raw_data,
			function ( $carry, $item ) {
				$donor_slug  = $item['donor_slug'];
				$amount_calc = $item['amount_calc'];
				$year        = $item['year'];

				if ( ! isset( $carry[ $donor_slug ] ) ) {
					$carry[ $donor_slug ] = array(
						'donor'       => $item['donor'],
						'amount_calc' => $amount_calc,
						'donor_type'  => $item['donor_type'],
						'donor_slug'  => $donor_slug,
						'donor_link'  => $item['donor_link'],
						'year'        => $year,
					);
				} else {
					$carry[ $donor_slug ]['amount_calc'] += $amount_calc;

					$years = explode( ', ', $carry[ $donor_slug ]['year'] );
					if ( ! in_array( $year, $years ) ) {
						$years[]                      = $year;
						$carry[ $donor_slug ]['year'] = ( $years ) ? implode( ', ', $years ) : '';
					}
				}

				return $carry;
			},
			array()
		);

		ksort( $data );

		return $data;
	}


	public static function generate_single_think_tank_data( $think_tank = '', $donation_year = '', $donor_type = '' ) {
		$instance = new self( array() );
		return $instance->get_single_think_tank_data( $think_tank, $donation_year, $donor_type );
	}

	public static function generate_single_donor_data( $donor = '', $donation_year = '', $donor_type = '' ) {
		$instance = new self( array() );
		return $instance->get_single_donor_data( $donor, $donation_year, $donor_type );
	}

	public static function generate_think_tank_archive_data( $donation_year ) {
		$instance = new self( array() );
		return $instance->get_think_tank_archive_data( $donation_year );
	}

	public static function generate_donor_archive_data( $donation_year = '', $donor_type = '' ) {
		$instance = new self( array() );
		return $instance->get_donor_archive_data( $donation_year, $donor_type );
	}

	public function generate_single_think_tank_table( $think_tank = '', $donation_year = '', $donor_type = '' ) {
		$donation_year = sanitize_text_field( $donation_year );

		$data = $this->get_single_think_tank_data( $donation_year );

		ob_start();
		if ( $data ) :
			$table_type = 'think-tank-archive';

			$params = array(
				'think_tank'    => '',
				'donation_year' => $donation_year,
				'donor_type'    => '',
			);

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					array(
						'tableType'    => $table_type,
						'data'         => $data,
						'thinkTank'    => sanitize_text_field( $params['think_tank'] ),
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
					<caption><?php printf( 'Donations in <span class="donation-year">%s</span> received from…', $donation_year ); ?></caption>
					<?php
					endif;
				?>
				<thead>
					<tr>
						<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'site-functionality' ); ?></th>
						<?php if ( ! empty( $data ) ) : ?>
							<?php
							$first_entry = reset( $data );
							foreach ( $first_entry['donor_types'] as $donor_type => $amount ) :
								?>
								<th class="column-numeric column-min-amount"><?php echo esc_html( $donor_type ); ?></th>
							<?php endforeach; ?>
						<?php endif; ?>
						<th class="column-numeric column-transparency-score"><?php esc_html_e( 'Score', 'site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
						<?php foreach ( $data as $think_tank_slug => $data ) : ?>
						<tr data-think-tank="<?php echo esc_attr( $think_tank_slug ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'site-functionality' ); ?>"><a href="<?php echo esc_url( get_term_link( $think_tank_slug, 'think_tank' ) ); ?>"><?php echo esc_html( $data['think_tank'] ); ?></a></td>
							<?php foreach ( $data['donor_types'] as $donor_type => $amount ) : ?>
								<td class="column-numeric column-min-amount" data-heading="<?php echo esc_attr( $donor_type ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></td>
							<?php endforeach; ?>
							<td class="column-numeric column-transparency-score" data-heading="<?php esc_attr_e( 'Transparency Score', 'site-functionality' ); ?>"><?php echo esc_html( $data['transparency_score'] ); ?></td>
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

	public function generate_single_donor_table( $donor = '', $donation_year = '', $donor_type = '' ) {
		$donor         = sanitize_text_field( $donor );
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		$data = $this->get_single_donor_data( $donor, $donation_year, $donor_type );

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

		if ( function_exists( '\wp_interactivity_process_directives' ) ) {
			$output = wp_interactivity_process_directives( $output );
		}

		return $output;
	}

	public function generate_think_tank_archive_table( $donation_year = '' ) {
		$donation_year = sanitize_text_field( $donation_year );

		$data = $this->get_think_tank_archive_data( $donation_year );

		ob_start();
		if ( $data ) :
			$table_type = 'think-tank-archive';

			$params = array(
				'think_tank'    => '',
				'donation_year' => $donation_year,
				'donor_type'    => '',
			);

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					array(
						'tableType'    => $table_type,
						'data'         => $data,
						'thinkTank'    => sanitize_text_field( $params['think_tank'] ),
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
					<caption><?php printf( 'Donations in <span class="donation-year">%s</span> received from…', $donation_year ); ?></caption>
					<?php
					endif;
				?>
				<thead>
					<tr>
						<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'site-functionality' ); ?></th>
						<?php if ( ! empty( $data ) ) : ?>
							<?php
							$first_entry = reset( $data );
							foreach ( $first_entry['donor_types'] as $donor_type => $amount ) :
								?>
								<th class="column-numeric column-min-amount"><?php echo esc_html( $donor_type ); ?></th>
							<?php endforeach; ?>
						<?php endif; ?>
						<th class="column-numeric column-transparency-score"><?php esc_html_e( 'Score', 'site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
						<?php foreach ( $data as $think_tank_slug => $data ) : ?>
						<tr data-think-tank="<?php echo esc_attr( $think_tank_slug ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'site-functionality' ); ?>"><a href="<?php echo esc_url( get_term_link( $think_tank_slug, 'think_tank' ) ); ?>"><?php echo esc_html( $data['think_tank'] ); ?></a></td>
							<?php foreach ( $data['donor_types'] as $donor_type => $amount ) : ?>
								<td class="column-numeric column-min-amount" data-heading="<?php echo esc_attr( $donor_type ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></td>
							<?php endforeach; ?>
							<td class="column-numeric column-transparency-score" data-heading="<?php esc_attr_e( 'Transparency Score', 'site-functionality' ); ?>"><?php echo esc_html( $data['transparency_score'] ); ?></td>
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

	public function generate_donor_archive_table( $donation_year = '', $donor_type = '' ) {
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		$data = $this->get_donor_archive_data( $donation_year, $donor_type );

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
		$table_type    = sanitize_text_field( $type_type );
		$think_tank    = sanitize_text_field( $params['think_tank'] );
		$donation_year = sanitize_text_field( $params['donation_year'] );
		$donor_type    = sanitize_text_field( $params['donor_type'] );

		ob_start();
		?>
		<div 
			data-id="#<?php echo Data_Tables::TABLE_ID; ?>"
			data-wp-interactive="<?php echo Data_Tables::APP_ID; ?>"
		>
			<table 
				id="<?php echo Data_Tables::TABLE_ID; ?>" 
				class="<?php echo $table_type; ?> dataTable" 
				data-search-label="<?php esc_attr_e( 'Filter by specific think tank', 'site-functionality' ); ?>"
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

	public static function render_single_think_tank_table( $think_tank = '', $donation_year = '', $donor_type = '' ) : void {
		$instance = new self( array() );
		echo $instance->generate_single_think_tank_table( $think_tank, $donation_year, $donor_type );
	}

	public static function render_single_donor_table( $donor = '', $donation_year = '', $donor_type = '' ) : void {
		$instance = new self( array() );
		echo $instance->generate_single_donor_table( $donor, $donation_year, $donor_type );
	}

	public static function render_think_tank_archive_table( $donation_year = '' ) : void {
		$instance = new self( array() );
		echo $instance->generate_think_tank_archive_table( $donation_year );
	}

	public static function render_donor_archive_table( $donation_year = '', $donor_type = '' ) : void {
		$instance = new self( array() );
		echo $instance->generate_donor_archive_table( $donation_year, $donor_type );
	}





}
