<?php
/**
 * Width Manager class.
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
 * Class Width_Manager
 *
 * Manages field width configurations for Forminator forms.
 *
 * @since 1.0.0
 */
class Width_Manager {

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options;

	/**
	 * Option prefix for form widths.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_PREFIX = 'ffw_form_widths_';

	/**
	 * Default width presets.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $default_presets = array(
		'full'         => array(
			'label' => 'Full Width',
			'value' => 100,
			'icon'  => 'full',
		),
		'half'         => array(
			'label' => 'Half Width',
			'value' => 50,
			'icon'  => 'half',
		),
		'third'        => array(
			'label' => 'One Third',
			'value' => 33.333,
			'icon'  => 'third',
		),
		'two-thirds'   => array(
			'label' => 'Two Thirds',
			'value' => 66.666,
			'icon'  => 'two-thirds',
		),
		'quarter'      => array(
			'label' => 'Quarter',
			'value' => 25,
			'icon'  => 'quarter',
		),
		'three-quarter' => array(
			'label' => 'Three Quarters',
			'value' => 75,
			'icon'  => 'three-quarter',
		),
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param array $options Plugin options.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Get field widths for a specific form.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return array Field widths configuration.
	 */
	public function get_form_widths( $form_id ) {
		$form_id = absint( $form_id );
		$widths  = get_option( self::OPTION_PREFIX . $form_id, array() );

		return $this->sanitize_widths( $widths );
	}

	/**
	 * Save field widths for a specific form.
	 *
	 * @since 1.0.0
	 * @param int   $form_id Form ID.
	 * @param array $widths  Field widths configuration.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function save_form_widths( $form_id, array $widths ) {
		$form_id = absint( $form_id );

		// Validate form exists.
		if ( ! $this->form_exists( $form_id ) ) {
			return new \WP_Error(
				'invalid_form',
				__( 'The specified form does not exist.', 'forminator-field-widths' )
			);
		}

		// Sanitize widths.
		$sanitized_widths = $this->sanitize_widths( $widths );

		// Save to database.
		$result = update_option( self::OPTION_PREFIX . $form_id, $sanitized_widths, false );

		// Clear cached CSS for this form.
		$this->clear_form_cache( $form_id );

		/**
		 * Fires after form widths are saved.
		 *
		 * @since 1.0.0
		 * @param int   $form_id           Form ID.
		 * @param array $sanitized_widths  Saved widths configuration.
		 */
		do_action( 'ffw_after_save_form_widths', $form_id, $sanitized_widths );

		return true;
	}

	/**
	 * Delete field widths for a specific form.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return bool True on success.
	 */
	public function delete_form_widths( $form_id ) {
		$form_id = absint( $form_id );

		delete_option( self::OPTION_PREFIX . $form_id );
		$this->clear_form_cache( $form_id );

		return true;
	}

	/**
	 * Get width configuration for a specific field.
	 *
	 * @since 1.0.0
	 * @param int    $form_id  Form ID.
	 * @param string $field_id Field ID (element_id).
	 * @return array|null Field width config or null if not set.
	 */
	public function get_field_width( $form_id, $field_id ) {
		$widths = $this->get_form_widths( $form_id );

		if ( isset( $widths['fields'][ $field_id ] ) ) {
			return $widths['fields'][ $field_id ];
		}

		return null;
	}

	/**
	 * Set width for a specific field.
	 *
	 * @since 1.0.0
	 * @param int    $form_id  Form ID.
	 * @param string $field_id Field ID.
	 * @param array  $config   Width configuration.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function set_field_width( $form_id, $field_id, array $config ) {
		$widths = $this->get_form_widths( $form_id );

		if ( ! isset( $widths['fields'] ) ) {
			$widths['fields'] = array();
		}

		$widths['fields'][ sanitize_text_field( $field_id ) ] = $this->sanitize_field_config( $config );

		return $this->save_form_widths( $form_id, $widths );
	}

	/**
	 * Get width presets.
	 *
	 * @since 1.0.0
	 * @return array Width presets.
	 */
	public function get_presets() {
		/**
		 * Filter the available width presets.
		 *
		 * @since 1.0.0
		 * @param array $presets Default presets.
		 */
		return apply_filters( 'ffw_width_presets', $this->default_presets );
	}

	/**
	 * Sanitize widths configuration.
	 *
	 * @since 1.0.0
	 * @param array $widths Raw widths data.
	 * @return array Sanitized widths.
	 */
	private function sanitize_widths( $widths ) {
		if ( ! is_array( $widths ) ) {
			return array(
				'fields'     => array(),
				'responsive' => array(),
				'global'     => array(),
			);
		}

		$sanitized = array(
			'fields'     => array(),
			'responsive' => array(),
			'global'     => array(),
		);

		// Sanitize field widths.
		if ( isset( $widths['fields'] ) && is_array( $widths['fields'] ) ) {
			foreach ( $widths['fields'] as $field_id => $config ) {
				$sanitized['fields'][ sanitize_text_field( $field_id ) ] = $this->sanitize_field_config( $config );
			}
		}

		// Sanitize responsive settings.
		if ( isset( $widths['responsive'] ) && is_array( $widths['responsive'] ) ) {
			$sanitized['responsive'] = array(
				'enable_mobile'     => ! empty( $widths['responsive']['enable_mobile'] ),
				'enable_tablet'     => ! empty( $widths['responsive']['enable_tablet'] ),
				'mobile_full_width' => ! empty( $widths['responsive']['mobile_full_width'] ),
			);
		}

		// Sanitize global settings.
		if ( isset( $widths['global'] ) && is_array( $widths['global'] ) ) {
			$sanitized['global'] = array(
				'max_width'  => isset( $widths['global']['max_width'] ) ? absint( $widths['global']['max_width'] ) : 0,
				'alignment'  => isset( $widths['global']['alignment'] ) ? sanitize_text_field( $widths['global']['alignment'] ) : 'left',
				'gap'        => isset( $widths['global']['gap'] ) ? absint( $widths['global']['gap'] ) : 16,
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize individual field configuration.
	 *
	 * @since 1.0.0
	 * @param array $config Raw field config.
	 * @return array Sanitized config.
	 */
	private function sanitize_field_config( $config ) {
		if ( ! is_array( $config ) ) {
			return array(
				'width'         => 100,
				'width_unit'    => 'percentage',
				'min_width'     => 0,
				'max_width'     => 0,
				'mobile_width'  => 100,
				'tablet_width'  => null,
			);
		}

		return array(
			'width'         => isset( $config['width'] ) ? $this->sanitize_width_value( $config['width'] ) : 100,
			'width_unit'    => isset( $config['width_unit'] ) && in_array( $config['width_unit'], array( 'percentage', 'pixels', 'auto' ), true ) ? $config['width_unit'] : 'percentage',
			'min_width'     => isset( $config['min_width'] ) ? absint( $config['min_width'] ) : 0,
			'max_width'     => isset( $config['max_width'] ) ? absint( $config['max_width'] ) : 0,
			'mobile_width'  => isset( $config['mobile_width'] ) ? $this->sanitize_width_value( $config['mobile_width'] ) : 100,
			'tablet_width'  => isset( $config['tablet_width'] ) ? $this->sanitize_width_value( $config['tablet_width'] ) : null,
		);
	}

	/**
	 * Sanitize width value.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw width value.
	 * @return float Sanitized width value.
	 */
	private function sanitize_width_value( $value ) {
		$value = floatval( $value );

		// Ensure value is within reasonable bounds.
		if ( $value < 0 ) {
			$value = 0;
		}

		// For percentage, cap at 100.
		if ( $value > 1000 ) {
			$value = 1000;
		}

		return round( $value, 3 );
	}

	/**
	 * Check if a Forminator form exists.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return bool True if form exists.
	 */
	private function form_exists( $form_id ) {
		if ( ! class_exists( 'Forminator_API' ) ) {
			return false;
		}

		$form = \Forminator_API::get_form( $form_id );
		return ! is_wp_error( $form ) && $form;
	}

	/**
	 * Clear cached CSS for a form.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return void
	 */
	public function clear_form_cache( $form_id ) {
		delete_transient( 'ffw_form_css_' . $form_id );

		/**
		 * Fires after form cache is cleared.
		 *
		 * @since 1.0.0
		 * @param int $form_id Form ID.
		 */
		do_action( 'ffw_cache_cleared', $form_id );
	}

	/**
	 * Get all forms with custom widths.
	 *
	 * @since 1.0.0
	 * @return array Array of form IDs with custom widths.
	 */
	public function get_forms_with_widths() {
		global $wpdb;

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				self::OPTION_PREFIX . '%'
			)
		);

		$form_ids = array();
		foreach ( $results as $option_name ) {
			$form_id = str_replace( self::OPTION_PREFIX, '', $option_name );
			if ( is_numeric( $form_id ) ) {
				$form_ids[] = absint( $form_id );
			}
		}

		return $form_ids;
	}

	/**
	 * Export width settings for a form.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return array Export data.
	 */
	public function export_form_widths( $form_id ) {
		$widths = $this->get_form_widths( $form_id );

		return array(
			'version' => FFW_VERSION,
			'form_id' => $form_id,
			'widths'  => $widths,
			'exported_at' => current_time( 'mysql' ),
		);
	}

	/**
	 * Import width settings for a form.
	 *
	 * @since 1.0.0
	 * @param int   $form_id Form ID.
	 * @param array $data    Import data.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function import_form_widths( $form_id, array $data ) {
		if ( ! isset( $data['widths'] ) || ! is_array( $data['widths'] ) ) {
			return new \WP_Error(
				'invalid_import_data',
				__( 'Invalid import data format.', 'forminator-field-widths' )
			);
		}

		return $this->save_form_widths( $form_id, $data['widths'] );
	}
}
