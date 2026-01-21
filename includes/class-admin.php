<?php
/**
 * Admin class.
 *
 * @package Forminator_Field_Widths
 * @since   1.0.0
 */

namespace Forminator_Field_Widths;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Handles admin settings page.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Width Manager instance.
	 *
	 * @since 1.0.0
	 * @var Width_Manager
	 */
	private $width_manager;

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options;

	/**
	 * AJAX Handler instance.
	 *
	 * @since 1.0.0
	 * @var Ajax_Handler
	 */
	private $ajax_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Width_Manager $width_manager Width Manager instance.
	 * @param array         $options       Plugin options.
	 */
	public function __construct( Width_Manager $width_manager, array $options ) {
		$this->width_manager = $width_manager;
		$this->options       = $options;
		$this->ajax_handler  = new Ajax_Handler( $width_manager, $options );

		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 99 );
	}

	/**
	 * Add settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			'forminator',
			__( 'Field Widths', 'forminator-field-widths' ),
			__( 'Field Widths', 'forminator-field-widths' ),
			'manage_options',
			'forminator-field-widths',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'forminator-field-widths' ) );
		}

		include FFW_DIR . 'admin/views/settings-page.php';
	}
}
