/* Etchmail Standalone — Admin JS */
(function ($) {
	'use strict';

	var $listSelect   = $('#etchfoin-sa-list-select');
	var $fieldsBody   = $('#etchfoin-sa-fields-body');
	var $fieldsSection    = $('#etchfoin-sa-fields-section');
	var $styleSection     = $('#etchfoin-sa-style-section');
	var $shortcodeSection = $('#etchfoin-sa-shortcode-section');
	var $shortcodeCode    = $('#etchfoin-sa-shortcode');
	var $status       = $('#etchfoin-sa-status');

	var allFields     = [];
	var savedFields   = [];

	/* ── Style control IDs → keys mapping ─────────────────────────────── */
	var styleKeys = {
		'etchfoin-style-theme':              { key: 'theme',              def: 'light' },
		'etchfoin-style-layout':             { key: 'layout',             def: 'stacked' },
		'etchfoin-style-max-width':          { key: 'max_width',          def: '600' },
		'etchfoin-style-form-padding':       { key: 'form_padding',       def: '24' },
		'etchfoin-style-field-gap':          { key: 'field_gap',          def: '16' },
		'etchfoin-style-form-bg':            { key: 'bg_color',           def: '#ffffff' },
		'etchfoin-style-form-border-color':  { key: 'form_border_color',  def: '#cccccc' },
		'etchfoin-style-form-border-radius': { key: 'border_radius',      def: '6' },
		'etchfoin-style-show-labels':        { key: 'show_labels',        def: 'true' },
		'etchfoin-style-label-color':        { key: 'label_color',        def: '#333333' },
		'etchfoin-style-label-size':         { key: 'label_size',         def: '13' },
		'etchfoin-style-label-weight':       { key: 'label_weight',       def: '600' },
		'etchfoin-style-label-spacing':      { key: 'label_spacing',      def: '4' },
		'etchfoin-style-field-bg':           { key: 'field_bg',           def: '#ffffff' },
		'etchfoin-style-field-text-color':   { key: 'text_color',         def: '#333333' },
		'etchfoin-style-field-border-color': { key: 'field_border_color', def: '#cccccc' },
		'etchfoin-style-field-focus-color':  { key: 'accent_color',       def: '#0073aa' },
		'etchfoin-style-field-border-width': { key: 'field_border_width', def: '1' },
		'etchfoin-style-field-border-radius':{ key: 'field_border_radius',def: '6' },
		'etchfoin-style-field-font-size':    { key: 'field_font_size',    def: '15' },
		'etchfoin-style-field-padding-v':    { key: 'field_padding_v',    def: '10' },
		'etchfoin-style-field-padding-h':    { key: 'field_padding_h',    def: '12' },
		'etchfoin-style-field-height':       { key: 'field_height',       def: '44' },
		'etchfoin-style-placeholder-color':  { key: 'placeholder_color',  def: '#999999' },
		'etchfoin-style-button-text':        { key: 'button_text',        def: 'Subscribe' },
		'etchfoin-style-button-bg':          { key: 'button_bg',          def: '#0073aa' },
		'etchfoin-style-button-text-color':  { key: 'button_text_color',  def: '#ffffff' },
		'etchfoin-style-button-font-size':   { key: 'button_font_size',   def: '15' },
		'etchfoin-style-button-font-weight': { key: 'button_font_weight', def: '600' },
		'etchfoin-style-button-padding-v':   { key: 'button_padding_v',   def: '10' },
		'etchfoin-style-button-padding-h':   { key: 'button_padding_h',   def: '24' },
		'etchfoin-style-button-radius':      { key: 'button_radius',      def: '6' },
		'etchfoin-style-button-full-width':  { key: 'button_full_width',  def: 'no' },
		'etchfoin-style-button-margin-top':  { key: 'button_margin_top',  def: '0' },
		'etchfoin-style-button-margin-bottom':{ key: 'button_margin_bottom',def: '0' },
		'etchfoin-style-success-msg':        { key: 'success_message',    def: 'Thank you for subscribing!' },
		'etchfoin-style-error-msg':          { key: 'error_message',      def: 'Something went wrong. Please try again.' },
		'etchfoin-style-success-color':      { key: 'success_color',      def: '#00a32a' },
		'etchfoin-style-error-color':        { key: 'error_color',        def: '#d63638' },
		// Popup
		'etchfoin-style-popup-enabled':      { key: 'popup_enabled',      def: 'no' },
		'etchfoin-style-popup-delay':        { key: 'popup_delay',        def: '3' },
		'etchfoin-style-popup-exit-intent':  { key: 'popup_exit_intent',  def: 'no' },
		'etchfoin-style-popup-scroll':       { key: 'popup_scroll',       def: '0' },
		'etchfoin-style-popup-show-once':    { key: 'popup_show_once',    def: 'yes' },
		'etchfoin-style-popup-cookie-days':  { key: 'popup_cookie_days',  def: '30' },
		'etchfoin-style-popup-close-overlay':{ key: 'popup_close_overlay',def: 'yes' },
		'etchfoin-style-popup-overlay-color':{ key: 'popup_overlay_color',def: 'rgba(0,0,0,0.6)' },
		'etchfoin-style-popup-hide-on-submit':{ key: 'popup_hide_on_submit',def: 'yes' }
	};

	/* ================================================================== */
	/*  INIT                                                              */
	/* ================================================================== */

	loadLists();

	$('#etchfoin-sa-refresh-lists').on('click', loadLists);
	$listSelect.on('change', onListChanged);
	$('#etchfoin-sa-save').on('click', saveForm);
	$('#etchfoin-sa-delete').on('click', deleteForm);
	$('#etchfoin-sa-copy').on('click', copyShortcode);
	$('#etchfoin-sa-check-all').on('change', toggleAll);
	$('#etchfoin-sa-save-styles').on('click', saveStyles);
	$('#etchfoin-sa-reset-styles').on('click', resetStyles);

	// Toggle popup sub-options visibility
	$('#etchfoin-style-popup-enabled').on('change', togglePopupOptions);
	function togglePopupOptions() {
		var isPopup = $('#etchfoin-style-popup-enabled').val() === 'yes';
		$('.etchfoin-sa-popup-options').toggle(isPopup);
	}
	togglePopupOptions();

	// Live preview on any style control change
	$('#etchfoin-sa-style-section').on('input change', 'input, select', function () {
		updatePreview();
		updateShortcodeFromStyles();
	});

	/* ================================================================== */
	/*  LISTS                                                             */
	/* ================================================================== */

	function loadLists() {
		$listSelect.html('<option value="">— Loading… —</option>');
		$.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action: 'etchfoin_standalone_get_lists',
			nonce:  etchfoinStandaloneAdmin.nonce
		}, function (res) {
			$listSelect.empty().append('<option value="">— Select a list —</option>');
			if (res.success && res.data) {
				$.each(res.data, function (uid, name) {
					$listSelect.append('<option value="' + esc(uid) + '">' + esc(name) + ' (' + esc(uid) + ')</option>');
				});

				// Restore previously selected list
				var saved = etchfoinStandaloneAdmin.selectedList;
				if (saved && $listSelect.find('option[value="' + saved + '"]').length) {
					$listSelect.val(saved).trigger('change');
				}
			} else {
				$listSelect.append('<option value="" disabled>No lists found — check API settings</option>');
			}
		}).fail(function () {
			$listSelect.html('<option value="" disabled>Error loading lists</option>');
		});
	}

	/* ================================================================== */
	/*  LIST SELECTION → LOAD FIELDS                                      */
	/* ================================================================== */

	function onListChanged() {
		var uid = $listSelect.val();
		if (!uid) {
			$fieldsSection.hide();
			$styleSection.hide();
			$shortcodeSection.hide();
			return;
		}

		$fieldsBody.html('<tr><td colspan="5">Loading fields…</td></tr>');
		$fieldsSection.show();
		$styleSection.hide();
		$shortcodeSection.hide();
		$status.text('');

		// Load API fields and saved config in parallel
		var apiReq = $.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action:   'etchfoin_standalone_get_list_fields',
			nonce:    etchfoinStandaloneAdmin.nonce,
			list_uid: uid
		});

		var savedReq = $.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action:   'etchfoin_standalone_get_saved_fields',
			nonce:    etchfoinStandaloneAdmin.nonce,
			list_uid: uid
		});

		$.when(apiReq, savedReq).done(function (apiRes, savedRes) {
			var api   = apiRes[0];
			var saved = savedRes[0];

			if (!api.success) {
				$fieldsBody.html('<tr><td colspan="5">Could not load fields.</td></tr>');
				return;
			}

			allFields   = api.data || [];
			savedFields = (saved.success && Array.isArray(saved.data)) ? saved.data : [];

			renderFieldsTable();
			$styleSection.show();
			loadStyles(uid);
			showShortcode(uid);
		}).fail(function () {
			$fieldsBody.html('<tr><td colspan="5">Error loading fields.</td></tr>');
		});
	}

	/* ================================================================== */
	/*  FIELDS TABLE                                                      */
	/* ================================================================== */

	function renderFieldsTable() {
		$fieldsBody.empty();

		if (!allFields.length) {
			$fieldsBody.html('<tr><td colspan="5">This list has no fields.</td></tr>');
			return;
		}

		var savedTags = {};
		savedFields.forEach(function (f) { savedTags[f.tag] = f; });

		allFields.forEach(function (field) {
			var tag      = field.tag;
			var label    = field.label || tag;
			var type     = field.type || 'text';
			var required = field.required || 'no';
			var checked  = savedTags[tag] ? ' checked' : '';
			var options  = field.options || (savedTags[tag] ? savedTags[tag].options : null) || null;

			var disabled = (type === 'email') ? ' checked disabled' : '';
			if (type === 'email') { checked = ' checked'; }

			var $row = $('<tr>')
				.attr('data-tag', tag)
				.attr('data-label', label)
				.attr('data-type', type)
				.attr('data-required', required);
			if (options) {
				$row.data('options', options);
			}
			$row.html(
				'<td class="check-column"><input type="checkbox"' + checked + disabled + ' /></td>'
				+ '<td>' + esc(label) + '</td>'
				+ '<td><code>' + esc(tag) + '</code></td>'
				+ '<td>' + esc(type) + '</td>'
				+ '<td>' + (required === 'yes' ? 'Yes' : 'No') + '</td>'
			);
			$fieldsBody.append($row);
		});
	}

	function toggleAll() {
		var checked = $(this).is(':checked');
		$fieldsBody.find('input[type="checkbox"]:not(:disabled)').prop('checked', checked);
	}

	/* ================================================================== */
	/*  SAVE / DELETE                                                     */
	/* ================================================================== */

	function saveForm() {
		var uid = $listSelect.val();
		if (!uid) return;

		var selected = [];
		$fieldsBody.find('tr').each(function () {
			var $row = $(this);
			if ($row.find('input[type="checkbox"]').is(':checked')) {
				var entry = {
					tag:      $row.data('tag'),
					label:    $row.data('label'),
					type:     $row.data('type'),
					required: $row.data('required')
				};
				var opts = $row.data('options');
				if (opts && typeof opts === 'object') {
					entry.options = opts;
				}
				selected.push(entry);
			}
		});

		if (!selected.length) {
			$status.text('Please select at least one field.').css('color', 'red');
			return;
		}

		$status.text('Saving…').css('color', '');

		$.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action:   'etchfoin_standalone_save_form',
			nonce:    etchfoinStandaloneAdmin.nonce,
			list_uid: uid,
			fields:   JSON.stringify(selected)
		}, function (res) {
			if (res.success) {
				savedFields = selected;
				$status.text('Saved!').css('color', 'green');
				showShortcode(uid);
				updatePreview();
			} else {
				$status.text('Error: ' + (res.data || 'Unknown')).css('color', 'red');
			}
		}).fail(function () {
			$status.text('Request failed.').css('color', 'red');
		});
	}

	function deleteForm() {
		var uid = $listSelect.val();
		if (!uid) return;

		if (!confirm('Delete the form configuration for this list?')) return;

		$.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action:   'etchfoin_standalone_delete_form',
			nonce:    etchfoinStandaloneAdmin.nonce,
			list_uid: uid
		}, function (res) {
			if (res.success) {
				savedFields = [];
				$fieldsBody.find('input[type="checkbox"]:not(:disabled)').prop('checked', false);
				$shortcodeSection.hide();
				$status.text('Configuration deleted.').css('color', 'green');
			}
		});
	}

	/* ================================================================== */
	/*  STYLES – LOAD / SAVE / RESET                                      */
	/* ================================================================== */

	function loadStyles(uid) {
		$.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action:   'etchfoin_standalone_get_styles',
			nonce:    etchfoinStandaloneAdmin.nonce,
			list_uid: uid
		}, function (res) {
			var saved = (res.success && res.data) ? res.data : {};
			applyStylesToControls(saved);
			togglePopupOptions();
			updatePreview();
			updateShortcodeFromStyles();
		});
	}

	function applyStylesToControls(saved) {
		$.each(styleKeys, function (elId, meta) {
			var val = (saved && saved[meta.key] !== undefined) ? saved[meta.key] : meta.def;
			$('#' + elId).val(val);
		});
	}

	function collectStyleValues() {
		var styles = {};
		$.each(styleKeys, function (elId, meta) {
			styles[meta.key] = $('#' + elId).val();
		});
		return styles;
	}

	function saveStyles() {
		var uid = $listSelect.val();
		if (!uid) return;

		var $styleStatus = $('#etchfoin-sa-style-status');
		$styleStatus.text('Saving…').css('color', '');

		$.post(etchfoinStandaloneAdmin.ajaxUrl, {
			action:   'etchfoin_standalone_save_styles',
			nonce:    etchfoinStandaloneAdmin.nonce,
			list_uid: uid,
			styles:   JSON.stringify(collectStyleValues())
		}, function (res) {
			if (res.success) {
				$styleStatus.text('Styles saved!').css('color', 'green');
				updateShortcodeFromStyles();
			} else {
				$styleStatus.text('Error: ' + (res.data || 'Unknown')).css('color', 'red');
			}
		}).fail(function () {
			$styleStatus.text('Request failed.').css('color', 'red');
		});
	}

	function resetStyles() {
		applyStylesToControls({});
		updatePreview();
		updateShortcodeFromStyles();
		$('#etchfoin-sa-style-status').text('Reset to defaults.').css('color', '#666');
	}

	/* ================================================================== */
	/*  LIVE PREVIEW                                                      */
	/* ================================================================== */

	function updatePreview() {
		var s = collectStyleValues();
		var $wrap = $('#etchfoin-sa-preview');

		// Get selected field labels for preview
		var fields = [];
		$fieldsBody.find('tr').each(function () {
			var $row = $(this);
			if ($row.find('input[type="checkbox"]').is(':checked')) {
				var entry = {
					tag:   $row.data('tag'),
					label: $row.data('label'),
					type:  $row.data('type'),
					required: $row.data('required')
				};
				var opts = $row.data('options');
				if (opts && typeof opts === 'object') {
					entry.options = opts;
				}
				fields.push(entry);
			}
		});
		if (!fields.length) {
			fields = [{ tag: 'email', label: 'Email', type: 'email', required: 'yes' }];
		}

		var showLabels = s.show_labels === 'true';
		var theme  = s.theme || 'light';
		var layout = s.layout || 'stacked';

		// Build CSS variables string
		var cssVars = ''
			+ '--etchmail-accent:' + s.accent_color + ';'
			+ '--etchmail-radius:' + s.border_radius + 'px;'
			+ '--etchmail-bg:' + s.bg_color + ';'
			+ '--etchmail-text:' + s.text_color + ';'
			+ '--etchmail-border:' + s.form_border_color + ';'
			+ '--etchmail-success:' + s.success_color + ';'
			+ '--etchmail-error:' + s.error_color + ';'
			+ '--etchmail-field-bg:' + s.field_bg + ';'
			+ '--etchmail-field-text:' + s.text_color + ';'
			+ '--etchmail-field-border:' + s.field_border_color + ';'
			+ '--etchmail-field-border-w:' + s.field_border_width + 'px;'
			+ '--etchmail-field-radius:' + s.field_border_radius + 'px;'
			+ '--etchmail-field-font-size:' + s.field_font_size + 'px;'
			+ '--etchmail-field-pad-v:' + s.field_padding_v + 'px;'
			+ '--etchmail-field-pad-h:' + s.field_padding_h + 'px;'
			+ '--etchmail-field-height:' + s.field_height + 'px;'
			+ '--etchmail-placeholder:' + s.placeholder_color + ';'
			+ '--etchmail-label-color:' + s.label_color + ';'
			+ '--etchmail-label-size:' + s.label_size + 'px;'
			+ '--etchmail-label-weight:' + s.label_weight + ';'
			+ '--etchmail-label-spacing:' + s.label_spacing + 'px;'
			+ '--etchmail-btn-bg:' + s.button_bg + ';'
			+ '--etchmail-btn-text:' + s.button_text_color + ';'
			+ '--etchmail-btn-font-size:' + s.button_font_size + 'px;'
			+ '--etchmail-btn-font-weight:' + s.button_font_weight + ';'
			+ '--etchmail-btn-pad-v:' + s.button_padding_v + 'px;'
			+ '--etchmail-btn-pad-h:' + s.button_padding_h + 'px;'
			+ '--etchmail-btn-radius:' + s.button_radius + 'px;'
			+ '--etchmail-btn-margin-top:' + s.button_margin_top + 'px;'
			+ '--etchmail-btn-margin-bottom:' + s.button_margin_bottom + 'px;'
			+ '--etchmail-max-width:' + s.max_width + 'px;'
			+ '--etchmail-form-padding:' + s.form_padding + 'px;'
			+ '--etchmail-field-gap:' + s.field_gap + 'px;';

		var html = '<div class="etchmail-form etchmail-form--' + esc(theme) + ' etchmail-form--' + esc(layout) + '" style="' + cssVars + '">';
		html += '<form class="etchmail-form__inner" onsubmit="return false;">';
		html += '<div class="etchmail-form__fields">';

		fields.forEach(function (f) {
			var inputType = mapFieldType(f.type);
			var req = (f.required === 'yes') ? ' *' : '';
			html += '<div class="etchmail-form__field etchmail-form__field--' + esc(inputType) + '">';
			if (showLabels) {
				html += '<label class="etchmail-form__label">' + esc(f.label) + (f.required === 'yes' ? ' <span class="etchmail-form__required">*</span>' : '') + '</label>';
			}
			var ph = showLabels ? '' : ' placeholder="' + esc(f.label) + req + '"';
			var fieldOpts = f.options || {};
			if (inputType === 'textarea') {
				html += '<textarea class="etchmail-form__input"' + ph + ' rows="3"></textarea>';
			} else if (inputType === 'checkbox') {
				html += '<label class="etchmail-form__checkbox-label"><input type="checkbox" class="etchmail-form__input" />' + (showLabels ? '' : ' ' + esc(f.label)) + '</label>';
			} else if (inputType === 'select') {
				html += '<select class="etchmail-form__input etchmail-form__select">';
				html += '<option value="">' + esc(f.label) + req + '</option>';
				for (var val in fieldOpts) {
					if (fieldOpts.hasOwnProperty(val)) {
						html += '<option value="' + esc(val) + '">' + esc(fieldOpts[val]) + '</option>';
					}
				}
				html += '</select>';
			} else if (inputType === 'multiselect') {
				html += '<select class="etchmail-form__input etchmail-form__select" multiple>';
				for (var val in fieldOpts) {
					if (fieldOpts.hasOwnProperty(val)) {
						html += '<option value="' + esc(val) + '">' + esc(fieldOpts[val]) + '</option>';
					}
				}
				html += '</select>';
			} else if (inputType === 'radiolist') {
				html += '<div class="etchmail-form__radio-group">';
				for (var val in fieldOpts) {
					if (fieldOpts.hasOwnProperty(val)) {
						html += '<label class="etchmail-form__radio-label"><input type="radio" name="' + esc(f.tag) + '" value="' + esc(val) + '" /> ' + esc(fieldOpts[val]) + '</label>';
					}
				}
				html += '</div>';
			} else if (inputType === 'checkboxlist') {
				html += '<div class="etchmail-form__checkbox-group">';
				for (var val in fieldOpts) {
					if (fieldOpts.hasOwnProperty(val)) {
						html += '<label class="etchmail-form__checkbox-label"><input type="checkbox" name="' + esc(f.tag) + '[]" value="' + esc(val) + '" /> ' + esc(fieldOpts[val]) + '</label>';
					}
				}
				html += '</div>';
			} else {
				html += '<input type="' + esc(inputType) + '" class="etchmail-form__input"' + ph + ' />';
			}
			html += '</div>';
		});

		html += '</div>';
		html += '<div class="etchmail-form__actions">';
		var btnFullW = s.button_full_width === 'yes' ? ' style="width:100%;"' : '';
		html += '<button type="button" class="etchmail-form__submit"' + btnFullW + '>' + esc(s.button_text || 'Subscribe') + '</button>';
		html += '</div>';
		html += '</form></div>';

		if (s.popup_enabled === 'yes') {
			html += '<div style="margin-top:10px;padding:8px 12px;background:#fff3cd;border:1px solid #ffc107;border-radius:4px;font-size:13px;color:#856404;">';
			html += '\u26A0 <strong>Popup Mode</strong> — This form will display as a popup overlay';
			if (s.popup_delay && s.popup_delay !== '0') { html += ' (delay: ' + esc(s.popup_delay) + 's)'; }
			if (s.popup_exit_intent === 'yes') { html += ' | exit-intent'; }
			if (s.popup_scroll && s.popup_scroll !== '0') { html += ' | scroll ' + esc(s.popup_scroll) + '%'; }
			if (s.popup_show_once === 'yes') { html += ' | show once'; }
			html += '</div>';
		}

		$wrap.html(html);
	}

	function mapFieldType(apiType) {
		var map = {
			text: 'text', email: 'email', url: 'url', phonenumber: 'tel',
			date: 'date', datetime: 'datetime-local', textarea: 'textarea',
			checkbox: 'checkbox', consentcheckbox: 'checkbox',
			dropdown: 'select', multiselect: 'multiselect',
			radiolist: 'radiolist', checkboxlist: 'checkboxlist',
			yearsrange: 'select', number: 'number'
		};
		return map[apiType] || 'text';
	}

	/* ================================================================== */
	/*  SHORTCODE PREVIEW                                                 */
	/* ================================================================== */

	function showShortcode(uid) {
		updateShortcodeFromStyles();
		$shortcodeSection.show();
	}

	function updateShortcodeFromStyles() {
		var uid = $listSelect.val();
		if (!uid) return;

		var s = collectStyleValues();
		var parts = ['[etchmail_form list="' + uid + '"'];

		// Only include attributes that differ from defaults
		$.each(styleKeys, function (elId, meta) {
			var val = s[meta.key];
			// skip 'list' key
			if (meta.key === 'list') return;
			if (val !== undefined && val !== meta.def && val !== '') {
				parts.push(meta.key + '="' + val + '"');
			}
		});
		parts[parts.length - 1] += ']';

		$shortcodeCode.text(parts.join(' '));
	}

	function copyShortcode() {
		var text = $shortcodeCode.text();
		if (navigator.clipboard) {
			navigator.clipboard.writeText(text).then(function () {
				$('#etchfoin-sa-copy').text('Copied!');
				setTimeout(function () { $('#etchfoin-sa-copy').text('Copy'); }, 1500);
			});
		}
	}

	/* ================================================================== */
	/*  UTILS                                                             */
	/* ================================================================== */

	function esc(str) {
		if (typeof str !== 'string') str = String(str);
		var el = document.createElement('span');
		el.textContent = str;
		return el.innerHTML;
	}

})(jQuery);
