<?php
/**
 * Frontend class.
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
 * Class Frontend
 *
 * Handles frontend CSS output for field widths.
 *
 * @since 1.0.0
 */
class Frontend {

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
	 * CSS Generator instance.
	 *
	 * @since 1.0.0
	 * @var CSS_Generator
	 */
	private $css_generator;

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
		$this->css_generator = new CSS_Generator( $options );

		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooks() {
		// Output CSS in wp_head for ALL forms that have custom widths.
		// This is the most reliable way to ensure CSS is loaded.
		add_action( 'wp_head', array( $this, 'output_all_css' ), 100 );
	}

	/**
	 * Output CSS for all forms with custom widths.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_all_css() {
		$form_ids = $this->width_manager->get_forms_with_widths();

		if ( empty( $form_ids ) ) {
			return;
		}

		$all_css = '';

		foreach ( $form_ids as $form_id ) {
			$widths   = $this->width_manager->get_form_widths( $form_id );
			$form_css = $this->css_generator->generate_form_css( $form_id, $widths );

			if ( ! empty( $form_css ) ) {
				$all_css .= $form_css . "\n\n";
			}
		}

		if ( ! empty( $all_css ) ) {
			echo '<style id="forminator-field-widths-css" type="text/css">' . "\n";
			echo $all_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</style>' . "\n";
		}
	}
}
