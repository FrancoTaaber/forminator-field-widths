<?php
/**
 * AJAX Handler class.
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
 * Class Ajax_Handler
 *
 * Handles all AJAX requests for the plugin.
 *
 * @since 1.0.0
 */
class Ajax_Handler {

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
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param Width_Manager $width_manager Width Manager instance.
	 * @param array         $options       Plugin options.
	 */
	public function __construct( Width_Manager $width_manager, array $options ) {
		$this->width_manager = $width_manager;
		$this->options       = $options;

		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_ajax_handlers() {
		add_action( 'wp_ajax_ffw_get_form_widths', array( $this, 'get_form_widths' ) );
		add_action( 'wp_ajax_ffw_save_form_widths', array( $this, 'save_form_widths' ) );
		add_action( 'wp_ajax_ffw_save_field_width', array( $this, 'save_field_width' ) );
		add_action( 'wp_ajax_ffw_clear_form_widths', array( $this, 'clear_form_widths' ) );
		add_action( 'wp_ajax_ffw_get_form_fields', array( $this, 'get_form_fields' ) );
		add_action( 'wp_ajax_ffw_export_settings', array( $this, 'export_settings' ) );
		add_action( 'wp_ajax_ffw_import_settings', array( $this, 'import_settings' ) );
		add_action( 'wp_ajax_ffw_preview_css', array( $this, 'preview_css' ) );
		add_action( 'wp_ajax_ffw_clear_cache', array( $this, 'clear_cache' ) );
	}

	/**
	 * Verify AJAX nonce and permissions.
	 *
	 * @since 1.0.0
	 * @param string $nonce_action Nonce action name.
	 * @return bool True if valid.
	 */
	private function verify_request( $nonce_action = 'ffw_admin' ) {
		if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security verification failed.', 'forminator-field-widths' ) ),
				403
			);
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'forminator-field-widths' ) ),
				403
			);
			return false;
		}

		return true;
	}

	/**
	 * Get form widths via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_form_widths() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		$widths = $this->width_manager->get_form_widths( $form_id );

		wp_send_json_success( array( 'widths' => $widths ) );
	}

	/**
	 * Save form widths via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_form_widths() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$widths  = isset( $_POST['widths'] ) ? json_decode( wp_unslash( $_POST['widths'] ), true ) : array();

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		if ( ! is_array( $widths ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid widths data.', 'forminator-field-widths' ) )
			);
		}

		$result = $this->width_manager->save_form_widths( $form_id, $widths );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		wp_send_json_success(
			array( 'message' => __( 'Field widths saved successfully.', 'forminator-field-widths' ) )
		);
	}

	/**
	 * Save individual field width via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function save_field_width() {
		$this->verify_request();

		$form_id  = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$field_id = isset( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) : '';
		$config   = isset( $_POST['config'] ) ? json_decode( wp_unslash( $_POST['config'] ), true ) : array();

		if ( ! $form_id || empty( $field_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form or field ID.', 'forminator-field-widths' ) )
			);
		}

		$result = $this->width_manager->set_field_width( $form_id, $field_id, $config );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		wp_send_json_success(
			array( 'message' => __( 'Field width saved.', 'forminator-field-widths' ) )
		);
	}

	/**
	 * Clear all widths for a form via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear_form_widths() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		$this->width_manager->delete_form_widths( $form_id );

		wp_send_json_success(
			array( 'message' => __( 'All field widths cleared.', 'forminator-field-widths' ) )
		);
	}

	/**
	 * Get form fields via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_form_fields() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		$fields = $this->get_forminator_fields( $form_id );

		wp_send_json_success( array( 'fields' => $fields ) );
	}

	/**
	 * Get Forminator form fields.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return array Form fields.
	 */
	private function get_forminator_fields( $form_id ) {
		if ( ! class_exists( 'Forminator_API' ) ) {
			return array();
		}

		$form = \Forminator_API::get_form( $form_id );

		if ( is_wp_error( $form ) || ! $form ) {
			return array();
		}

		$fields = array();

		if ( ! empty( $form->fields ) ) {
			foreach ( $form->fields as $field_obj ) {
				// Handle both object (Forminator_Form_Field_Model) and array formats.
				$field_data = null;

				if ( is_object( $field_obj ) && method_exists( $field_obj, 'to_array' ) ) {
					$fd = $field_obj->to_array();
					$field_data = array(
						'id'    => $fd['element_id'] ?? $field_obj->slug ?? '',
						'type'  => $fd['type'] ?? '',
						'label' => $fd['field_label'] ?? $fd['label'] ?? $fd['placeholder'] ?? $field_obj->slug ?? '',
						'cols'  => $fd['cols'] ?? 12,
					);
				} elseif ( is_object( $field_obj ) ) {
					$field_data = array(
						'id'    => $field_obj->element_id ?? $field_obj->slug ?? '',
						'type'  => $field_obj->type ?? '',
						'label' => $field_obj->field_label ?? $field_obj->label ?? $field_obj->slug ?? '',
						'cols'  => $field_obj->cols ?? 12,
					);
				} elseif ( is_array( $field_obj ) ) {
					if ( isset( $field_obj['fields'] ) && is_array( $field_obj['fields'] ) ) {
						// Wrapper containing multiple fields.
						foreach ( $field_obj['fields'] as $inner_field ) {
							$fd = is_object( $inner_field ) ? 
								( method_exists( $inner_field, 'to_array' ) ? $inner_field->to_array() : (array) $inner_field ) : 
								$inner_field;
							
							$inner_data = array(
								'id'    => $fd['element_id'] ?? '',
								'type'  => $fd['type'] ?? '',
								'label' => $fd['field_label'] ?? $fd['label'] ?? $fd['placeholder'] ?? $fd['element_id'] ?? '',
								'cols'  => $fd['cols'] ?? 12,
							);
							if ( ! empty( $inner_data['id'] ) ) {
								$fields[] = $inner_data;
							}
						}
						continue;
					} else {
						$field_data = array(
							'id'    => $field_obj['element_id'] ?? '',
							'type'  => $field_obj['type'] ?? '',
							'label' => $field_obj['field_label'] ?? $field_obj['label'] ?? $field_obj['placeholder'] ?? $field_obj['element_id'] ?? '',
							'cols'  => $field_obj['cols'] ?? 12,
						);
					}
				}

				if ( $field_data && ! empty( $field_data['id'] ) ) {
					$fields[] = $field_data;
				}
			}
		}

		return $fields;
	}

	/**
	 * Get field label.
	 *
	 * @since 1.0.0
	 * @param array $field Field data.
	 * @return string Field label.
	 */
	private function get_field_label( $field ) {
		// Try different label properties.
		$label_keys = array( 'field_label', 'label', 'placeholder', 'element_id' );

		foreach ( $label_keys as $key ) {
			if ( ! empty( $field[ $key ] ) ) {
				return $field[ $key ];
			}
		}

		return $field['type'] ?? __( 'Unnamed Field', 'forminator-field-widths' );
	}

	/**
	 * Export settings via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function export_settings() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		$export_data = $this->width_manager->export_form_widths( $form_id );

		wp_send_json_success( array( 'data' => $export_data ) );
	}

	/**
	 * Import settings via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function import_settings() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$data    = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : array();

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		$result = $this->width_manager->import_form_widths( $form_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		wp_send_json_success(
			array( 'message' => __( 'Settings imported successfully.', 'forminator-field-widths' ) )
		);
	}

	/**
	 * Preview CSS via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function preview_css() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$widths  = isset( $_POST['widths'] ) ? json_decode( wp_unslash( $_POST['widths'] ), true ) : array();

		if ( ! $form_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid form ID.', 'forminator-field-widths' ) )
			);
		}

		$css_generator = new CSS_Generator( $this->options );
		$css = $css_generator->generate_form_css( $form_id, $widths );

		wp_send_json_success( array( 'css' => $css ) );
	}

	/**
	 * Clear cache via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear_cache() {
		$this->verify_request();

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( $form_id ) {
			$this->width_manager->clear_form_cache( $form_id );
		} else {
			// Clear all caches.
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					'%_transient_ffw_form_css_%'
				)
			);
		}

		wp_send_json_success(
			array( 'message' => __( 'Cache cleared successfully.', 'forminator-field-widths' ) )
		);
	}
}
