<?php
/**
 * Helper Functions
 */
namespace Site_Functionality\Integrations\Data_Tables;

/**
 * Get post that matches taxonomy term
 *
 * @param  string $slug
 * @param  string $type
 * @return array $post_id
 */
function get_post_from_term( $slug, $type ) {
	$args = array(
		'post_type'      => $type,
		'posts_per_page' => 1,
		'name'           => $slug,
		'fields'         => 'ids',
	);

	return get_posts( $args );
}

/**
 * Retrieve the most recent donation year term.
 *
 * @return string|false The name of the most recent donation year term, or false if none found.
 */
function get_most_recent_donation_year() {
	$taxonomy = 'donation_year';

	$args = array(
		'taxonomy'   => $taxonomy,
		'orderby'    => 'name',
		'order'      => 'DESC',
		'number'     => 1,
		'hide_empty' => true,
	);

	$terms = get_terms( $args );

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		return $terms[0]->name;
	}

	return false;
}

/**
 * Retrieve default donation year setting
 *
 * @return string The name of the most recent donation year term, or false if none found.
 */
function get_default_donation_year() {
	$taxonomy = 'donation_year';
	$settings = get_option( 'site_settings', '' );
	$year_id  = ( $settings && isset( $settings['default_year'] ) ) ? sanitize_text_field( $settings['default_year'] ) : false;

	if ( ! $year_id ) {
		return $year_id;
	}

	$term = get_term( (int) $year_id, $taxonomy );
	return ( ! empty( $term ) && ! is_wp_error( $term ) ) ? $term->name : false;
}

/**
 * Retrieves all donation_year terms.
 *
 * @return array An array of donation_year term names.
 */
function get_years(): array {
	$taxonomy = 'donation_year';

	$args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'fields'     => 'names',
		'orderby'    => 'name',
		'order'      => 'DESC',
	);

	return get_terms( $args );
}

/**
 * Retrieves all donor_types terms.
 *
 * @return array An array of donor_types term slug => name.
 */
function get_types(): array {
	$taxonomy = 'donor_type';

	$args = array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
	);

	$terms = get_terms( $args );

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$list = wp_list_pluck( $terms, 'name', 'slug' );
		return $list;
	}

	return array();
}

/**
 * Get Donation options array
 *
 * @return array
 */
function get_year_options(): array {
	$list = get_years();
	array_unshift( $list, 'all' );
	return $list;
}

/**
 * Get Type options array
 *
 * @return array
 */
function get_type_options(): array {
	$all = array(
		'all' => __( 'All' ),
	);
	$types = get_types();

	return $all + $types;
}

/**
 * Convert camelCase to dash
 *
 * @param  string $string
 * @return string
 */
function camel_to_dash( $string ) : string {
	return ltrim( strtolower( preg_replace( '/[A-Z]([A-Z](?![a-z]))*/', '-$0', $string ) ), '-' );
}

/**
 * Convert dash to camelCase
 *
 * @param string $string
 * @return string
 */
function dash_to_camel( $string, $separator = '_' ) {
	return lcfirst( str_replace( $separator, '', ucwords( $string, $separator ) ) );
}


/**
 * Retrieves the Transparency Score for a given think tank slug.
 *
 * @param string $think_tank_slug The think tank slug.
 * @return int The Transparency Score.
 */
function get_transparency_score( $think_tank_slug ): int {
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