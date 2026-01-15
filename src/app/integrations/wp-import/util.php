<?php
<?php
/**
 * Get the hierarchy of a taxonomy term as a string.
 *
 * @param string $term_name The name of the term.
 * @param string $taxonomy  The taxonomy name. Default is 'donor'.
 * @return string The term hierarchy as a formatted string, or an empty string if the term does not exist.
 */
function get_term_hierarchy( string $term_name, string $taxonomy = 'donor', string $separator = '|' ): string {
	$term = get_term_by( 'name', $term_name, $taxonomy );

	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$hierarchy = get_term_parents_list(
		$term->term_id,
		$taxonomy,
		array(
			'format'    => 'name',
			'separator' => '|',
			'link'      => false,
			'inclusive' => true,
		)
	);

	return trim( $hierarchy );
}

/**
 * Get the parent term name of a taxonomy term.
 * 
 * @usage [get_donor_name_from_name({donor[1]})]
 *
 * @param string $term_name The name of the term.
 * @param string $taxonomy  The taxonomy name. Default is 'donor'.
 * @return string The parent term name, or an empty string if the term does not exist.
 */
function get_donor_name_from_name( string $donor_name ): ?string {
	$donor = get_donor_from_name( $donor_name );

	return ( $donor ) ? $donor->name : null;
}

/**
 * Get the parent term ID of a taxonomy term.
 * 
 * @usage [get_donor_id_from_name({donor[1]})]
 *
 * @param string $term_name The name of the term.
 * @param string $taxonomy  The taxonomy name. Default is 'donor'.
 * @return int The parent term ID, or null if the term does not exist.
 */
function get_donor_id_from_name( string $donor_name ): ?int {
	$donor = get_donor_from_name( $donor_name );

    return ( $donor ) ? $donor->term_id : null;
}

/**
 * Get the donor term object from its name.
 * 
 * @usage [get_donor_from_name({donor[1]})]
 *
 * @param string $donor_name The name of the donor term.
 * @return object|null The term object, or null if the term does not exist.
 */
function get_donor_from_name( string $donor_name ): ?object {
	$taxonomy = 'donor';
	$term = get_term_by( 'name', $donor_name, $taxonomy );

	return ( ! empty( $term ) && ! is_wp_error( $term ) ) ? $term : null;
}

/**
 * Get the parent ID of a donor term from its name.
 * 
 * @usage [get_donor_parent_id_from_name({donor[1]})]
 *
 * @param string $donor_name The name of the donor term.
 * @return int|null The parent term ID, or null if the term does not exist.
 */
function get_donor_parent_id_from_name( string $donor_name ): ?int {
	$parent = get_donor_parent_from_name( $donor_name );
	
	return ( ! empty( $parent ) && ! is_wp_error( $parent ) ) ? $parent->term_id : null;
}

/**
 * Get the parent name of a donor term from its name.
 * 
 * @usage [get_donor_parent_name_from_name({donor[1]})]
 *
 * @param string $donor_name The name of the donor term.
 * @return string|null The parent term name, or null if the term does not exist.
 */
function get_donor_parent_name_from_name( string $donor_name ): ?string {
	$parent = get_donor_parent_from_name( $donor_name );
	
	return ( ! empty( $parent ) && ! is_wp_error( $parent ) ) ? $parent->name : null;
}

/**
 * Get the parent term object of a donor term from its name.
 * 
 * @usage [get_donor_parent_from_name({donor[1]})]
 *
 * @param string $donor_name The name of the donor term.
 * @return object|null The parent term object, or null if the term does not exist.
 */
function get_donor_parent_from_name( string $donor_name ): ?object {
	$taxonomy = 'donor';
	$term = get_term_by( 'name', $donor_name, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}
	$parent = get_term( $term->parent, $taxonomy );
	if ( ! $parent || is_wp_error( $parent ) ) {
		return null;
	}

    return $parent;
}

/**
 * Get the donor parent terms by donor name
 * 
 * @usage [get_donor_parent_terms_from_name({donor[1]},'donor_type')]
 *
 * @param string $donor_name The name of the donor term.
 * @param string $taxonomy
 * @return int|null The parent term ID, or null if the term does not exist.
 */
function get_donor_parent_terms_from_name( string $donor_name, string $taxonomy = 'donor_type' ): ?array {
	$parent = get_donor_parent_from_name( $donor_name );
	
	if( empty( $parent ) && is_wp_error( $parent ) ) {
		return null;
	}
	
	$terms = get_term_by( 'name', $donor_name, $taxonomy );
		
	return ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms : null;
}

/**
 * Get donor terms from a name.
 * 
 * @usage [get_donor_terms_from_name({donor[1]})]
 *
 * @param string $donor_name The name of the donor term.
 * @return string The parent term ID, or null if the term does not exist.
 */
function get_donor_terms_from_name( string $donor_name ): string {
    $donor_name = trim( wp_strip_all_tags( (string) $donor_name ) );
	$taxonomy = 'donor';
	$term = get_term_by( 'name', $donor_name, $taxonomy );

	if( ! $term || is_wp_error( $term ) ) {
		return '';
	}
	
	$terms = array( $term->term_id );

	if( $term->parent ) {
        $parent = get_term( (int) $term->parent, $taxonomy );

        if ( $parent && ! is_wp_error( $parent ) ) {
            array_unshift( $terms, (int) $parent->term_id );
        }
	}

	error_log( sprintf( 'Donor terms assigned to post: %s', implode( ', ', $terms ) ) );

	return implode( ',', $terms );
}


/**
 * Get donor term hierarchy as a comma-separated string.
 *
 * Intended for WP All Import "PHP Function" fields.
 *
 * Examples:
 * - Parent exists: "Parent Term, Donor Term"
 * - No parent: "Donor Term"
 *
 * @param string $donor_name Donor term name from import data.
 * @return string Comma-separated hierarchy, or empty string if not resolvable.
 */
function site_functionality_get_donor_term_hierarchy( $donor_name ) {
	$donor_name = trim( wp_strip_all_tags( (string) $donor_name ) );

	if ( '' === $donor_name ) {
		return '';
	}

	$taxonomy = 'donor';

	$donor = get_term_by( 'name', $donor_name, $taxonomy );

	if ( ! $donor || is_wp_error( $donor ) ) {
		return '';
	}

	if ( ! empty( $donor->parent ) ) {
		$parent = get_term( (int) $donor->parent, $taxonomy );

		if ( $parent && ! is_wp_error( $parent ) ) {
			if ( $parent->name !== $donor->name ) {
				return $parent->name . ', ' . $donor->name;
			}
		}
	}

	return $donor->name;
}