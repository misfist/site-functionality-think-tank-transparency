<?php
/**
 * Helper Functions
 *
 * @since   1.0.0
 *
 * @package   Site_Functionality
 */

function assign_parent_donor( $value ) {
	$post_type = 'donor';
	$donor_id  = 0;

	/**
	 * Look up donor by name
	 */
	 $args   = array(
		 'post_type'      => $post_type,
		 'fields'         => 'ids',
		 'posts_per_page' => 1,
		 'title'          => $value,
	 );
	 $donors = get_posts( $args );

	 /**
	  * Look up donor by postmeta 'import_id'
	  */
	 $key = 'import_id';

	 $args   = array(
		 'post_type'      => $post_type,
		 'fields'         => 'ids',
		 'posts_per_page' => 1,
		 'meta_query'     => array(
			 array(
				 'key'   => $key,
				 'value' => $value,
			 ),
		 ),
	 );
	 $donors = get_posts( $args );

	 if ( ! empty( $donor ) && ! is_wp_error( $donor ) ) {
		 $donor_id = $donors[0];
	 } else {

	 }
	 return $donor_id;
}

/**
 * Usage: [assign_parent_donor_by_import_id( {parentid[1]} )]
 *
 * @param  string $value
 * @return mixed
 */
function assign_parent_donor_by_import_id( $value ) {
	$post_type = 'donor';
	$donor_id  = 0;

	 /**
	  * Look up donor by postmeta 'import_id'
	  */
	 $key = 'import_id';

	 $args   = array(
		 'post_type'      => $post_type,
		 'fields'         => 'ids',
		 'posts_per_page' => 1,
		 'meta_query'     => array(
			 array(
				 'key'   => $key,
				 'value' => $value,
			 ),
		 ),
	 );
	 $donors = get_posts( $args );

	 if ( ! empty( $donor ) && ! is_wp_error( $donor ) ) {
		 $donor_id = $donors[0];
	 }
	 return $donor_id;
}

/**
 * Usage: [assign_parent_donor_by_title( {topleveldonor[1]} )]
 *
 * @param  [type] $value
 * @return void
 */
function assign_parent_donor_by_title( $value ) {
	$post_type = 'donor';
	$donor_id  = 0;

	/**
	 * Look up donor by name
	 */
	 $args   = array(
		 'post_type'      => $post_type,
		 'fields'         => 'ids',
		 'posts_per_page' => 1,
		 'title'          => $value,
	 );
	 $donors = get_posts( $args );

	 if ( ! empty( $donors ) && ! is_wp_error( $donors ) ) {
		 $donor_id = $donors[0];
	 }

	 return (int) $donor_id;
}