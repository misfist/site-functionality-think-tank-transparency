<?php
/**
 * WP-CLI utilities
 *
 * @package site-functionality
 */
namespace Site_Functionality\Integrations\CLI;

use \WP_CLI as WP_CLI;
use function \WP_CLI\Utils\make_progress_bar;
use function \WP_CLI\Utils\get_flag_value;

/**
 * Typed settings.
 */
class Commands {
    /**
     * Assigns parent ID to Donor posts.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Whether or not to implement or just test
     *
     * ## EXAMPLES
     *
     * wp custom assign-donor-parents --dry-run
	 *
	 * @param string[] $args Positional arguments.
	 * @param string[] $assoc_args Associative arguments.
	 *
	 * @return void
     */
	public function assign_donor_parents( $args = [], $assoc_args = [] ) {
		$post_type = 'donor';
        $dry_run   = get_flag_value( $assoc_args, 'dry-run' );

		/**
		 * 1. Get Donor posts that have `import_parent_id` meta
		 */
		$donors = $this->find_posts();

		if ( ! empty( $donors ) && ! is_wp_error( $donors ) ) {
			/**
			 * 2. For each Donor post found, get value of `import_parent_id`
			 */
            $count = count( $donors );
            $updated = 0;

            WP_CLI::log( sprintf( '%s Donor posts containing `import_parent_id` found.', $count ) );

            $progress = make_progress_bar( 'Add Parents', $count );
			foreach ( $donors as $child_post_id ) {
                $existing_parent = get_post_field( 'post_parent', (int) $child_post_id );

                if( $existing_parent ) {
                    WP_CLI::warning( sprintf( 'Post (%s) not updated, it already has a post_parent (%s). ', $child_post_id, $existing_parent ) );
                } else {

                    				/**
				 * 2.1. Find Donor post that have `import_id` value = to `import_parent_id`
				 */
				$parent_post_id = $this->find_donor_post( $child_post_id );

				if ( $parent_post_id ) {

					/**
					 * 2.2. Assign post->ID of found post to current Donor post
					 */
                    if( $dry_run ) {

                        // WP_CLI::log( sprintf( 'DRY-RUN MODE: Post parent (%s) would be added for post (%s)', $parent_post_id, $child_post_id ) );

                    } else {

                        $post_data = array(
                            'ID'          => (int) $child_post_id,
                            'post_parent' => (int) $parent_post_id,
                            'meta_input'  => array(
                                'wp_parent_id' => (int) $parent_post_id,
                            ),
                        );
    
                        $updated_post_id = wp_update_post( $post_data );
    
                        if( $updated_post_id ) {

                            $updated++;
    
                            WP_CLI::success( sprintf( 'Post parent (%s) added for post (%s)', $parent_post_id, $updated_post_id ) );
    
                        } else {

                            WP_CLI::warning( sprintf( 'Post parent (%s) was found, but wasn\'t added for post (%s)', $parent_post_id, $child_post_id ) );

                        }

                    }

				} else {

                    WP_CLI::warning( sprintf( 'No parent was added for post (%s)', $child_post_id ) );

				}

                }

                $progress->tick();
			}

            $progress->finish();

            if( ! $dry_run ) {
                WP_CLI::success( sprintf( '%s were updated.', $updated ) );
            }

		} else {

			WP_CLI::error( __( 'No posts found.', 'site-functionality' ) );

		}

	}

	/**
	 * Get Posts
	 *
	 * @return array \WP_Post
	 */
	public function find_posts() : array {
		$post_type = 'donor';

		$meta_query = array(
			array(
				'key'     => 'import_parent_id',
				'compare' => 'EXISTS',
			),
		);

		$args = array(
			'post_type'      => $post_type,
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
		);

		$query = new \WP_Query( $args );

		return $query->get_posts();
	}

	/**
	 * Get Donor Post
	 *
	 * @param  int $child_post_id
	 * @return mixed int $post->ID || false
	 */
	public function find_donor_post( $child_post_id ) {
		$meta_key  = 'import_parent_id';
		$parent_id = get_post_meta( $child_post_id, $meta_key, true );

		$post_type = 'donor';

		$meta_query = array(
			array(
				'key'     => 'import_parent_id',
				'value'   => $parent_id,
				'compare' => '=',
			),
		);

		$args = array(
			'post_type'      => $post_type,
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_query'     => $meta_query,
		);

		$query = new \WP_Query( $args );

		$posts = $query->get_posts();

		if ( $posts ) {
			return $posts[0];
		} else {
			return false;
		}
	}

}

/**
 * Assign Donor parents
 *
 * @return void
 */
function assign_donor_parents( $args, $assoc_args ) : void {
    $assign_donor_parents = new Commands();
    $results = $assign_donor_parents->assign_donor_parents( $args, $assoc_args );
}
WP_CLI::add_command( 'custom assign-donor-parents', __NAMESPACE__ . '\assign_donor_parents' );
