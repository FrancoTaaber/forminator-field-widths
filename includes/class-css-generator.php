<?php
/**
 * CSS Generator class.
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
 * Class CSS_Generator
 *
 * Generates CSS for field width configurations.
 *
 * @since 1.0.0
 */
class CSS_Generator {

	/**
	 * Plugin options.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $options;

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
	 * Generate CSS for a form's field widths.
	 *
	 * @since 1.0.0
	 * @param int   $form_id Form ID.
	 * @param array $widths  Width configuration.
	 * @return string Generated CSS.
	 */
	public function generate_form_css( $form_id, array $widths ) {
		$css     = '';
		$form_id = absint( $form_id );

		if ( empty( $widths['fields'] ) ) {
			return $css;
		}

		$rules = array();

		foreach ( $widths['fields'] as $field_id => $config ) {
			$width = isset( $config['width'] ) ? floatval( $config['width'] ) : 100;

			// Skip 100% widths - that's the default.
			if ( abs( $width - 100 ) < 0.1 ) {
				continue;
			}

			$field_id = esc_attr( $field_id );

			// Multiple selectors to ensure it works.
			$selectors = array(
				// Standard Forminator selector.
				".forminator-custom-form-{$form_id} #{$field_id}",
				// With .forminator-col class.
				".forminator-custom-form-{$form_id} #{$field_id}.forminator-col",
				// Fallback for UI wrapper.
				".forminator-ui.forminator-custom-form-{$form_id} #{$field_id}",
			);

			$selector = implode( ",\n", $selectors );

			$rules[] = sprintf(
				"%s {\n  width: %s%% !important;\n  flex: 0 0 %s%% !important;\n  max-width: %s%% !important;\n}",
				$selector,
				$width,
				$width,
				$width
			);
		}

		if ( ! empty( $rules ) ) {
			$css = "/* Forminator Field Widths - Form #{$form_id} */\n" . implode( "\n\n", $rules );
		}

		// Add mobile responsive CSS.
		if ( ! empty( $this->options['mobile_full_width'] ) ) {
			$mobile_rules = array();
			foreach ( $widths['fields'] as $field_id => $config ) {
				$width = isset( $config['width'] ) ? floatval( $config['width'] ) : 100;
				if ( abs( $width - 100 ) < 0.1 ) {
					continue;
				}

				$field_id     = esc_attr( $field_id );
				$mobile_rules[] = sprintf(
					".forminator-custom-form-{$form_id} #{$field_id} { width: 100%% !important; flex: 0 0 100%% !important; max-width: 100%% !important; }"
				);
			}

			if ( ! empty( $mobile_rules ) ) {
				$breakpoint = isset( $this->options['mobile_breakpoint'] ) ? absint( $this->options['mobile_breakpoint'] ) : 768;
				$css       .= "\n\n@media (max-width: {$breakpoint}px) {\n  " . implode( "\n  ", $mobile_rules ) . "\n}";
			}
		}

		return $css;
	}
}
