<?php
/**
 * Uninstall script for Forminator Field Widths plugin.
 *
 * This file runs when the plugin is deleted from WordPress admin.
 * It cleans up all plugin data including options and transients.
 *
 * @package Forminator_Field_Widths
 * @since   1.0.0
 */

// Exit if not called from WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up all plugin data.
 *
 * @since 1.0.0
 */
function ffw_uninstall_cleanup() {
	global $wpdb;

	// Check if we should remove data (could be a setting in the future).
	$remove_data = true;

	/**
	 * Filter whether to remove all plugin data on uninstall.
	 *
	 * @since 1.0.0
	 * @param bool $remove_data Whether to remove data. Default true.
	 */
	$remove_data = apply_filters( 'ffw_uninstall_remove_data', $remove_data );

	if ( ! $remove_data ) {
		return;
	}

	// Delete main plugin options.
	delete_option( 'ffw_options' );

	// Delete all form-specific width settings.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'ffw_form_widths_%'
		)
	);

	// Delete all transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'%_transient_ffw_%'
		)
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'%_transient_timeout_ffw_%'
		)
	);

	// Delete update checker transients.
	delete_transient( 'ffw_update_check' );
	delete_site_transient( 'ffw_update_check' );

	// Clean up any user meta if applicable.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
			'ffw_%'
		)
	);

	// For multisite, clean up network-wide.
	if ( is_multisite() ) {
		// Delete network options.
		delete_site_option( 'ffw_options' );

		// Get all sites.
		$sites = get_sites( array( 'fields' => 'ids' ) );

		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );

			// Delete site-specific options.
			delete_option( 'ffw_options' );

			// Delete form-specific settings for this site.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					'ffw_form_widths_%'
				)
			);

			// Delete transients for this site.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					'%_transient_ffw_%'
				)
			);

			restore_current_blog();
		}
	}

	// Clear any object cache.
	wp_cache_flush();
}

// Run cleanup.
ffw_uninstall_cleanup();
