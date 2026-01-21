<?php
/**
 * Plugin Name: Forminator Field Widths
 * Plugin URI: https://github.com/FrancoTaaber/forminator-field-widths
 * Description: Add visual field width controls to Forminator forms. Easily customize field widths without writing CSS code.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Franco Taaber
 * Author URI: https://francotaaber.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: forminator-field-widths
 * Domain Path: /languages
 *
 * @package Forminator_Field_Widths
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'FFW_VERSION', '1.0.0' );

// Plugin file path.
define( 'FFW_FILE', __FILE__ );

// Plugin directory path.
define( 'FFW_DIR', plugin_dir_path( __FILE__ ) );

// Plugin directory URL.
define( 'FFW_URL', plugin_dir_url( __FILE__ ) );

// Minimum Forminator version required.
define( 'FFW_MIN_FORMINATOR', '1.20.0' );

// Minimum PHP version required.
define( 'FFW_MIN_PHP', '7.4' );

/**
 * Check if PHP version meets minimum requirements.
 *
 * @since 1.0.0
 * @return bool True if PHP version is adequate.
 */
function ffw_php_version_check() {
	return version_compare( PHP_VERSION, FFW_MIN_PHP, '>=' );
}

/**
 * Check if Forminator is active and meets minimum version.
 *
 * @since 1.0.0
 * @return bool True if Forminator is active and version is adequate.
 */
function ffw_forminator_check() {
	if ( ! class_exists( 'Forminator' ) ) {
		return false;
	}

	if ( ! defined( 'FORMINATOR_VERSION' ) ) {
		return false;
	}

	return version_compare( FORMINATOR_VERSION, FFW_MIN_FORMINATOR, '>=' );
}

/**
 * Display admin notice if PHP version is too low.
 *
 * @since 1.0.0
 * @return void
 */
function ffw_php_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'Forminator Field Widths requires PHP %1$s or higher. Your current PHP version is %2$s. Please upgrade PHP to use this plugin.', 'forminator-field-widths' ),
				esc_html( FFW_MIN_PHP ),
				esc_html( PHP_VERSION )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Display admin notice if Forminator is not active or version is too low.
 *
 * @since 1.0.0
 * @return void
 */
function ffw_forminator_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			if ( ! class_exists( 'Forminator' ) ) {
				esc_html_e( 'Forminator Field Widths requires Forminator plugin to be installed and activated.', 'forminator-field-widths' );
			} else {
				printf(
					/* translators: 1: Required Forminator version, 2: Current Forminator version */
					esc_html__( 'Forminator Field Widths requires Forminator %1$s or higher. Your current version is %2$s. Please update Forminator.', 'forminator-field-widths' ),
					esc_html( FFW_MIN_FORMINATOR ),
					esc_html( FORMINATOR_VERSION )
				);
			}
			?>
		</p>
	</div>
	<?php
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 * @return void
 */
function ffw_init() {
	// Check PHP version.
	if ( ! ffw_php_version_check() ) {
		add_action( 'admin_notices', 'ffw_php_notice' );
		return;
	}

	// Check Forminator.
	if ( ! ffw_forminator_check() ) {
		add_action( 'admin_notices', 'ffw_forminator_notice' );
		return;
	}

	// Load plugin text domain.
	load_plugin_textdomain(
		'forminator-field-widths',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Load the main plugin class.
	require_once FFW_DIR . 'includes/class-plugin.php';

	// Initialize the plugin.
	Forminator_Field_Widths\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'ffw_init', 20 );

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 * @return void
 */
function ffw_activate() {
	// Check PHP version.
	if ( ! ffw_php_version_check() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			sprintf(
				/* translators: %s: Required PHP version */
				esc_html__( 'Forminator Field Widths requires PHP %s or higher.', 'forminator-field-widths' ),
				esc_html( FFW_MIN_PHP )
			)
		);
	}

	// Set default options.
	$default_options = array(
		'enable_responsive'  => true,
		'mobile_breakpoint'  => 768,
		'mobile_full_width'  => true,
	);

	// Only set defaults if not already set.
	if ( false === get_option( 'ffw_options' ) ) {
		add_option( 'ffw_options', $default_options );
	}

	// Create transient for activation redirect.
	set_transient( 'ffw_activation_redirect', true, 30 );
}
register_activation_hook( __FILE__, 'ffw_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 * @return void
 */
function ffw_deactivate() {
	// Clear cached CSS.
	delete_transient( 'ffw_cached_css' );

	// Clear any form-specific caches.
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'%_transient_ffw_form_css_%'
		)
	);
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'%_transient_timeout_ffw_form_css_%'
		)
	);
}
register_deactivation_hook( __FILE__, 'ffw_deactivate' );

/**
 * Add settings link on plugins page.
 *
 * @since 1.0.0
 * @param array $links Plugin action links.
 * @return array Modified links.
 */
function ffw_plugin_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'admin.php?page=forminator-field-widths' ),
		esc_html__( 'Settings', 'forminator-field-widths' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ffw_plugin_links' );
