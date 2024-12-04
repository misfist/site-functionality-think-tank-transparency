<?php
/**
 * Think Tanks
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\Integrations\Data_Tables;

use Site_Functionality\Common\Abstracts\Base;
use function Site_Functionality\Integrations\Data_Tables\get_post_from_term;
use function Site_Functionality\Integrations\Data_Tables\get_transparency_score;
use function Site_Functionality\Integrations\Data_Tables\camel_to_dash;
use function Site_Functionality\Integrations\Data_Tables\dash_to_camel;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Think_Tank extends Base {

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

		// echo '<pre>';
		// var_dump( $params );
		// echo '</pre>';

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
	 * Get Raw Table Data
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return array An array of transaction data including think_tank term and total amount.
	 */
	public function get_the_top_ten_raw_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
		$params = array(
			'donor_type'      => sanitize_text_field( $think_tank ),
			'donation_year'   => sanitize_text_field( $donation_year ),
			'number_of_items' => intval( $number_of_items ),
		);

		$posts = $this->get_the_transations( $params );

		$result = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$think_tanks = wp_get_post_terms( $post->ID, 'think_tank' );
				if ( ! $think_tanks ) {
					continue;
				}

				$think_tank = $think_tanks[0];
				$amount     = get_post_meta( $post->ID, 'amount_calc', true );

				$result[] = array(
					'think_tank'   => $think_tank->name,
					'total_amount' => (int) $amount,
					'year'         => implode( ',', wp_get_post_terms( $post->ID, 'donation_year', array( 'fields' => 'names' ) ) ),
					'type'         => implode( ',', wp_get_post_terms( $post->ID, 'donor_type', array( 'fields' => 'names' ) ) ),
				);
			}

			usort(
				$result,
				function ( $a, $b ) {
					return ( strcmp( $a['think_tank'], $b['think_tank'] ) );
				}
			);

		}

		return $result;
	}

	/**
	 * Get raw donor data for think tank.
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return array
	 */
	public function get_the_think_tank_raw_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
		if ( 'all' === $donation_year ) {
			$donation_year = '';
		}
		if ( 'all' === $donor_type ) {
			$donor_type = '';
		}
		
		$params = array(
			'think_tank'    => sanitize_text_field( $think_tank ),
			'donation_year' => sanitize_text_field( $donation_year ),
			'donor_type'    => sanitize_text_field( $donor_type ),
		);

		// $think_tank_var    = $_GET['think_tank'];
		// $donation_year_var = $_GET['donation_year'];
		// $donor_type_var    = $_GET['donor_type'];

		// $think_tank    = ( $think_tank_var ) ? sanitize_text_field( $think_tank_var ) : sanitize_text_field( $params['think_tank'] );
		// $donation_year = ( $donation_year_var ) ? sanitize_text_field( $donation_year_var ) : sanitize_text_field( $params['donation_year'] );
		// $donor_type    = ( $donor_type_var ) ? sanitize_text_field( $donor_type_var ) : sanitize_text_field( $params['donor_type'] );

		$posts = $this->get_the_transations( $params );

		$results = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post_id ) {
				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = implode( ' > ', $donor_names );
				$donor_slug  = implode( '-', $donor_slugs );

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

		return $results;
	}

	/**
	 * Get data for top ten
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return array An array of transaction data including think_tank term and total amount.
	 */
	public function get_the_top_ten_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
		$raw_data = $this->get_the_top_ten_raw_data( $donor_type, $donation_year, $number_of_items );

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

	/**
	 * Get data for think tanks
	 *
	 * @param string $donation_year The donation year to filter by.
	 * @return array Array of think tank data.
	 */
	public function get_the_think_tanks_data( $donation_year = '' ) {
		$params = array(
			'donation_year' => sanitize_text_field( $donation_year ),
		);

		// $donation_year_var = get_query_var( 'donation_year', '' );
		// $donation_year = ( $donation_year_var ) ? sanitize_text_field( $donation_year_var ) : sanitize_text_field( $params['donation_year'] );

		$posts = $this->get_the_transations( $params );

		$data            = array();
		$all_donor_types = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$post_id = $post->ID;

				$think_tank_terms = wp_get_post_terms( get_the_ID(), 'think_tank' );
				if ( ! $think_tank_terms ) {
					continue;
				}

				$think_tank      = $think_tank_terms[0]->name;
				$think_tank_slug = $think_tank_terms[0]->slug;

				if ( ! isset( $data[ $think_tank_slug ] ) ) {
					$post_id                  = get_post_from_term( $think_tank_slug, 'think_tank' );
					$data[ $think_tank_slug ] = array(
						'think_tank'         => $think_tank,
						'donor_types'        => array(),
						'transparency_score' => $this->get_transparency_score( $think_tank_slug ),
					);
				}

				$donor_type_terms = wp_get_post_terms( get_the_ID(), 'donor_type' );
				foreach ( $donor_type_terms as $donor_type_term ) {
					$donor_type = $donor_type_term->name;

					if ( ! isset( $data[ $think_tank_slug ]['donor_types'][ $donor_type ] ) ) {
						$data[ $think_tank_slug ]['donor_types'][ $donor_type ] = 0;
					}

					$amount_calc = get_post_meta( get_the_ID(), 'amount_calc', true );
					$amount_calc = intval( $amount_calc );

					$data[ $think_tank_slug ]['donor_types'][ $donor_type ] += $amount_calc;

					$all_donor_types[ $donor_type ] = true;
				}
			}
		}

		if ( ! empty( $data ) ) {
			foreach ( $data as &$think_tank_data ) {
				foreach ( $all_donor_types as $donor_type => $value ) {
					if ( ! isset( $think_tank_data['donor_types'][ $donor_type ] ) ) {
						$think_tank_data['donor_types'][ $donor_type ] = 0;
					}
				}
			}

			ksort( $data );
		}

		return $data;
	}

	/**
	 * Aggregates donor data for think tank.
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return array
	 */
	public function get_the_think_tank_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
		$raw_data = $this->get_the_think_tank_raw_data( $think_tank, $donation_year, $donor_type );

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

	/**
	 * Generate table for top ten
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return string HTML table markup.
	 */
	public function generate_top_ten_table( $donor_type = '', $donation_year = '', $number_of_items = 10 ): string {
		$data = $this->get_the_top_ten_data( $donor_type, $donation_year, $number_of_items );

		ob_start();
		if ( $data ) :
			?>

			<table 
				id="table-<?php echo sanitize_title( $donor_type ); ?>" 
				class="top-ten-recipients dataTable" 
				data-total-rows="<?php echo intval( count( $data ) ); ?>"
			>
				<thead>
					<tr>
						<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'site-functionality' ); ?></th>
						<th class="column-min-amount column-numeric"><?php esc_html_e( 'Min Amount', 'site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data as $row ) : ?>
						<tr>
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'site-functionality' ); ?>">
								<a href="<?php echo esc_url( get_term_link( $row['think_tank'], 'think_tank' ) ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a>
							</td>
							<td class="column-min-amount column-numeric" data-heading="<?php esc_attr_e( 'Min Amount', 'site-functionality' ); ?>"><?php echo number_format( $row['total_amount'], 0 ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Generate table for think tanks
	 *
	 * @param  string $donation_year
	 * @return string HTML table markup.
	 */
	public function generate_think_tanks_table( $donation_year = '' ): string {
		$donation_year = sanitize_text_field( $donation_year );

		$data = $this->get_the_think_tanks_data( $donation_year );

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

	/**
	 * Generate table for individual think tank
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return string
	 */
	public function generate_think_tank_table( $think_tank = '', $donation_year = '', $donor_type = '' ): string {
		// $queried_obj   = get_queried_object();
		// $think_tank    = ( $think_tank ) ? sanitize_text_field( $think_tank ) : $queried_obj->post_name;
		// $year_var      = $_GET['donation_year'];
		// $donor_var     = $_GET['donor_type'];
		// $donation_year = $year_var ? $year_var : $donation_year;
		// $donation_year = sanitize_text_field( $donation_year );
		// $donor_type    = $donor_var ? $donor_var : $donor_type;
		// $donor_type    = sanitize_text_field( $donor_type );

		$data = $this->get_the_think_tank_data( $think_tank, $donation_year, $donor_type );

		ob_start();
		if ( $data ) :
			$table_type = 'single-think-tank';

			$params = array(
				'think_tank'    => $think_tank,
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			);

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					array(
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
					<caption><?php esc_html_e( 'Donations received in', 'site-functionality' ); ?> <span class="donation-year" data-wp-text="context.donationYear"></span></caption>
					<?php
					endif;
				?>
				<thead>
					<tr>
						<th class="column-donor"><?php esc_html_e( 'Donor', 'site-functionality' ); ?></th>
						<th class="column-numeric column-min-amount"><?php esc_html_e( 'Min Amount', 'site-functionality' ); ?></th>
						<th class="column-source"><?php esc_html_e( 'Source', 'site-functionality' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data as $key => $row ) :
						$amount           = $row['amount_calc'];
						$formatted_source = sprintf( '<a href="%1$s" class="source-link" target="_blank"><span class="screen-reader-text">%1$s</span><span class="icon material-symbols-outlined" aria-hidden="true">link</span></a>', esc_url( $row['source'] ) );
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['donor_slug'] ); ?>">
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'ttt' ); ?>"><a href="<?php echo esc_url( $row['donor_link'] ); ?>"><?php echo esc_html( $row['donor'] ); ?></a></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?>
							<td class="column-source" data-heading="<?php esc_attr_e( 'Source', 'ttt' ); ?>"><?php echo ( $row['source'] ) ? $formatted_source : ''; ?></td>
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
		$think_tank            = sanitize_text_field( $params['think_tank'] );
		$donation_year         = sanitize_text_field( $params['donation_year'] );
		$donor_type            = sanitize_text_field( $params['donor_type'] );

		if ( 'all' === $donation_year ) {
			$donation_year = '';
		}
		if ( 'all' === $donor_type ) {
			$donor_type = '';
		}

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

	/**
	 * Retrieves the Transparency Score for a given think tank slug.
	 *
	 * @param string $think_tank_slug The think tank slug.
	 * @return int The Transparency Score.
	 */
	public function get_transparency_score( $think_tank_slug ): int {
		$post_type = 'think_tank';
		$args      = array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'name'           => $think_tank_slug,
			'fields'         => 'ids',
		);

		$think_tank = get_post_from_term( $think_tank_slug, $post_type );

		if ( ! empty( $think_tank ) && ! is_wp_error( $think_tank ) ) {
			$score = get_post_meta( $think_tank[0], 'transparency_score', true );
			wp_reset_postdata();
			return intval( $score );
		}

		return 0;
	}

	/**
	 * Static: Retrieve individual think tank data
	 *
	 * @param string $think_tank Optional.
	 * @param string $donation_year Optional.
	 * @param string $donor_type Optional.
	 * @return array Array of transaction data.
	 */
	public static function get_think_tank_raw_data( $think_tank = '', $donation_year = '', $donor_type = '' ) {
		$instance = new self( array() );
		return $instance->get_the_think_tank_raw_data( $think_tank, $donation_year, $donor_type );
	}

	/**
	 * Get data for top ten
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return array An array of transaction data including think_tank term and total amount.
	 */
	public static function get_top_ten_data( $think_tank = '', $donation_year = '', $number_of_items = 10 ): array {
		$instance = new self( array() );
		return $instance->get_the_top_ten_data( $think_tank, $donation_year, $number_of_items );
	}

	/**
	 * Get data for think tanks
	 *
	 * @param string $donation_year The donation year to filter by.
	 * @return array Array of think tank data.
	 */
	public static function get_think_tank_archive_data( $donation_year = '' ) {
		$instance = new self( array() );
		return $instance->get_the_think_tanks_data( $donation_year );
	}

	/**
	 * Aggregates donor data for think tank.
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return array
	 */
	public static function get_single_think_tank_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
		$instance = new self( array() );
		return $instance->get_the_think_tank_data( $think_tank, $donation_year, $donor_type );
	}

		/**
	 * Generate table for think tanks
	 *
	 * @param  string $donation_year
	 * @return string HTML table markup.
	 */
	public function generate_think_tank_archive_table( $donation_year = '' ): string {
		$instance = new self( array() );
		return $instance->generate_think_tanks_table( $donation_year );
	}

	/**
	 * Generate table for individual think tank
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return string
	 */
	public function generate_single_think_tank_table( $think_tank = '', $donation_year = '', $donor_type = '' ): string {
		$instance = new self( array() );
		return $instance->generate_think_tank_table( $think_tank, $donation_year, $donor_type );
	}

	/**
	 * Generate table for top ten
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return void
	 */
	public static function render_top_ten_table( $donor_type = '', $donation_year = '', $number_of_items = 10 ): void {
		$instance = new self( array() );
		echo $instance->generate_top_ten_table( $think_tank, $donation_year, $donor_type );
	}

	/**
	 * Generate table for think tanks
	 *
	 * @param  string $donation_year
	 * @return void
	 */
	public static function render_think_tank_archive( $donation_year = '' ): void {
		$instance = new self( array() );
		echo $instance->generate_think_tanks_table( $donation_year );
	}

	/**
	 * Generate table for individual think tank
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return void
	 */
	public static function render_single_think_tank( $think_tank = '', $donation_year = '', $donor_type = '' ): void {
		$instance = new self( array() );
		echo $instance->generate_think_tank_table( $think_tank, $donation_year, $donor_type );
	}

}
