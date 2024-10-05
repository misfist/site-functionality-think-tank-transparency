<?php
/**
 * Admin Settings
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Admin;

use Site_Functionality\Common\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Settings extends Base {

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	private $option_name = 'site_settings';

	/**
	 * Capability required for changing settings.
	 *
	 * @var string
	 */
	private $capability = 'manage_options';

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
	public function init(): void {
		add_action( 'admin_init', array( $this, 'disable_comments' ) );
		add_action( 'admin_menu', array( $this, 'disable_admin_menu_comments' ) );
		add_action( 'init', array( $this, 'disable_admin_bar_menu_comments' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 5 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the Site Settings page to the Settings menu.
	 *
	 * Uses `add_options_page` to add the page under the Settings menu in the admin dashboard.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_options_page(
			__( 'Site Settings', 'site-functionality' ),
			__( 'Site Settings', 'site-functionality' ),
			$this->capability,
			$this->option_name,
			array( $this, 'render_settings_page' ),
			0
		);
	}

	/**
	 * Registers settings, sections, and fields for the Site Settings page.
	 *
	 * Registers the settings group, adds the settings section, and adds the settings fields.
	 *
	 * @link https://developer.wordpress.org/plugins/settings/custom-settings-page/
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( $this->option_name . '_group', $this->option_name );

		add_settings_section(
			$this->option_name . '_section',
			'',
			null,
			$this->option_name
		);

		add_settings_section(
			$this->option_name . '_content_section',
			__( 'General Content', 'site-functionality' ),
			null,
			$this->option_name
		);

		add_settings_section(
			$this->option_name . '_content_think_tank_section',
			__( 'Think Tank Content', 'site-functionality' ),
			null,
			$this->option_name
		);

		add_settings_section(
			$this->option_name . '_content_donor_section',
			__( 'Donor', 'site-functionality' ),
			null,
			$this->option_name
		);

		add_settings_field(
			'default_year',
			__( 'Default Year', 'site-functionality' ),
			array( $this, 'render_default_year' ),
			$this->option_name,
			$this->option_name . '_section'
		);

		add_settings_field(
			'rows_per_page',
			__( 'Number of Results', 'site-functionality' ),
			array( $this, 'render_rows_per_page' ),
			$this->option_name,
			$this->option_name . '_section'
		);

		add_settings_field(
			'think_tank_box_total',
			__( 'Data Box - Total Text', 'site-functionality' ),
			array( $this, 'render_think_tank_box_total' ),
			$this->option_name,
			$this->option_name . '_content_think_tank_section',
			array(
				'label_for' => 'think_tank_box_total',
				'description'  => __( 'e.g. Minimum funding to date from', 'site-functionality' ),
			)
		);

		add_settings_field(
			'think_tank_box_not_accepted',
			__( 'Data Box - No Donations Accepted Text', 'site-functionality' ),
			array( $this, 'render_think_tank_box_not_accepted' ),
			$this->option_name,
			$this->option_name . '_content_think_tank_section',
			array(
				'label_for' => 'think_tank_box_not_accepted',
				'description'  => __( 'e.g. Did not accept any donations from', 'site-functionality' ),
			)
		);

		add_settings_field(
			'think_tank_all_no_data',
			__( 'No Donation Info Available', 'site-functionality' ),
			array( $this, 'render_think_tank_all_no_data' ),
			$this->option_name,
			$this->option_name . '_content_think_tank_section',
			array(
				'label_for' => 'think_tank_all_no_data',
				'description'  => __( 'e.g. No donation data available from this think tank.', 'site-functionality' ),
			)
		);

		add_settings_field(
			'think_tank_total_text',
			__( 'Total Text', 'site-functionality' ),
			array( $this, 'render_think_tank_total_text' ),
			$this->option_name,
			$this->option_name . '_content_think_tank_section',
			array(
				'label_for' => 'think_tank_total_text',
				'description'  => __( 'e.g. Minimum amount received', 'site-functionality' ),
			)
		);

		add_settings_field(
			'think_tank_no_data_text',
			__( 'No Data Text', 'site-functionality' ),
			array( $this, 'render_think_tank_no_data_text' ),
			$this->option_name,
			$this->option_name . '_content_think_tank_section',
			array(
				'label_for' => 'think_tank_no_data_text',
				'description'  => __( 'e.g. This think tank has not provided data regarding its donations.', 'site-functionality' ),
			)
		);

		add_settings_field(
			'donor_total_text',
			__( 'Total Text', 'site-functionality' ),
			array( $this, 'render_donor_total_text' ),
			$this->option_name,
			$this->option_name . '_content_donor_section',
			array(
				'label_for' => 'donor_total_text',
				'description'  => __( 'e.g. Minimum contributions', 'site-functionality' ),
			)
		);

		add_settings_field(
			'data_note',
			__( 'Data Note', 'site-functionality' ),
			array( $this, 'render_data_note' ),
			$this->option_name,
			$this->option_name . '_content_section'
		);
	}

	/**
	 * Outputs the HTML for the settings page.
	 *
	 * Displays the settings form with fields for saving and updating the settings.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		$content = get_option( $this->option_name . '_options[data_note]', '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
			<?php
			settings_fields( $this->option_name . '_group' );
			do_settings_sections( $this->option_name );
			submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Outputs the HTML for the Data Note field.
	 *
	 * Uses `wp_editor` to display a textarea with the WP Block Editor for entering notes.
	 *
	 * @return void
	 */
	public function render_data_note(): void {
		$options   = get_option( $this->option_name );
		$data_note = isset( $options['data_note'] ) ? $options['data_note'] : '';

		wp_editor(
			$data_note,
			$this->option_name . '_data_note', // Unique ID for the editor
			array(
				'textarea_name' => $this->option_name . '[data_note]', // Name attribute for the textarea
				'textarea_rows' => 8,
				'teeny'         => true,
				'editor_class'  => 'site-functionality-editor-class'
			)
		);
		?>
		<p class="description">
			<?php esc_html_e( 'The data note will appear below every data table on the site.', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the Data Note field.
	 *
	 * @return void
	 */
	public function render_think_tank_box_total(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['think_tank_box_total'] ) ? $options['think_tank_box_total'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[think_tank_box_total]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. Minimum funding to date from', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the field.
	 *
	 * @return void
	 */
	public function render_think_tank_box_no_data(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['think_tank_box_no_data'] ) ? $options['think_tank_box_no_data'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[think_tank_box_no_data]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. No data regarding donations from', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the field.
	 *
	 * @return void
	 */
	public function render_think_tank_box_not_accepted(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['think_tank_box_not_accepted'] ) ? $options['think_tank_box_not_accepted'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[think_tank_box_not_accepted]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. Did not accept any donations from', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the field.
	 *
	 * @return void
	 */
	public function render_think_tank_all_no_data(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['think_tank_all_no_data'] ) ? $options['think_tank_all_no_data'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[think_tank_all_no_data]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. No donation data available from this think tank.', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the field.
	 *
	 * @return void
	 */
	public function render_think_tank_total_text(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['think_tank_total_text'] ) ? $options['think_tank_total_text'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[think_tank_total_text]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. Minimum amount received', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the field.
	 *
	 * @return void
	 */
	public function render_think_tank_no_data_text(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['think_tank_no_data_text'] ) ? $options['think_tank_no_data_text'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[think_tank_no_data_text]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. This think tank has not provided data regarding its donations.', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the field.
	 *
	 * @return void
	 */
	public function render_donor_total_text(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['donor_total_text'] ) ? $options['donor_total_text'] : '';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[donor_total_text]" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'e.g. Minimum contributions', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the Default Year field.
	 *
	 * Displays a select box populated with `donation_year` taxonomy terms, ordered by name in descending order.
	 *
	 * @return void
	 */
	public function render_default_year(): void {
		$options       = get_option( $this->option_name );
		$selected_year = isset( $options['default_year'] ) ? $options['default_year'] : '';

		$terms = get_terms(
			array(
				'taxonomy'   => 'donation_year',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'DESC',
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			echo '<select name="' . esc_attr( $this->option_name ) . '[default_year]">';
			echo '<option value=""' . selected( $selected_year, '', false ) . '>' . esc_html__( '-Select Year-', 'site-functionality' ) . '</option>';
			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->term_id ) . '"' . selected( $selected_year, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>' . __( 'No donation years found.', 'site-functionality' ) . '</p>';
		}
		?>
		<p class="description">
			<?php esc_html_e( 'If a default year is selected, all data tables on the site will display data for that year by default.', 'site-functionality' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the HTML for the Default Year field.
	 *
	 * Displays a select box populated with `donation_year` taxonomy terms, ordered by name in descending order.
	 *
	 * @return void
	 */
	public function render_rows_per_page(): void {
		$options   = get_option( $this->option_name );
		$value = isset( $options['rows_per_page'] ) ? $options['rows_per_page'] : 25;
		?>
		<input type="number" list="rows-per-page" name="<?php echo esc_attr( $this->option_name ); ?>[rows_per_page]" value="<?php echo esc_attr( $value ); ?>"  step="1" min="5" max="100" class="small-text">
		<p class="description">
			<?php esc_html_e( 'The number of rows per page to display.', 'site-functionality' ); ?>
		</p>

		<datalist id="rows-per-page">
			<option value="25"></option>
			<option value="50"></option>
			<option value="75"></option>
			<option value="100"></option>
		</datalist>
		<?php
	}

	/**
	 * Disable comments
	 *
	 * @return void
	 */
	public function disable_comments() : void {
		add_filter( 'comments_open', '\__return_false', 20, 2 );
		add_filter( 'pings_open', '\__return_false', 20, 2 );
		add_filter( 'comments_array', '\__return_empty_array', 10, 2 );
	}

	/**
	 * Remove comments menu admin men
	 *
	 * @return void
	 */
	public function disable_admin_menu_comments() : void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Remove comments menu from admin bar
	 *
	 * @return void
	 */
	public function disable_admin_bar_menu_comments() : void {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}

}
