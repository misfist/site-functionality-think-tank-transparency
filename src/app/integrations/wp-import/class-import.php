<?php
/**
 * WP Import Actions
 *
 * @package site-functionality
 */
namespace Site_Functionality\Integrations\WP_Import;

use Site_Functionality\Common\Abstracts\Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Import extends Base {

    /**
     * Single instance of the class.
     *
     * @var Import|null
     */
    private static ?Import $instance = null;

    /**
     * Get the single instance of the class.
     *
     * @param array $settings Optional settings.
     * @return Import
     */
    public static function get_instance( array $settings = array() ): Import {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self( $settings );
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.6
     */
    public function __construct( $settings = array() ) {
        parent::__construct( $settings );
        $this->data['action'] = 'run_imports_action';
        $this->data['nonce'] = 'run_imports_nonce';
        $this->data['import_ids'] = array();
        $this->init();
    }

    /**
     * Initizalize the class.
     *
     * @return void
     */
    public function init(): void {
        add_action( 'admin_menu', array( $this, 'register_page' ) );
        add_action( 'wp_ajax_run_imports', array( $this, 'ajax_run_imports' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register a custom Tools menu page for triggering imports.
     */
    public function register_page(): void {
        add_management_page(
            __( 'Run Imports', 'site-functionality' ),
            __( 'Run Imports', 'site-functionality' ),
            'manage_options',
            'run-imports',
            array( $this, 'render_page' )
        );
    }

    /**
     * Render the custom Tools page with the form and checkboxes.
     */
    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $all_imports = $this->get_all_imports();

        ?>
        <div class="wrap">
            <h1><?php echo __( 'Run Imports', 'site-functionality' ); ?></h1>
            <form id="run-imports-form">
                <?php wp_nonce_field( $this->data['action'], $this->data['nonce'] ); ?>

                <p><?php echo __( 'Select the imports you want to run:', 'site-functionality' ); ?></p>

                <?php if ( ! empty( $all_imports ) ) : ?>
                    <ul>
                        <?php foreach ( $all_imports as $import ) : ?>
                            <li>
                                <label>
                                    <input type="checkbox" name="import_ids[]" value="<?php echo esc_attr( $import['id'] ); ?>" checked />
                                    <?php echo esc_html( $import['name'] ); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p><?php echo __( 'No imports found.', 'site-functionality' ); ?></p>
                <?php endif; ?>

                <p>
                    <button type="button" id="run-imports" class="button button-primary">
                        <?php echo __( 'Run Selected Imports', 'site-functionality' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle AJAX requests to run imports.
     */
    public function ajax_run_imports(): void {
        check_ajax_referer( $this->data['nonce'], 'security' );

        $import_ids = isset( $_POST['import_ids'] ) ? array_map( 'absint', $_POST['import_ids'] ) : array();

        if ( empty( $import_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No imports selected.', 'site-functionality' ) ) );
        }

        $results = $this->run_imports( $import_ids );
        wp_send_json_success( array( 'results' => $results ) );
    }

    /**
     * Get all WP All Import imports.
     *
     * @return array Array of imports with ID and name.
     */
    public function get_all_imports(): array {
        if ( ! class_exists( 'PMXI_Import_List' ) ) {
            return array();
        }

        $import_list = new \PMXI_Import_List();
        $imports     = $import_list->getBy()->convertRecords();

        $import_data = array();
        foreach ( $imports as $import ) {
            $import_data[] = array(
                'id'   => $import['id'],
                'name' => $import['friendly_name'],
            );
        }

        return $import_data;
    }

    /**
     * Run multiple imports sequentially.
     *
     * @param array $import_ids An array of import IDs to run.
     * @return array An array with import IDs as keys and boolean results (true = success, false = failure).
     */
    public function run_imports( array $import_ids ): array {
        $results = array();

        if ( ! class_exists( 'PMXI_Import_Record' ) ) {
            return $results;
        }

        $import_record = new \PMXI_Import_Record();

        foreach ( $import_ids as $import_id ) {
            $import_id = absint( $import_id );

            if ( $import_id ) {
                $import = $import_record->getById( $import_id );

                if ( $import->isEmpty() ) {
                    $results[ $import_id ] = false;
                } else {
                    $this->delete_import_posts( $import_id );

                    try {
                        $import->execute();
                        $results[ $import_id ] = true;
                    } catch ( \Exception $e ) {
                        $results[ $import_id ] = false;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Delete posts associated with a specific WP All Import ID.
     *
     * @param int $import_id The ID of the WP All Import record.
     * @return int The number of posts deleted.
     */
    public function delete_import_posts( int $import_id ): int {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}pmxi_posts WHERE import_id = %d",
            $import_id
        );
        $post_ids = $wpdb->get_col( $query );

        if ( empty( $post_ids ) ) {
            return 0;
        }

        $deleted_count = 0;
        foreach ( $post_ids as $post_id ) {
            if ( wp_delete_post( $post_id, true ) ) {
                $deleted_count++;
            }
        }

        return $deleted_count;
    }

        /**
     * Enqueue scripts for AJAX handling.
     */
    public function enqueue_scripts(): void {
        wp_enqueue_script(
            'site-functionality-imports',
            plugin_dir_url( __FILE__ ) . 'assets/imports.js',
            array(),
            SITE_FUNCTIONALITY_VERSION,
            true
        );
    
        wp_localize_script(
            'site-functionality-imports',
            'wpImports',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( $this->data['nonce'] ),
                'action'   => $this->data['action'],
            )
        );
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance.
     */
    private function __wakeup() {}

}
