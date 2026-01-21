<?php
/**
 * Main plugin class.
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
 * Class Plugin
 *
 * Main plugin class that initializes all components using singleton pattern
 * and dependency injection for testability.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin instance.
	 *
	 * @since 1.0.0
	 * @var Admin|null
	 */
	private $admin = null;

	/**
	 * Width Manager instance.
	 *
	 * @since 1.0.0
	 * @var Width_Manager|null
	 */
	private $width_manager = null;

	/**
	 * Frontend instance.
	 *
	 * @since 1.0.0
	 * @var Frontend|null
	 */
	private $frontend = null;

	/**
	 * Plugin Updater instance.
	 *
	 * @since 1.0.0
	 * @var Plugin_Updater|null
	 */
	private $plugin_updater = null;

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options = array();

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_options();
		$this->load_dependencies();
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @since 1.0.0
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Load plugin options with defaults.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_options() {
		$defaults = array(
			'enable_responsive'  => true,
			'mobile_breakpoint'  => 768,
			'mobile_full_width'  => true,
		);

		$saved_options = get_option( 'ffw_options', array() );
		$this->options = wp_parse_args( $saved_options, $defaults );
	}

	/**
	 * Load required dependencies.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies() {
		// Core classes.
		require_once FFW_DIR . 'includes/class-width-manager.php';
		require_once FFW_DIR . 'includes/class-frontend.php';
		require_once FFW_DIR . 'includes/class-css-generator.php';
		require_once FFW_DIR . 'includes/class-plugin-updater.php';

		// Admin classes (only in admin).
		if ( is_admin() ) {
			require_once FFW_DIR . 'includes/class-admin.php';
			require_once FFW_DIR . 'includes/class-ajax-handler.php';
		}
	}

	/**
	 * Initialize components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_components() {
		// Initialize Width Manager.
		$this->width_manager = new Width_Manager( $this->options );

		// Initialize Frontend.
		$this->frontend = new Frontend( $this->width_manager, $this->options );

		// Initialize Admin (only in admin context).
		if ( is_admin() ) {
			$this->admin = new Admin( $this->width_manager, $this->options );
		}

		// Initialize Plugin Updater for auto-updates from GitHub.
		$this->plugin_updater = new Plugin_Updater(
			FFW_FILE,
			FFW_VERSION,
			array(
				'github_repo' => 'FrancoTaaber/forminator-field-widths',
			)
		);
	}

	/**
	 * Register global hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooks() {
		// Handle activation redirect.
		add_action( 'admin_init', array( $this, 'activation_redirect' ) );

		// Register REST API endpoints.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Redirect to settings page after activation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activation_redirect() {
		if ( get_transient( 'ffw_activation_redirect' ) ) {
			delete_transient( 'ffw_activation_redirect' );

			// Only redirect if not network activation or bulk activation.
			if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=forminator-field-widths' ) );
				exit;
			}
		}
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'forminator-field-widths/v1',
			'/forms/(?P<form_id>\d+)/widths',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_form_widths' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
					'args'                => array(
						'form_id' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param );
							},
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'rest_save_form_widths' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
					'args'                => array(
						'form_id' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return is_numeric( $param );
							},
						),
					),
				),
			)
		);

		register_rest_route(
			'forminator-field-widths/v1',
			'/presets',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'rest_get_presets' ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
				),
			)
		);
	}

	/**
	 * Check REST API permissions.
	 *
	 * @since 1.0.0
	 * @return bool True if user can manage options.
	 */
	public function rest_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * REST API: Get form widths.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_get_form_widths( $request ) {
		$form_id = absint( $request->get_param( 'form_id' ) );
		$widths  = $this->width_manager->get_form_widths( $form_id );

		return rest_ensure_response( $widths );
	}

	/**
	 * REST API: Save form widths.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_save_form_widths( $request ) {
		$form_id = absint( $request->get_param( 'form_id' ) );
		$widths  = $request->get_json_params();

		$result = $this->width_manager->save_form_widths( $form_id, $widths );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Field widths saved successfully.', 'forminator-field-widths' ),
			)
		);
	}

	/**
	 * REST API: Get width presets.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_get_presets( $request ) {
		$presets = $this->width_manager->get_presets();
		return rest_ensure_response( $presets );
	}

	/**
	 * Get plugin options.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Get a specific option value.
	 *
	 * @since 1.0.0
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if option not found.
	 * @return mixed
	 */
	public function get_option( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/**
	 * Get the Width Manager instance.
	 *
	 * @since 1.0.0
	 * @return Width_Manager
	 */
	public function get_width_manager() {
		return $this->width_manager;
	}

	/**
	 * Get the Frontend instance.
	 *
	 * @since 1.0.0
	 * @return Frontend
	 */
	public function get_frontend() {
		return $this->frontend;
	}

	/**
	 * Get the Admin instance.
	 *
	 * @since 1.0.0
	 * @return Admin|null
	 */
	public function get_admin() {
		return $this->admin;
	}
}
