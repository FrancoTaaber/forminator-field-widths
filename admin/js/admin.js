/**
 * Forminator Field Widths - Admin Scripts
 *
 * @package Forminator_Field_Widths
 * @since   1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Main FFW Admin object
	 */
	var FFWAdmin = {

		/**
		 * Current form ID
		 */
		formId: 0,

		/**
		 * Cached widths data
		 */
		widths: {},

		/**
		 * Save timers for debouncing
		 */
		saveTimers: {},

		/**
		 * Initialize
		 */
		init: function() {
			this.formId = this.getFormId();
			this.bindEvents();
			
			if (this.formId) {
				this.loadWidths();
			}
		},

		/**
		 * Get current form ID from URL or DOM
		 */
		getFormId: function() {
			var urlParams = new URLSearchParams(window.location.search);
			var id = urlParams.get('id');
			
			if (id) {
				return parseInt(id, 10);
			}

			var $input = $('input[name="form_id"]');
			if ($input.length) {
				return parseInt($input.val(), 10);
			}

			return 0;
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			var self = this;

			// Settings page tab switching
			$(document).on('click', '.sui-vertical-tab a', function(e) {
				e.preventDefault();
				var tab = $(this).parent().data('tab');
				self.switchTab(tab);
			});

			// Responsive toggle
			$(document).on('change', '#ffw-enable-responsive', function() {
				var $settings = $('.ffw-responsive-settings');
				if ($(this).is(':checked')) {
					$settings.slideDown();
				} else {
					$settings.slideUp();
				}
			});

			// Clear cache button
			$(document).on('click', '#ffw-clear-cache', function() {
				self.clearCache($(this));
			});

			// Clear form widths
			$(document).on('click', '.ffw-clear-form-widths', function() {
				var formId = $(this).data('form-id');
				self.clearFormWidths(formId, $(this).closest('tr'));
			});

			// Width value changes
			$(document).on('input change', '.ffw-width-value', function() {
				var $control = $(this).closest('.ffw-width-control');
				var fieldId = $control.data('field-id');
				self.handleWidthChange($control, fieldId);
			});

			// Unit changes
			$(document).on('change', '.ffw-width-unit', function() {
				var $control = $(this).closest('.ffw-width-control');
				var fieldId = $control.data('field-id');
				self.handleUnitChange($control, fieldId);
			});

			// Preset clicks
			$(document).on('click', '.ffw-preset', function() {
				var $control = $(this).closest('.ffw-width-control');
				var preset = $(this).data('preset');
				self.applyPreset($control, preset);
			});

			// Mobile width toggle
			$(document).on('change', '.ffw-enable-responsive', function() {
				var $row = $(this).closest('.ffw-responsive-row').find('.ffw-mobile-width-row');
				if ($(this).is(':checked')) {
					$row.slideDown();
				} else {
					$row.slideUp();
				}
			});

			// Mobile width change
			$(document).on('input change', '.ffw-mobile-width', function() {
				var $control = $(this).closest('.ffw-width-control');
				var fieldId = $control.data('field-id');
				self.handleWidthChange($control, fieldId);
			});
		},

		/**
		 * Switch settings tab
		 */
		switchTab: function(tab) {
			$('.sui-vertical-tab').removeClass('current');
			$('.sui-vertical-tab[data-tab="' + tab + '"]').addClass('current');
			$('.ffw-tab-content').hide();
			$('#' + tab + '-content').show();
		},

		/**
		 * Load widths for current form
		 */
		loadWidths: function() {
			var self = this;

			$.ajax({
				url: ffwAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ffw_get_form_widths',
					form_id: this.formId,
					nonce: ffwAdmin.nonce
				},
				success: function(response) {
					if (response.success && response.data.widths) {
						self.widths = response.data.widths;
						self.updateAllControls();
					}
				}
			});
		},

		/**
		 * Save field width
		 */
		saveFieldWidth: function(fieldId, config) {
			var self = this;

			// Show saving indicator
			this.showSaveStatus(fieldId, 'saving');

			$.ajax({
				url: ffwAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ffw_save_field_width',
					form_id: this.formId,
					field_id: fieldId,
					config: JSON.stringify(config),
					nonce: ffwAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						self.showSaveStatus(fieldId, 'saved');
					} else {
						self.showSaveStatus(fieldId, 'error');
					}
				},
				error: function() {
					self.showSaveStatus(fieldId, 'error');
				}
			});
		},

		/**
		 * Debounced save
		 */
		debouncedSave: function(fieldId, config) {
			var self = this;

			if (this.saveTimers[fieldId]) {
				clearTimeout(this.saveTimers[fieldId]);
			}

			this.saveTimers[fieldId] = setTimeout(function() {
				self.saveFieldWidth(fieldId, config);
			}, 500);
		},

		/**
		 * Handle width value change
		 */
		handleWidthChange: function($control, fieldId) {
			var config = this.getConfigFromControl($control);

			// Update local cache
			if (!this.widths.fields) {
				this.widths.fields = {};
			}
			this.widths.fields[fieldId] = config;

			// Update slider
			this.updateSlider($control, config.width);

			// Update preview
			this.updatePreview($control, config.width);

			// Save (debounced)
			this.debouncedSave(fieldId, config);
		},

		/**
		 * Handle unit change
		 */
		handleUnitChange: function($control, fieldId) {
			var unit = $control.find('.ffw-width-unit').val();
			var $input = $control.find('.ffw-width-value');

			// Update input constraints
			if (unit === 'percentage') {
				$input.attr('max', 100);
				if (parseFloat($input.val()) > 100) {
					$input.val(100);
				}
			} else if (unit === 'pixels') {
				$input.attr('max', 2000);
			}

			this.handleWidthChange($control, fieldId);
		},

		/**
		 * Apply preset
		 */
		applyPreset: function($control, preset) {
			var presetConfig = ffwAdmin.presets[preset];
			if (!presetConfig) return;

			// Update values
			$control.find('.ffw-width-value').val(presetConfig.value);
			$control.find('.ffw-width-unit').val('percentage');

			// Update active state
			$control.find('.ffw-preset').removeClass('active');
			$control.find('.ffw-preset[data-preset="' + preset + '"]').addClass('active');

			// Trigger change
			var fieldId = $control.data('field-id');
			this.handleWidthChange($control, fieldId);
		},

		/**
		 * Get config from control
		 */
		getConfigFromControl: function($control) {
			var width = parseFloat($control.find('.ffw-width-value').val()) || 100;
			var unit = $control.find('.ffw-width-unit').val() || 'percentage';
			var mobileWidth = parseFloat($control.find('.ffw-mobile-width').val()) || 100;
			var hasResponsive = $control.find('.ffw-enable-responsive').is(':checked');

			return {
				width: width,
				width_unit: unit,
				mobile_width: hasResponsive ? mobileWidth : 100,
				min_width: 0,
				max_width: 0
			};
		},

		/**
		 * Update all controls from cached data
		 */
		updateAllControls: function() {
			var self = this;

			$('.ffw-width-control').each(function() {
				var $control = $(this);
				var fieldId = $control.data('field-id');
				var config = self.getFieldConfig(fieldId);

				$control.find('.ffw-width-value').val(config.width);
				$control.find('.ffw-width-unit').val(config.width_unit);
				$control.find('.ffw-mobile-width').val(config.mobile_width);

				self.updateSlider($control, config.width);
				self.updateActivePreset($control, config.width);
			});
		},

		/**
		 * Get field config from cache
		 */
		getFieldConfig: function(fieldId) {
			var defaults = {
				width: 100,
				width_unit: 'percentage',
				mobile_width: 100,
				min_width: 0,
				max_width: 0
			};

			if (this.widths.fields && this.widths.fields[fieldId]) {
				return $.extend({}, defaults, this.widths.fields[fieldId]);
			}

			return defaults;
		},

		/**
		 * Initialize slider for a control
		 */
		initSlider: function($control) {
			var self = this;
			var $slider = $control.find('.ffw-slider');

			if (!$slider.length || $slider.hasClass('ui-slider')) {
				return;
			}

			var currentValue = parseFloat($control.find('.ffw-width-value').val()) || 100;

			$slider.slider({
				min: 0,
				max: 100,
				value: currentValue,
				range: 'min',
				slide: function(event, ui) {
					$control.find('.ffw-width-value').val(ui.value);
					self.updatePreview($control, ui.value);
				},
				change: function(event, ui) {
					if (event.originalEvent) {
						var fieldId = $control.data('field-id');
						self.handleWidthChange($control, fieldId);
					}
				}
			});
		},

		/**
		 * Update slider value
		 */
		updateSlider: function($control, value) {
			var $slider = $control.find('.ffw-slider');
			if ($slider.hasClass('ui-slider')) {
				$slider.slider('value', value);
			}
		},

		/**
		 * Update active preset based on value
		 */
		updateActivePreset: function($control, value) {
			$control.find('.ffw-preset').removeClass('active');

			// Check if value matches a preset
			var presets = {
				100: 'full',
				50: 'half',
				33.333: 'third',
				25: 'quarter'
			};

			var preset = presets[Math.round(value * 1000) / 1000];
			if (preset) {
				$control.find('.ffw-preset[data-preset="' + preset + '"]').addClass('active');
			}
		},

		/**
		 * Update preview
		 */
		updatePreview: function($control, value) {
			var $preview = $control.find('.ffw-preview-field');
			if ($preview.length) {
				$preview.css('width', value + '%');
			}
		},

		/**
		 * Show save status
		 */
		showSaveStatus: function(fieldId, status) {
			var $control = $('.ffw-width-control[data-field-id="' + fieldId + '"]');
			var $status = $control.find('.ffw-save-status');

			if (!$status.length) {
				$status = $('<span class="ffw-save-status"></span>');
				$control.find('.sui-box-settings-col-2').append($status);
			}

			$status.removeClass('saving saved error');

			switch (status) {
				case 'saving':
					$status.addClass('saving').html('<span class="spinner is-active"></span> ' + ffwAdmin.i18n.saving);
					break;
				case 'saved':
					$status.addClass('saved').text(ffwAdmin.i18n.saved);
					setTimeout(function() {
						$status.fadeOut(function() {
							$(this).remove();
						});
					}, 2000);
					break;
				case 'error':
					$status.addClass('error').text(ffwAdmin.i18n.saveError);
					break;
			}
		},

		/**
		 * Clear cache
		 */
		clearCache: function($button) {
			$button.addClass('sui-button-onload');

			$.ajax({
				url: ffwAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ffw_clear_cache',
					nonce: ffwAdmin.nonce
				},
				success: function(response) {
					$button.removeClass('sui-button-onload');
					if (response.success) {
						FFWAdmin.showNotice(response.data.message, 'success');
					}
				},
				error: function() {
					$button.removeClass('sui-button-onload');
					FFWAdmin.showNotice(ffwAdmin.i18n.saveError, 'error');
				}
			});
		},

		/**
		 * Clear form widths
		 */
		clearFormWidths: function(formId, $row) {
			if (!confirm(ffwAdmin.i18n.confirmClear)) {
				return;
			}

			$.ajax({
				url: ffwAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ffw_clear_form_widths',
					form_id: formId,
					nonce: ffwAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						$row.fadeOut(function() {
							$(this).remove();
						});
					}
				}
			});
		},

		/**
		 * Show notice
		 */
		showNotice: function(message, type) {
			type = type || 'info';

			// Try to use SUI notice if available
			if (typeof SUI !== 'undefined' && SUI.openNotice) {
				SUI.openNotice('ffw-notice-' + Date.now(), '<p>' + message + '</p>', {
					type: type,
					autoclose: { show: true, timeout: 3000 }
				});
			} else {
				// Fallback to custom notice
				var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
				$('.wrap h1').first().after($notice);

				setTimeout(function() {
					$notice.fadeOut(function() {
						$(this).remove();
					});
				}, 3000);
			}
		},

		/**
		 * Export settings
		 */
		exportSettings: function() {
			$.ajax({
				url: ffwAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ffw_export_settings',
					form_id: this.formId,
					nonce: ffwAdmin.nonce
				},
				success: function(response) {
					if (response.success && response.data.data) {
						var json = JSON.stringify(response.data.data, null, 2);
						var blob = new Blob([json], { type: 'application/json' });
						var url = URL.createObjectURL(blob);
						var a = document.createElement('a');
						a.href = url;
						a.download = 'ffw-settings-form-' + FFWAdmin.formId + '.json';
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						URL.revokeObjectURL(url);
					}
				}
			});
		},

		/**
		 * Import settings
		 */
		importSettings: function(data) {
			$.ajax({
				url: ffwAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ffw_import_settings',
					form_id: this.formId,
					data: JSON.stringify(data),
					nonce: ffwAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						FFWAdmin.showNotice(response.data.message, 'success');
						FFWAdmin.loadWidths();
					} else {
						FFWAdmin.showNotice(response.data.message, 'error');
					}
				}
			});
		}
	};

	// Initialize on DOM ready
	$(document).ready(function() {
		FFWAdmin.init();
	});

	// Expose for external use
	window.FFWAdmin = FFWAdmin;

})(jQuery);
