<?php
/**
 * Settings page template.
 *
 * @package Forminator_Field_Widths
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all Forminator forms.
$forms = array();
if ( class_exists( 'Forminator_API' ) ) {
	$all_forms = Forminator_API::get_forms( null, 1, -1 );
	if ( ! is_wp_error( $all_forms ) ) {
		$forms = $all_forms;
	}
}

// Get selected form ID.
$selected_form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
if ( ! $selected_form_id && ! empty( $forms ) ) {
	$selected_form_id = $forms[0]->id;
}

// Get form fields and widths.
$form_fields = array();
$form_widths = array();
if ( $selected_form_id ) {
	$width_manager = \Forminator_Field_Widths\Plugin::get_instance()->get_width_manager();
	$form_widths   = $width_manager->get_form_widths( $selected_form_id );

	// Get form fields.
	$form = Forminator_API::get_form( $selected_form_id );
	if ( ! is_wp_error( $form ) && $form && ! empty( $form->fields ) ) {
		foreach ( $form->fields as $field_obj ) {
			if ( is_object( $field_obj ) && method_exists( $field_obj, 'to_array' ) ) {
				$fd = $field_obj->to_array();
				$form_fields[] = array(
					'id'    => $fd['element_id'] ?? $field_obj->slug ?? '',
					'type'  => $fd['type'] ?? '',
					'label' => $fd['field_label'] ?? $fd['label'] ?? $fd['placeholder'] ?? $field_obj->slug ?? '',
				);
			} elseif ( is_object( $field_obj ) ) {
				$form_fields[] = array(
					'id'    => $field_obj->element_id ?? $field_obj->slug ?? '',
					'type'  => $field_obj->type ?? '',
					'label' => $field_obj->field_label ?? $field_obj->label ?? $field_obj->slug ?? '',
				);
			} elseif ( is_array( $field_obj ) ) {
				if ( isset( $field_obj['fields'] ) ) {
					foreach ( $field_obj['fields'] as $inner ) {
						$fd = is_object( $inner ) ? ( method_exists( $inner, 'to_array' ) ? $inner->to_array() : (array) $inner ) : $inner;
						$form_fields[] = array(
							'id'    => $fd['element_id'] ?? '',
							'type'  => $fd['type'] ?? '',
							'label' => $fd['field_label'] ?? $fd['label'] ?? $fd['element_id'] ?? '',
						);
					}
				} else {
					$form_fields[] = array(
						'id'    => $field_obj['element_id'] ?? '',
						'type'  => $field_obj['type'] ?? '',
						'label' => $field_obj['field_label'] ?? $field_obj['label'] ?? $field_obj['element_id'] ?? '',
					);
				}
			}
		}
	}
}
?>
<div class="wrap" id="ffw-settings">
	<h1><?php esc_html_e( 'Forminator Field Widths', 'forminator-field-widths' ); ?></h1>
	
	<p class="description">
		<?php esc_html_e( 'Set custom widths for your Forminator form fields. Changes are applied immediately after saving.', 'forminator-field-widths' ); ?>
	</p>

	<?php if ( empty( $forms ) ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'No Forminator forms found. Please create a form first.', 'forminator-field-widths' ); ?></p>
		</div>
	<?php else : ?>
		
		<!-- Form Selector -->
		<div class="ffw-form-selector">
			<label for="ffw-form-select"><strong><?php esc_html_e( 'Select Form:', 'forminator-field-widths' ); ?></strong></label>
			<select id="ffw-form-select">
				<?php foreach ( $forms as $form ) : ?>
					<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $selected_form_id, $form->id ); ?>>
						<?php echo esc_html( $form->name ); ?> (ID: <?php echo esc_html( $form->id ); ?>)
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( $selected_form_id && ! empty( $form_fields ) ) : ?>
			
			<!-- Fields Table -->
			<table class="wp-list-table widefat fixed striped" id="ffw-fields-table" data-form-id="<?php echo esc_attr( $selected_form_id ); ?>">
				<thead>
					<tr>
						<th style="width: 30%;"><?php esc_html_e( 'Field', 'forminator-field-widths' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'Width', 'forminator-field-widths' ); ?></th>
						<th style="width: 50%;"><?php esc_html_e( 'Quick Select', 'forminator-field-widths' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $form_fields as $field ) : ?>
						<?php
						$field_id = $field['id'];
						if ( empty( $field_id ) ) {
							continue;
						}
						$current_width = isset( $form_widths['fields'][ $field_id ]['width'] ) 
							? floatval( $form_widths['fields'][ $field_id ]['width'] ) 
							: 100;
						?>
						<tr data-field-id="<?php echo esc_attr( $field_id ); ?>">
							<td>
								<strong><?php echo esc_html( $field['label'] ?: $field_id ); ?></strong><br>
								<code style="font-size: 11px; color: #888;"><?php echo esc_html( $field['type'] ); ?> - <?php echo esc_html( $field_id ); ?></code>
							</td>
							<td>
								<div class="ffw-width-input-wrap">
									<input type="number" class="ffw-width-input" value="<?php echo esc_attr( $current_width ); ?>" min="10" max="100" step="1">
									<span>%</span>
								</div>
							</td>
							<td>
								<div class="ffw-presets">
									<button type="button" class="button ffw-preset <?php echo ( abs( $current_width - 100 ) < 0.5 ) ? 'active' : ''; ?>" data-value="100">100%</button>
									<button type="button" class="button ffw-preset <?php echo ( abs( $current_width - 75 ) < 0.5 ) ? 'active' : ''; ?>" data-value="75">75%</button>
									<button type="button" class="button ffw-preset <?php echo ( abs( $current_width - 66.66 ) < 1 ) ? 'active' : ''; ?>" data-value="66.66">66%</button>
									<button type="button" class="button ffw-preset <?php echo ( abs( $current_width - 50 ) < 0.5 ) ? 'active' : ''; ?>" data-value="50">50%</button>
									<button type="button" class="button ffw-preset <?php echo ( abs( $current_width - 33.33 ) < 1 ) ? 'active' : ''; ?>" data-value="33.33">33%</button>
									<button type="button" class="button ffw-preset <?php echo ( abs( $current_width - 25 ) < 0.5 ) ? 'active' : ''; ?>" data-value="25">25%</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Save Button -->
			<p class="submit">
				<button type="button" class="button button-primary button-large" id="ffw-save">
					<?php esc_html_e( 'Save Field Widths', 'forminator-field-widths' ); ?>
				</button>
				<span id="ffw-status"></span>
			</p>

		<?php elseif ( $selected_form_id ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'This form has no fields yet.', 'forminator-field-widths' ); ?></p>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</div>

<style>
#ffw-settings {
	max-width: 900px;
}

.ffw-form-selector {
	margin: 20px 0;
	padding: 15px;
	background: #fff;
	border: 1px solid #ccd0d4;
}

.ffw-form-selector select {
	margin-left: 10px;
	min-width: 300px;
}

#ffw-fields-table {
	margin-top: 20px;
}

.ffw-width-input-wrap {
	display: flex;
	align-items: center;
	gap: 5px;
}

.ffw-width-input {
	width: 70px !important;
	text-align: center;
}

.ffw-presets {
	display: flex;
	gap: 5px;
	flex-wrap: wrap;
}

.ffw-preset {
	min-width: 50px;
}

.ffw-preset.active {
	background: #2271b1;
	color: #fff;
	border-color: #2271b1;
}

#ffw-status {
	margin-left: 15px;
	font-style: italic;
}

#ffw-status.success {
	color: #00a32a;
}

#ffw-status.error {
	color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
	var formId = <?php echo $selected_form_id ? $selected_form_id : 0; ?>;
	
	// Form selector
	$('#ffw-form-select').on('change', function() {
		window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=forminator-field-widths&form_id=' ) ); ?>' + $(this).val();
	});
	
	// Preset buttons
	$('.ffw-preset').on('click', function() {
		var $row = $(this).closest('tr');
		var value = parseFloat($(this).data('value'));
		
		$row.find('.ffw-width-input').val(value);
		$row.find('.ffw-preset').removeClass('active');
		$(this).addClass('active');
	});
	
	// Width input change
	$('.ffw-width-input').on('input', function() {
		var $row = $(this).closest('tr');
		var value = parseFloat($(this).val()) || 100;
		
		$row.find('.ffw-preset').removeClass('active');
		$row.find('.ffw-preset').each(function() {
			if (Math.abs(parseFloat($(this).data('value')) - value) < 1) {
				$(this).addClass('active');
			}
		});
	});
	
	// Save
	$('#ffw-save').on('click', function() {
		var $btn = $(this);
		var $status = $('#ffw-status');
		var widths = { fields: {} };
		
		$('#ffw-fields-table tbody tr').each(function() {
			var fieldId = $(this).data('field-id');
			var width = parseFloat($(this).find('.ffw-width-input').val()) || 100;
			
			widths.fields[fieldId] = {
				width: width,
				width_unit: 'percentage',
				mobile_width: 100
			};
		});
		
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Saving...', 'forminator-field-widths' ) ); ?>');
		$status.removeClass('success error').text('');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'ffw_save_form_widths',
				form_id: formId,
				widths: JSON.stringify(widths),
				nonce: '<?php echo esc_js( wp_create_nonce( 'ffw_admin' ) ); ?>'
			},
			success: function(response) {
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save Field Widths', 'forminator-field-widths' ) ); ?>');
				if (response.success) {
					$status.addClass('success').text('<?php echo esc_js( __( 'Saved! Refresh your form page to see changes.', 'forminator-field-widths' ) ); ?>');
				} else {
					$status.addClass('error').text(response.data.message || '<?php echo esc_js( __( 'Error saving', 'forminator-field-widths' ) ); ?>');
				}
			},
			error: function() {
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save Field Widths', 'forminator-field-widths' ) ); ?>');
				$status.addClass('error').text('<?php echo esc_js( __( 'Error saving', 'forminator-field-widths' ) ); ?>');
			}
		});
	});
});
</script>
