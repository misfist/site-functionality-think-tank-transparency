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
use function Site_Functionality\Integrations\Data_Tables\get_year_options;
use function Site_Functionality\Integrations\Data_Tables\get_type_options;

$donation_year = get_query_var( 'donation_year', '' );
$donor_type    = get_query_var( 'donor_type', '' );
$years         = get_year_options();
$types         = get_type_options();


// Generate unique id for aria-controls.
$unique_id = wp_unique_id( 'p-' );

$state = array(
	'donationYears' => $year,
	'endpoints'     => array(
		'thinkTank'    => \Site_Functionality\App\Taxonomies\Think_Tank::TAXONOMY['rest_base'],
		'donor'        => \Site_Functionality\App\Taxonomies\Donor::TAXONOMY['rest_base'],
		'donationYear' => \Site_Functionality\App\Taxonomies\Year::TAXONOMY['rest_base'],
		'donorType'    => \Site_Functionality\App\Taxonomies\Donor_Type::TAXONOMY['rest_base'],
	),
	'years'         => $years,
	'types'         => $types,
	'donationYear'  => $donation_year,
	'donorType'     => $donor_type,
);

if ( $attributes['thinkTank'] ) {
	$state['thinkTank'] = sanitize_text_field( $attributes['thinkTank'] );
}
if ( $donation_year ) {
	$state['donationYear'] = sanitize_text_field( $donation_year );
}
if ( $donor_type ) {
	$state['donorType'] = sanitize_text_field( $donor_type );
}

\wp_interactivity_state(
	'data-tables',
	$state
);

$context = array(
	'thinkTank'    => sanitize_text_field( $attributes['thinkTank'] ),
	'donationYear' => sanitize_text_field( $donation_year ),
	'donorType'    => sanitize_text_field( $donor_type ),
	'years'        => $years,
	'types'        => $types,
);
?>

<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="data-tables"
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
	data-wp-watch="callbacks.log"
>

	<div 
		id="custom-filters" 
		class="wp-block-group data-filters" 
	>


	
		<div>Year (context): <span data-wp-text="context.donationYear"></span></div>
		<div>Year (state): <span data-wp-text="state.donationYear"></span></div>

		<div>Donor Type (context): <span data-wp-text="context.donorType"></span></div>
		<div>Donor Type (state): <span data-wp-text="state.donorType"></span></div>
	</div>
	<!-- /wp:group -->

	<span data-wp-text="context.dataTable"></span>
	 

</div>
