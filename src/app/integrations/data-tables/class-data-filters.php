<?php
/**
 * Integrations
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\Integrations\Data_Tables;

use Site_Functionality\Common\Abstracts\Base;
use function Site_Functionality\Integrations\Data_Tables\get_years;
use function Site_Functionality\Integrations\Data_Tables\get_type_options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data_Filters extends Base {

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
	 * Generate filter markup
	 *
	 * @param  array $years
	 * @return string
	 */
	public function generate_year_filters(): string {
		$years = get_years();
		
		ob_start();

		if ( $years ) {
			array_unshift( $years, esc_html__( 'All', 'site-functionality' ) );

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID
				);
			}
			?>
			<div 
				class="filter-group year"
				data-wp-watch="callbacks.log"
				data-wp-interactive="<?php echo Data_Tables::APP_ID; ?>"
			>
				<template data-wp-each--year="state.years">
					<input 
						type="radio" 
						data-wp-bind--checked="state.isChecked" 
						name="year" 
						data-wp-bind--value="context.year"
						data-wp-on--click="actions.handleYear"
					>
					<label for="" data-wp-text="context.year" data-wp-on--click="actions.handleYear"></label>
				</template>
			</div>
			<?php
		}

		$output = ob_get_clean();

		if( function_exists( '\wp_interactivity_process_directives' ) ) {
			$output = wp_interactivity_process_directives( $output );
		}

		return $output;
	}

		/**
	 * Generate filter markup
	 *
	 * @param  array $years
	 * @return string
	 */
	public function generate_year_filters_backup( $years ): string {
		ob_start();

		if ( $years ) {
			$list = array(
				'years' => $years,
			);

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					$list
				);
			}
			?>
			<div 
				class="filter-group year"
				data-wp-watch="callbacks.log"
				data-wp-interactive="<?php echo Data_Tables::APP_ID; ?>"
				data-wp-context='<?php echo json_encode( $list ); ?>'
			>
				<?php 
				$link = esc_url( add_query_arg( 'donation_year', 'all' ) ); ?>
				<input 
					type="radio" 
					name="filter-year" 
					id="filter-year-all" 
					class="filter-checkbox" 
					value="all" 
					data-query-var="<?php echo $link; ?>" 
					data-wp-on--click="actions.setValue"
					data-wp-bind--checked="state.isYearChecked"
					data-key="donationYear"
					data-value="all"
				/>
				<label for="filter-year-all">
					<?php esc_html_e( 'All', 'site-functionality' ); ?>
				</label>
				<?php
				foreach ( $years as $year ) :
					?>
					<?php
					$link = esc_url( add_query_arg( 'donation_year', 'all' ) ); ?>
					<input 
						type="radio" 
						id="filter-year-<?php echo $year; ?>" 
						name="filter-year" 
						class="filter-checkbox" 
						value="<?php echo $year; ?>" 
						data-query-var="<?php echo $link; ?>" 
						data-wp-on--click="actions.setValue"
						data-wp-bind--checked="state.isYearChecked"
						
						data-key="donationYear"
						data-value="<?php echo $year; ?>"
					/>
					<label for="filter-year-<?php echo $year; ?>">
						<?php echo esc_html( $year ); ?>
					</label>
					<?php
					endforeach;
				?>
			</div>
			<?php
		}

		$output = ob_get_clean();

		if( function_exists( '\wp_interactivity_process_directives' ) ) {
			$output = wp_interactivity_process_directives( $output );
		}

		return $output;
	}

	/**
	 * Generate filter markup
	 *
	 * @param  array $types
	 * @return string
	 */
	public function generate_type_filters( $types ): string {
		ob_start();

		if ( $types ) {
			$list = get_type_options();

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID
				);
			}
			?>
			<div 
				class="filter-group type"
				data-wp-watch="callbacks.log"
				data-wp-interactive="<?php echo Data_Tables::APP_ID; ?>"
			>
			<?php
			foreach( $list as $key => $value ) :
				?>
					<input 
						id="type-filter-<?php echo $key; ?>"
						type="radio" 
						data-wp-bind--checked="state.isTypeChecked" 
						name="donorType"
						value="<?php echo $key; ?>"
						data-wp-on--click="actions.handleType"
					>
					<label for="type-filter-<?php echo $key; ?>" data-wp-on--click="actions.handleType">
						<?php echo esc_html( $value ); ?>
					</label>
				<?php
			endforeach;
			?>
			</div>
			<?php
		}

		$output = ob_get_clean();

		if( function_exists( '\wp_interactivity_process_directives' ) ) {
			$output = wp_interactivity_process_directives( $output );
		}

		return $output;
	}

	/**
	 * Generate filter markup
	 *
	 * @param  array $types
	 * @return string
	 */
	public function generate_type_filters_backup( $types ): string {
		ob_start();

		if ( $types ) {
			$type_slugs      = wp_list_pluck( $types, 'slug' );
			$type_translated = wp_list_pluck( $types, 'name', 'slug' );

			if ( function_exists( '\wp_interactivity_state' ) ) {
				\wp_interactivity_state(
					Data_Tables::APP_ID,
					array(
						'types'           => $type_slugs,
						'translatedTypes' => $type_translated,
						'translatedType'  => function() {
							$state   = \wp_interactivity_state();
							$context = \wp_interactivity_get_context();
							return $state['translatedTypes'][ $context['item'] ];
						},
					)
				);
			}
			?>
			<div 
				class="filter-group type"
				data-wp-watch="callbacks.log"
				data-wp-interactive="<?php echo Data_Tables::APP_ID; ?>"
			>
				<input 
					type="radio" 
					id="filter-type-all" 
					name="filter-type" 
					class="filter-checkbox" 
					value="all" 
					data-query-var="donor_type=" 
					data-wp-on--click="actions.setValue"
					data-wp-bind--checked="state.isTypeChecked"
					data-key="donorType"
					data-value="all"
				/>
				<label for="filter-type-all">
					<?php esc_html_e( 'All', 'site-functionality' ); ?>
				</label>
				<?php
				foreach ( $type_slugs as $slug ) :
					?>
					<input 
						type="radio" 
						id="filter-type-<?php echo $slug; ?>" 
						name="filter-type" 
						class="filter-checkbox" 
						value="<?php echo $slug; ?>" 
						data-query-var="donor_type='<?php echo $slug; ?>'" 
						data-wp-on--click="actions.setValue"
						data-wp-bind--checked="state.isTypeChecked"
						data-key="donorType"
						data-value="<?php echo $slug; ?>"
					/>
					<label for="filter-type-<?php echo $slug; ?>">
						<?php echo esc_html( $type_translated[$slug] ); ?>
					</label>
					<?php
					endforeach;
				?>
			</div>
			<?php
		}

		$output = ob_get_clean();

		if( function_exists( '\wp_interactivity_process_directives' ) ) {
			$output = wp_interactivity_process_directives( $output );
		}

		return $output;
	}

	/**
	 * Render year tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return string
	 */
	public function generate_years(): string {
		return $this->generate_year_filters();
	}

	/**
	 * Render year tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return string
	 */
	public function generate_year_links(): string {
		$years   = get_years();

		return $this->generate_year_filter_links( $years );
	}

	/**
	 * Render year tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return string
	 */
	public function generate_archive_years(): string {
		global $post;
		$post_id = $post->ID;
		$type    = $post->post_type;
		$args    = array(
			'taxonomy' => 'donation_year',
			'fields'   => 'slugs',
			'order'    => 'DESC',
		);
		$years   = $this->get_terms( $args );

		return $this->generate_year_filters( $years );
	}

	/**
	 * Render donor type tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return string
	 */
	public function generate_types(): string {
		global $post;
		$taxonomy = 'donor_type';
		$types    = get_terms(
			array(
				'taxonomy' => $taxonomy,
			)
		);

		return $this->generate_type_filters( $types );
	}

	/**
	 * Get the years list
	 *
	 * @return array
	 */
	public function get_the_year_options() : array {
		$years = get_years();
		if( $years ) {
			array_unshift( $years, esc_html__( 'All', 'site-functionality' ) );
			return $years;
		}
		return array();
	}

	/**
	 * Render year tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return void
	 */
	public function render_the_years( $name = '', $type = 'donor' ): void {
		echo $this->generate_years( $name, $type );
	}

	/**
	 * Render archive year tabs
	 *
	 * @return void
	 */
	public function render_the_archive_years(): void {
		echo $this->generate_archive_years();
	}

	/**
	 * Render donor type tabs
	 *
	 * @return void
	 */
	public function render_the_types(): void {
		echo $this->generate_types();
	}

	/**
	 * Render year tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return void
	 */
	public static function render_years(): void {
		$instance = new self( array() );
		echo $instance->generate_years();
	}

	/**
	 * Render year tabs
	 *
	 * @param  string $name
	 * @param  string $type
	 * @return void
	 */
	public static function render_year_links(): void {
		$instance = new self( array() );
		echo $instance->generate_year_links();
	}

	/**
	 * Render archive year tabs
	 *
	 * @return void
	 */
	public static function render_archive_years(): void {
		$instance = new self( array() );
		echo $instance->generate_archive_years();
	}

	/**
	 * Render donor type tabs
	 *
	 * @return void
	 */
	public static function render_types(): void {
		$instance = new self( array() );
		echo $instance->generate_types();
	}

	/**
	 * Get array of year options
	 *
	 * @return array
	 */
	public static function render_year_options(): array {
		$instance = new self( array() );
		return $instance->get_the_year_options();
	}
	
}
