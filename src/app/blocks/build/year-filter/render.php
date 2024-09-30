<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
use function \Site_Functionality\Integrations\Data_Tables\get_year_options;
use function \Site_Functionality\Integrations\Data_Tables\get_type_options;

$namespace = 'data-tables';

$donation_year  = sanitize_text_field( get_query_var( 'donation_year', 'all' ) );
$donor_type     = sanitize_text_field( get_query_var( 'donor_type', 'all' ) );
$donation_years = array(
	'all'  => __( 'All', 'data-tables' ),
	'2024' => __( '2024', 'data-tables' ),
	'2023' => __( '2023', 'data-tables' ),
	'2022' => __( '2022', 'data-tables' ),
	'2021' => __( '2023', 'data-tables' ),
	'2020' => __( '2020', 'data-tables' ),
	'2019' => __( '2019', 'data-tables' ),
);
$donor_types    = array(
	'all'                 => __( 'All', 'data-tables' ),
	'foreign-government'  => __( 'Foreign Government', 'data-tables' ),
	'pentagon-contractor' => __( 'Pentagon Contractor', 'data-tables' ),
	'u-s-government'      => __( 'U.S. Government', 'data-tables' ),
);
$table_content  = '<table 
			class="responsive dataTable"
			data-wp-bind--for="context.loadedTable" 
			data-wp-bind--text="state.loadedTable"
		>
		</table>';

wp_interactivity_state(
	$namespace,
	array(
		'donationYears'        => $donation_years,
		'donorTypes'           => $donor_types,
		'donationYear'         => $donation_year, /* Context value */
		'donorType'            => $donor_type,    /* Context value */
		'selectedDonationYear' => $donation_year, /* Initial selected value */
		'selectedDonorType'    => $donor_type,    /* Initial selected value */
		'loadedTable'          => $table_content,
	)
);

// Generate unique id for aria-controls.
$unique_id = wp_unique_id( 'p-' );
$context   = array(
	'donationYear' => $donation_year, /* Currently Selected */
	'donorType'    => $donor_type, /* Currently Selected */
);
?>

<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="<?php echo $namespace; ?>"
	<?php echo wp_interactivity_data_wp_context( $context );
	?>
>
	<div 
		id="custom-filters" 
		class="wp-block-group wpDataTables data-filters is-layout-flow wp-block-group-is-layout-flow" 
		data-table-id="" 
		data-wp-watch="callbacks.log"
	>

	<div class="filter-group year">
		<template 
			data-wp-if="Object.keys(state.donationYears).length > 0"
			data-wp-each--donationYear="Object.keys(state.donationYears)">
			<input 
				type="radio" 
				data-wp-bind--checked="state.selectedDonationYear === donationYear" 
				name="donationYear" 
				data-wp-bind--value="donationYear" 
				data-wp-on--click="actions.handleYearChange">
			<label 
				data-wp-bind--for="'donation-year-' + donationYear" 
				data-wp-bind--text="state.donationYears[donationYear]">
			</label>
		</template>
	</div>

	<div class="filter-group type">
		<template 
			data-wp-if="Object.keys(state.donorTypes).length > 0"
			data-wp-each--donorType="Object.keys(state.donorTypes)">
			<input 
				type="radio" 
				data-wp-bind--checked="state.selectedDonorType === donorType" 
				name="donorType" 
				data-wp-bind--value="donorType" 
				data-wp-on--click="actions.handleDonorTypeChange">
			<label 
				data-wp-bind--for="'donor-type-' + donorType" 
				data-wp-bind--text="state.donorTypes[donorType]">
			</label>
		</template>
	</div>


	<div class="dataTables_wrapper wpDataTables wpDataTablesWrapper">
		<?php echo wp_interactivity_process_directives( $table_content ); ?>
	</div>

</div>
