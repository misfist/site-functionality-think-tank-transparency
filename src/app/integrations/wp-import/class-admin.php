<?php
/**
 * WP All Import admin runner.
 *
 * Select imports + order in wp-admin, then run the sequence in the background via WP-Cron,
 * executing WP-CLI: `wp all-import run <ids> --force-run`.
 *
 * @package site-functionality
 */

namespace Site_Functionality\Integrations\WP_Import;

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI and background runner for sequential WP All Import runs.
 */
final class Admin {

	/**
	 * Text domain.
	 *
	 * @var string
	 */
	const TEXTDOMAIN = 'site-functionality';

	/**
	 * Capability required to run imports.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'site-functionality-import-runner';

	/**
	 * admin-post action for running sequence.
	 *
	 * @var string
	 */
	const POST_ACTION_RUN = 'site_functionality_import_runner_run';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION_RUN = 'site_functionality_import_runner_run';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_NAME = '_site_functionality_import_runner_nonce';

	/**
	 * Cron hook.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'site_functionality_import_runner_cron';

	/**
	 * AJAX action name.
	 *
	 * @var string
	 */
	const AJAX_ACTION_STATUS = 'site_functionality_import_runner_status';

	/**
	 * AJAX nonce action.
	 *
	 * @var string
	 */
	const AJAX_NONCE_ACTION = 'site_functionality_import_runner_status';


	/**
	 * Option name for saved sequence.
	 *
	 * Stored as: [ import_id => [ 'enabled' => bool, 'order' => int ] ].
	 *
	 * @var string
	 */
	const OPTION_SEQUENCE = 'site_functionality_import_runner_sequence';

	/**
	 * Option name for status.
	 *
	 * @var string
	 */
	const OPTION_STATUS = 'site_functionality_import_runner_status';

	/**
	 * Option name for last output.
	 *
	 * @var string
	 */
	const OPTION_LAST_OUTPUT = 'site_functionality_import_runner_last_output';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	// public static function register(): void {
	// add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	// add_action( 'admin_post_' . self::POST_ACTION_RUN, array( __CLASS__, 'handle_run_post' ) );
	// add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	// add_action( self::CRON_HOOK, array( __CLASS__, 'run_sequence_cron' ), 10, 1 );

	// add_action( 'wp_ajax_' . self::AJAX_ACTION_STATUS, array( __CLASS__, 'ajax_get_status' ) );
	// add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	// }

	public static function register(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_sequence_cron' ), 10, 1 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
			add_action( 'admin_post_' . self::POST_ACTION_RUN, array( __CLASS__, 'handle_run_post' ) );
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'wp_ajax_' . self::AJAX_ACTION_STATUS, array( __CLASS__, 'ajax_get_status' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}
	}

	/**
	 * Add Tools page.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_management_page(
			esc_html__( 'Import Data', self::TEXTDOMAIN ),
			esc_html__( 'Import Data', self::TEXTDOMAIN ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', self::TEXTDOMAIN ) );
		}
		
		self::maybe_reset_to_idle();

		$imports  = self::get_available_imports();
		$sequence = self::get_saved_sequence();

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Data Import', self::TEXTDOMAIN ); ?></h1>
			<?php self::render_status_panel(); ?>
			<p><?php echo esc_html__( 'Select which imports to run and define the execution order. The sequence runs in the background.', self::TEXTDOMAIN ); ?></p>

			<?php if ( empty( $imports ) ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'No WP All Import imports were found.', self::TEXTDOMAIN ); ?></p>
				</div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::POST_ACTION_RUN ); ?>" />
					<?php wp_nonce_field( self::NONCE_ACTION_RUN, self::NONCE_NAME ); ?>

					<table class="widefat striped" style="max-width: 980px;">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Run', self::TEXTDOMAIN ); ?></th>
								<th><?php echo esc_html__( 'Import', self::TEXTDOMAIN ); ?></th>
								<th><?php echo esc_html__( 'Import ID', self::TEXTDOMAIN ); ?></th>
								<th><?php echo esc_html__( 'Order', self::TEXTDOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $imports as $import ) : ?>
								<?php
								$import_id   = (int) $import['id'];
								$import_name = (string) $import['name'];

								$is_enabled = isset( $sequence[ $import_id ]['enabled'] ) ? (bool) $sequence[ $import_id ]['enabled'] : false;
								$order      = isset( $sequence[ $import_id ]['order'] ) ? (int) $sequence[ $import_id ]['order'] : $import_id;
								?>
								<tr>
									<td>
										<label>
											<input type="checkbox" name="imports[<?php echo esc_attr( (string) $import_id ); ?>][enabled]" value="1" <?php checked( $is_enabled ); ?> />
											<span class="screen-reader-text"><?php echo esc_html__( 'Enable import', self::TEXTDOMAIN ); ?></span>
										</label>
									</td>
									<td><?php echo esc_html( $import_name ); ?></td>
									<td><?php echo esc_html( (string) $import_id ); ?></td>
									<td>
										<input
											type="number"
											name="imports[<?php echo esc_attr( (string) $import_id ); ?>][order]"
											value="<?php echo esc_attr( (string) $order ); ?>"
											min="1"
											step="1"
											style="width: 90px;"
										/>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="description" style="max-width: 980px;">
						<?php echo esc_html__( 'Lower order runs first. Ties are resolved by Import ID.', self::TEXTDOMAIN ); ?>
					</p>

					<?php submit_button( esc_html__( 'Run Selected Imports', self::TEXTDOMAIN ) ); ?>
				</form>

				<?php self::render_last_output(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle admin POST: save selection and schedule background run.
	 *
	 * @return void
	 */
	public static function handle_run_post(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', self::TEXTDOMAIN ) );
		}

		check_admin_referer( self::NONCE_ACTION_RUN, self::NONCE_NAME );

		$imports       = self::get_available_imports();
		$available_ids = self::pluck_import_ids( $imports );

		$posted_imports = isset( $_POST['imports'] ) && is_array( $_POST['imports'] ) ? wp_unslash( $_POST['imports'] ) : array();
		$sequence       = self::normalize_posted_sequence( $posted_imports, $available_ids );

		update_option( self::OPTION_SEQUENCE, $sequence, false );

		/*
		 * IMPORTANT:
		 * Cron event identity is hook + args.
		 * Using a user ID as args is brittle and commonly leads to "not scheduled" checks.
		 * Use a constant args payload instead.
		 */
		$args = array( 'runner' => 1 );

		$status = array(
			'state'      => '',
			'queued_at'  => 0,
			'started_at' => 0,
			'ended_at'   => 0,
			'message'    => '',
			'args'       => $args,
		);

		// Schedule a single background run (avoid duplicates). Args must match.
		if ( ! wp_next_scheduled( self::CRON_HOOK, $args ) ) {
			$scheduled = wp_schedule_single_event( time() + 5, self::CRON_HOOK, $args );

			if ( false === $scheduled ) {
				$status['state']   = 'error';
				$status['message'] = __( 'Failed to schedule cron event.', self::TEXTDOMAIN );
				update_option( self::OPTION_STATUS, $status, false );

				wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
				exit;
			}
		}

		$next = wp_next_scheduled( self::CRON_HOOK, $args );

		if ( false === $next ) {
			$status['state']   = 'error';
			$status['message'] = __( 'No scheduled cron event was found for this run.', self::TEXTDOMAIN );
		} else {
			$status['state']     = 'queued';
			$status['queued_at'] = time();
		}

		update_option( self::OPTION_STATUS, $status, false );

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Cron callback: run the selected imports in order.
	 *
	 * @param array $args Scheduled arguments.
	 * @return void
	 */
	public static function run_sequence_cron( array $args = array() ): void {
		$sequence   = self::get_saved_sequence();
		$import_ids = self::get_ordered_enabled_import_ids( $sequence );

		$status = get_option( self::OPTION_STATUS, array() );
		$status = is_array( $status ) ? $status : array();

		$status['state']      = 'running';
		$status['started_at'] = time();
		$status['ended_at']   = 0;
		$status['message']    = '';
		update_option( self::OPTION_STATUS, $status, false );

		if ( empty( $import_ids ) ) {
			$status['state']    = 'error';
			$status['ended_at'] = time();
			$status['message']  = __( 'No imports selected.', self::TEXTDOMAIN );
			update_option( self::OPTION_STATUS, $status, false );
			return;
		}

		$result = self::run_wp_cli_all_import( $import_ids );

		update_option( self::OPTION_LAST_OUTPUT, $result['output'], false );

		$status['ended_at'] = time();

		if ( true === $result['success'] ) {
			$status['state']   = 'success';
			$status['message'] = __( 'Import sequence completed successfully.', self::TEXTDOMAIN );
		} else {
			$status['state']   = 'error';
			$status['message'] = __( 'Import sequence failed. See output below.', self::TEXTDOMAIN );
		}

		update_option( self::OPTION_STATUS, $status, false );
	}

	/**
	 * Admin notices (only shown on this page).
	 *
	 * @return void
	 */
	public static function admin_notices(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$status = get_option( self::OPTION_STATUS, array() );
		if ( ! is_array( $status ) || empty( $status['state'] ) ) {
			return;
		}

		$state   = (string) $status['state'];
		$message = isset( $status['message'] ) ? (string) $status['message'] : '';

		if ( 'queued' === $state ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'Import sequence queued and will begin shortly.', self::TEXTDOMAIN ) . '</p></div>';
			return;
		}

		if ( 'running' === $state ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Import sequence is running in the background.', self::TEXTDOMAIN ) . '</p></div>';
			return;
		}

		if ( 'success' === $state ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
			return;
		}

		if ( 'error' === $state ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Render the last output panel.
	 *
	 * @return void
	 */
	private static function render_last_output(): void {
		$output = get_option( self::OPTION_LAST_OUTPUT, '' );
		if ( empty( $output ) ) {
			return;
		}

		?>
		<h2><?php echo esc_html__( 'Last Output', self::TEXTDOMAIN ); ?></h2>
		<textarea readonly="readonly" style="width: 100%; max-width: 980px; height: 260px; font-family: monospace;"><?php echo esc_textarea( (string) $output ); ?></textarea>
		<?php
	}

	/**
	 * Get available imports from WP All Import.
	 *
	 * @return array<int,array{id:int,name:string}>
	 */
	private static function get_available_imports(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'pmxi_imports';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT id, friendly_name FROM {$table} ORDER BY id ASC",
			ARRAY_A
		);

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}

		$imports = array();

		foreach ( $rows as $row ) {
			$id            = isset( $row['id'] ) ? (int) $row['id'] : 0;
			$friendly_name = isset( $row['friendly_name'] ) ? (string) $row['friendly_name'] : '';

			if ( $id <= 0 ) {
				continue;
			}

			$imports[] = array(
				'id'   => $id,
				'name' => $friendly_name,
			);
		}

		return $imports;
	}

	/**
	 * Extract import IDs from available imports.
	 *
	 * @param array<int,array{id:int,name:string}> $imports Imports.
	 * @return int[]
	 */
	private static function pluck_import_ids( array $imports ): array {
		$ids = array();

		foreach ( $imports as $import ) {
			if ( isset( $import['id'] ) ) {
				$ids[] = (int) $import['id'];
			}
		}

		return $ids;
	}

	/**
	 * Get saved sequence.
	 *
	 * @return array<int,array{enabled:bool,order:int}>
	 */
	private static function get_saved_sequence(): array {
		$sequence = get_option( self::OPTION_SEQUENCE, array() );

		return is_array( $sequence ) ? $sequence : array();
	}

	/**
	 * Normalize posted selection, restricting to imports that actually exist.
	 *
	 * @param array $posted       Posted imports payload.
	 * @param int[] $available_ids Available import IDs.
	 * @return array<int,array{enabled:bool,order:int}>
	 */
	private static function normalize_posted_sequence( array $posted, array $available_ids ): array {
		$sequence = array();

		foreach ( $posted as $import_id => $row ) {
			$import_id = (int) $import_id;

			if ( $import_id <= 0 ) {
				continue;
			}

			if ( ! in_array( $import_id, $available_ids, true ) ) {
				continue;
			}

			$enabled = is_array( $row ) && isset( $row['enabled'] ) ? true : false;
			$order   = is_array( $row ) && isset( $row['order'] ) ? (int) $row['order'] : 999;

			$sequence[ $import_id ] = array(
				'enabled' => $enabled,
				'order'   => max( 1, $order ),
			);
		}

		return $sequence;
	}

	/**
	 * Get ordered enabled import IDs.
	 *
	 * @param array<int,array{enabled:bool,order:int}> $sequence Sequence.
	 * @return int[]
	 */
	private static function get_ordered_enabled_import_ids( array $sequence ): array {
		$rows = array();

		foreach ( $sequence as $import_id => $row ) {
			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			$rows[] = array(
				'id'    => (int) $import_id,
				'order' => isset( $row['order'] ) ? (int) $row['order'] : 999,
			);
		}

		if ( empty( $rows ) ) {
			return array();
		}

		usort( $rows, array( __CLASS__, 'compare_sequence_rows' ) );

		$ids = array();

		foreach ( $rows as $row ) {
			$ids[] = (int) $row['id'];
		}

		return $ids;
	}

	/**
	 * Compare rows for ordering: order ASC, then id ASC.
	 *
	 * @param array $a Row A.
	 * @param array $b Row B.
	 * @return int
	 */
	public static function compare_sequence_rows( array $a, array $b ): int {
		$a_order = isset( $a['order'] ) ? (int) $a['order'] : 999;
		$b_order = isset( $b['order'] ) ? (int) $b['order'] : 999;

		if ( $a_order === $b_order ) {
			$a_id = isset( $a['id'] ) ? (int) $a['id'] : 0;
			$b_id = isset( $b['id'] ) ? (int) $b['id'] : 0;

			if ( $a_id === $b_id ) {
				return 0;
			}

			return ( $a_id < $b_id ) ? -1 : 1;
		}

		return ( $a_order < $b_order ) ? -1 : 1;
	}

	/**
	 * Execute WP-CLI to run WP All Import sequentially.
	 *
	 * @param int[] $import_ids Import IDs in execution order.
	 * @return array{success:bool,output:string}
	 */
	private static function run_wp_cli_all_import( array $import_ids ): array {
		$ids = implode( ',', array_map( 'intval', $import_ids ) );

		$command  = 'wp';
		$command .= ' --path=' . escapeshellarg( ABSPATH );
		$command .= ' --url=' . escapeshellarg( home_url() );
		$command .= ' all-import run ' . escapeshellarg( $ids );
		$command .= ' --force-run';

		$result = self::run_shell_command( $command );

		return array(
			'success' => 0 === (int) $result['exit_code'],
			'output'  => (string) $result['output'],
		);
	}

	/**
	 * Run a shell command and capture stdout/stderr.
	 *
	 * @param string $command Shell command.
	 * @return array{exit_code:int,output:string}
	 */
	private static function run_shell_command( string $command ): array {
		if ( ! function_exists( 'proc_open' ) ) {
			return array(
				'exit_code' => 1,
				'output'    => __( 'proc_open() is not available on this server.', self::TEXTDOMAIN ),
			);
		}

		$descriptor_spec = array(
			1 => array( 'pipe', 'w' ), // stdout.
			2 => array( 'pipe', 'w' ), // stderr.
		);

		$process = proc_open( $command, $descriptor_spec, $pipes );

		if ( ! is_resource( $process ) ) {
			return array(
				'exit_code' => 1,
				'output'    => __( 'Failed to start the import process.', self::TEXTDOMAIN ),
			);
		}

		$stdout = stream_get_contents( $pipes[1] );
		$stderr = stream_get_contents( $pipes[2] );

		fclose( $pipes[1] );
		fclose( $pipes[2] );

		$exit_code = proc_close( $process );

		$output  = "COMMAND:\n{$command}\n\n";
		$output .= "STDOUT:\n{$stdout}\n\n";
		$output .= "STDERR:\n{$stderr}\n";

		return array(
			'exit_code' => (int) $exit_code,
			'output'    => $output,
		);
	}

	/**
	 * Render current run status.
	 *
	 * @return void
	 */
	private static function render_status_panel(): void {
		$status = self::get_import_status();

		$state      = isset( $status['state'] ) ? (string) $status['state'] : '';
		$message    = isset( $status['message'] ) ? (string) $status['message'] : '';
		$queued_at  = isset( $status['queued_at'] ) ? (int) $status['queued_at'] : 0;
		$started_at = isset( $status['started_at'] ) ? (int) $status['started_at'] : 0;
		$ended_at   = isset( $status['ended_at'] ) ? (int) $status['ended_at'] : 0;

		?>
	<div class="card" style="max-width: 980px;">
		<h2 style="margin-top: 0;"><?php echo esc_html__( 'Run Status', self::TEXTDOMAIN ); ?></h2>

		<p>
			<strong><?php echo esc_html__( 'State:', self::TEXTDOMAIN ); ?></strong>
			<span id="site-functionality-import-runner-state"><?php echo esc_html( $state ? $state : __( 'none', self::TEXTDOMAIN ) ); ?></span>
		</p>

		<?php if ( $message ) : ?>
			<p id="site-functionality-import-runner-message"><?php echo esc_html( $message ); ?></p>
		<?php else : ?>
			<p id="site-functionality-import-runner-message"></p>
		<?php endif; ?>

		<ul style="margin-bottom: 0;">
			<li>
				<strong><?php echo esc_html__( 'Queued:', self::TEXTDOMAIN ); ?></strong>
				<span id="site-functionality-import-runner-queued"><?php echo $queued_at ? esc_html( wp_date( 'Y-m-d H:i:s', $queued_at ) ) : esc_html__( '—', self::TEXTDOMAIN ); ?></span>
			</li>
			<li>
				<strong><?php echo esc_html__( 'Started:', self::TEXTDOMAIN ); ?></strong>
				<span id="site-functionality-import-runner-started"><?php echo $started_at ? esc_html( wp_date( 'Y-m-d H:i:s', $started_at ) ) : esc_html__( '—', self::TEXTDOMAIN ); ?></span>
			</li>
			<li>
				<strong><?php echo esc_html__( 'Ended:', self::TEXTDOMAIN ); ?></strong>
				<span id="site-functionality-import-runner-ended"><?php echo $ended_at ? esc_html( wp_date( 'Y-m-d H:i:s', $ended_at ) ) : esc_html__( '—', self::TEXTDOMAIN ); ?></span>
			</li>
		</ul>
	</div>
		<?php
	}

	/**
	 * AJAX: return current status as JSON.
	 *
	 * @return void
	 */
	public static function ajax_get_status(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Insufficient permissions.', self::TEXTDOMAIN ) ),
				403
			);
		}

		check_ajax_referer( self::AJAX_NONCE_ACTION, 'nonce' );

		$status = self::get_import_status();

		wp_send_json_success(
			array(
				'state'      => isset( $status['state'] ) ? (string) $status['state'] : '',
				'message'    => isset( $status['message'] ) ? (string) $status['message'] : '',
				'queued_at'  => isset( $status['queued_at'] ) ? (int) $status['queued_at'] : 0,
				'started_at' => isset( $status['started_at'] ) ? (int) $status['started_at'] : 0,
				'ended_at'   => isset( $status['ended_at'] ) ? (int) $status['ended_at'] : 0,
			)
		);
	}

	/**
	 * Get the current import runner status.
	 *
	 * Important: This method is for UI state, not for validating scheduling.
	 * Scheduling failures should be recorded in handle_run_post(), not inferred here.
	 *
	 * @return array{state:string,message:string,queued_at:int,started_at:int,ended_at:int,args:array}
	 */
	private static function get_import_status(): array {
		$status = get_option( self::OPTION_STATUS, array() );
		$status = is_array( $status ) ? $status : array();

		$status = wp_parse_args(
			$status,
			array(
				'state'      => '',
				'message'    => '',
				'queued_at'  => 0,
				'started_at' => 0,
				'ended_at'   => 0,
				'args'       => array(),
			)
		);

		$state = (string) $status['state'];

		// If nothing is in progress and nothing is queued, the UI should be idle.
		if ( 0 === (int) $status['started_at'] && 0 === (int) $status['ended_at'] ) {
			$args = is_array( $status['args'] ) ? $status['args'] : array();
			$next = wp_next_scheduled( self::CRON_HOOK, $args );

			/*
			 * If we're not actively scheduled and not running, treat the page as idle
			 * unless we are explicitly in a terminal state (success/error) from a prior run.
			 */
			if ( false === $next && ( '' === $state || 'queued' === $state || 'running' === $state || 'idle' === $state ) ) {
				$status['state']   = 'idle';
				$status['message'] = '';
				return $status;
			}

			// If a cron event exists and we haven't started yet, we are queued.
			if ( false !== $next && 0 === (int) $status['started_at'] ) {
				$status['state'] = 'queued';
				return $status;
			}
		}

		// Started but not ended: running.
		if ( $status['started_at'] > 0 && 0 === (int) $status['ended_at'] ) {
			$status['state'] = 'running';
			return $status;
		}

		// Terminal states (success/error) are returned as stored.
		return $status;
	}

	/**
	 * Reset stale status to idle when nothing is scheduled or running.
	 *
	 * @return void
	 */
	private static function maybe_reset_to_idle(): void {
		$status = get_option( self::OPTION_STATUS, array() );
		$status = is_array( $status ) ? $status : array();

		$state      = isset( $status['state'] ) ? (string) $status['state'] : '';
		$started_at = isset( $status['started_at'] ) ? (int) $status['started_at'] : 0;
		$ended_at   = isset( $status['ended_at'] ) ? (int) $status['ended_at'] : 0;
		$args       = isset( $status['args'] ) && is_array( $status['args'] ) ? $status['args'] : array();

		$next = wp_next_scheduled( self::CRON_HOOK, $args );

		if ( false === $next && 0 === $started_at && 0 === $ended_at && ( 'error' === $state || 'success' === $state ) ) {
			update_option(
				self::OPTION_STATUS,
				array(
					'state'      => 'idle',
					'queued_at'  => 0,
					'started_at' => 0,
					'ended_at'   => 0,
					'message'    => '',
					'args'       => $args,
				),
				false
			);
		}
	}

	/**
	 * Enqueue admin assets for the import runner page.
	 *
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( 'tools_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$handle = 'site-functionality-wp-import-runner';

		wp_enqueue_script(
			$handle,
			plugins_url( 'assets/index.js', __DIR__ ),
			array(),
			'1.0.0',
			true
		);

		wp_localize_script(
			$handle,
			'SiteFunctionalityImportRunner',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION_STATUS,
				'nonce'   => wp_create_nonce( self::AJAX_NONCE_ACTION ),
			)
		);
	}
}
