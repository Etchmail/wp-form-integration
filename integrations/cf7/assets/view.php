<?php
/**  Etchmail × CF7 – editor-panel markup
 *
 *  todo rebase this page, JS should handle the entire page. Some values are filled in via php.
 *
 */
defined( 'ABSPATH' ) || exit;

$formid  = $this->form->id;
$enabled = (bool) $this->enabled;

// --- etch types --- //
// text textarea  multiselect date phonenumber checkbox consentcheckbox url email
// dropdown radiolist  checkboxlist
//
$emfi_compat = [
	//  CF7 basetype     ⇒  Etchmail field-types allowed
	'text'       => [ 'text' ],
	'email'      => [ 'text' ],
	'url'        => [ 'text', 'url' ],
	'tel'        => [ 'text', 'phonenumber' ],
	'number'     => [ 'text', 'phonenumber' ],
	'date'       => [ 'date' ],
	'textarea'   => [ 'textarea' ],
	'select'     => [ 'dropdown' ],
	'checkbox'   => [ 'multiselect' ],
	'radio'      => [ 'radiolist', 'text' ],
	'acceptance' => [ 'consentcheckbox', 'checkbox' ],
];
echo '<script>window.emfiCompat = ' .
     wp_json_encode( $emfi_compat ) .
     ';</script>';

?>
<div class="etchmail-settings-wrap">
    <div id="emfi-notice" style="display:none;margin-top:15px;"></div>


    <!--    <div class="etchmail-header">-->
    <!--       <img src="" alt="Etchmail" class="etchmail-logo">-->
    <!--        <h2>Etchmail Integration</h2>-->
    <!--        <p class="description">Connect this form to your Etchmail mailing list</p>-->
    <!--    </div>-->

    <div class="etchmail-card">
        <div class="etchmail-card-header">
            <label class="etchmail-toggle">
                <input type="checkbox" name="etchmail_enabled" value="1" <?php checked( $enabled ); ?> >
                <span class="etchmail-toggle-slider"></span>
                <span class="etchmail-toggle-label">Enable Etchmail integration</span>
            </label>

            <span class="etchmail-formid">
		        <small>Form ID: <?php echo (int) $this->form->id; ?></small>
	        </span>
        </div>

        <div id="etchmail-settings" class="etchmail-card-body" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
            <div class="etchmail-section">
                <h3 class="etchmail-section-title">Mailing List</h3>
                <div class="etchmail-field-group">
                    <div class="etchmail-select-wrapper">
                        <select name="etchmail_list_id" id="etchmail_list_id" class="etchmail-select">
                            <option value="">Select a mailing list...</option>
                        </select>
<!--                        <button type="button" id="load-etchmail-lists"-->
<!--                                class="etchmail-button etchmail-button-secondary">-->
<!--                            <span class="dashicons dashicons-update"></span> Refresh Lists-->
<!--                        </button>-->
                    </div>
                    <p class="etchmail-field-description">Choose which Etchmail list will receive submissions from this
                        form</p>
                </div>
            </div>

            <div id="etchmail-mapping" class="etchmail-section"
                 style="<?php echo ( $this->list_uid && $enabled ) ? '' : 'display:none;'; ?>">
                <h3 class="etchmail-section-title">Field Mapping</h3>
                <p class="etchmail-section-description">Match your form fields to Etchmail list fields</p>
                <p class="etchmail-section-description">
                    Required Etchmail field(s):
					<?php
					$required_fields = array();
					foreach ( $this->list_fields as $emfi ) {
						if ( isset( $emfi['required'] ) && $emfi['required'] === "yes" ) {
							$required_fields[] = esc_html( $emfi['label'] );
						}
					}

					if ( empty( $required_fields ) ) {
						echo 'None';
					} else {
						echo implode( ', ', $required_fields );
					}
					?>
                </p>


                <div class="etchmail-mapping-table">
                    <div class="etchmail-mapping-header">
                        <div class="etchmail-mapping-col">Contact Form Field</div>
                        <div class="etchmail-mapping-col">Etchmail Field</div>
                        <div class="etchmail-mapping-col"></div>
                    </div>

                    <div class="etchmail-mapping-body">
						<?php foreach ( $this->form_fields as $tag ) :
							if ( empty( $tag->name ) ) {
								continue;
							} ?>
                            <div class="etchmail-mapping-row">
                                <div class="etchmail-mapping-col">
                                    <span class="etchmail-field-name"><?php echo esc_html( $tag->name ); ?></span>
                                    <span class="etchmail-field-type"><?php echo esc_html( $tag->basetype ); ?></span>
                                </div>
                                <div class="etchmail-mapping-col">
                                    <select name="emfi_field_map[<?php echo esc_attr( $tag->name ); ?>]"
                                            id="emfi-map-<?php echo esc_attr( $tag->name ); ?>"
                                            class="emfi-map-select etchmail-select"
                                            data-cf7-field="<?php echo esc_attr( $tag->name ); ?>">
                                        <option value="">— Don't map —</option>
										<?php
										$cf_type      = $tag->basetype ?: 'text';
										$allowed_tags = $emfi_compat[ $cf_type ] ?? [];
										foreach ( $this->list_fields as $emfi ) {
											if ( ! in_array(
												$emfi['type'],
												$emfi_compat[ $cf_type ] ?? [ 'text' ],
												true
											) ) {
												continue;
											}
											?>
                                            <option value="<?php echo esc_attr( $emfi['tag'] ); ?>"
												<?php selected( $this->mapped_fields[ $tag->name ] ?? '', $emfi['tag'] ); ?>>
												<?php
												echo esc_html( $emfi['label'] );
												echo $emfi['required'] === 'yes' ? ' (required)' : '';
												?>
                                            </option>
											<?php
										}
										?>
                                    </select>
                                </div>
                                <div class="etchmail-mapping-col etchmail-compatibility">
                                    <span class="etchmail-info-icon dashicons dashicons-info"></span>
                                    <div class="etchmail-tooltip">
                                        <strong>Compatible Etchmail field types:</strong><br>
										<?php echo esc_html( implode( ', ', $allowed_tags ) ); ?>
                                    </div>
                                </div>
                            </div>
						<?php endforeach; ?>
                    </div>
                </div>
                <div id="emfi-problems" class="etchmail-alert" style="display:none;margin: 10px 0 10px 0"></div>
                <div class="etchmail-actions">
                    <button type="button" id="save-emfi-settings" class="etchmail-button etchmail-button-primary">
                        <span class="dashicons dashicons-yes"></span> Save Field Mapping
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    jQuery(function ($) {
        /* ─────────────── Globals & DOM refs ─────────────── */
        let plugin_enabled = <?php echo $enabled ? 'true' : 'false'; ?>;
        const $checkbox = $('input[name="etchmail_enabled"]');
        const $settings = $('#etchmail-settings');
        const $mappingBox = $('#etchmail-mapping');
        const $tab = $('#etchmail-panel-tab');
        const $problems = $('#emfi-problems');
        const $listSelect = $('#etchmail_list_id');
        const $loadLists = $('#load-etchmail-lists');
        const $saveBtn = $('#save-emfi-settings');
        const formId = <?php echo (int) $formid; ?>;
        const nonce = '<?php echo wp_create_nonce( 'etchmail_nonce' ); ?>';

        /* PHP → JS data blobs */
        let list_fields = <?php echo wp_json_encode( $this->list_fields ); ?>;
        let form_fields = <?php echo wp_json_encode( $this->form_fields ); ?>;
        let mapped_fields = <?php echo wp_json_encode( $this->mapped_fields ); ?>;

        /* ─────────────── Notice helpers ─────────────── */
        function showNotice(msg, ok = true) {
            const $n = $('#emfi-notice')
                .hide()
                .removeClass()
                .addClass(ok ? 'notice notice-success' : 'notice notice-error')
                .html(`<p>${msg}</p>`)
                .fadeIn();
            setTimeout(() => $n.fadeOut(), 4000);
        }

        function flagEtchmailTab(show) {
            if (!$tab.length) return;
            $tab.find('.icon-in-circle').remove();
            if (show) {
                $('<span>', {class: 'icon-in-circle', 'aria-hidden': true, text: '!'})
                    .appendTo($tab.find('a'));
            }
        }

        /* ─────────────── Stale-mapping checker ─────────────── */
        function misConfigCheck(mapped, forms, lists) {
            const formNames = new Set(forms.map(f => f.name));
            const listTags = new Set(lists.map(l => l.tag));
            const issues = [];

            Object.entries(mapped).forEach(([cf7, etch]) => {
                if (!formNames.has(cf7))
                    issues.push(`Form field <strong>${cf7}</strong> no longer exists in the form.`);
                if (!listTags.has(etch))
                    issues.push(`Etchmail field <strong>${etch}</strong> no longer exists in the list.`);
            });
            return issues;
        }

        function runMisConfigCheck() {
            if (!plugin_enabled) {
                flagEtchmailTab(false);
                $problems.hide().empty();
                return;
            }

            const problems = misConfigCheck(mapped_fields, form_fields, list_fields);

            if (problems.length) {
                flagEtchmailTab(true);

                $problems
                    .removeClass().addClass('etchmail-alert-error')
                    .html('<h4>Configuration Issues Detected</h4><ul><li>' + problems.join('</li><li>') + '</li></ul>')
                    .show();

                showNotice('Etchmail mapping has issues that need attention.', false);
            } else {
                flagEtchmailTab(false);
                $problems.hide().empty();
            }
        }

        /* ─────────────── Checkbox toggle ─────────────── */
        function toggleSettings() {
            plugin_enabled = $checkbox.is(':checked');
            $settings.toggle(plugin_enabled);
            if (!plugin_enabled) {
                $mappingBox.hide();
            } else if ($listSelect.val()) {
                handleListChange();
            }
            $.post(ajaxurl, {
                action: 'emfi_save_cf7_enabled',
                nonce,
                form_id: formId,
                enabled: plugin_enabled ? '1' : '0'
            });
            runMisConfigCheck();
        }

        $checkbox.on('change', toggleSettings);

        /* ─────────────── Load list dropdown ─────────────── */
        function loadLists() {
            $loadLists.prop('disabled', true).find('.dashicons').addClass('etchmail-spin');
            $.post(ajaxurl, {
                action: 'emfi_get_lists',
                form_id: formId,
                list_uid: $listSelect.val(),
                enabled: plugin_enabled ? '1' : '0',
                nonce
            }, res => {
                $loadLists.prop('disabled', false).find('.dashicons').removeClass('etchmail-spin');

                if (!res.success) {
                    showNotice('Failed to load lists: ' + res.data, false);
                    return;
                }

                $listSelect.empty().append('<option value="">Select a mailing list...</option>');
                res.data.forEach(l => {
                    $listSelect.append(
                        $('<option>').val(l.list_uid).text(l.name)
                            .prop('selected', l.list_uid === '<?php echo esc_js( $this->list_uid ); ?>')
                    );
                });

                if (res.data.length === 0) {
                    showNotice('No mailing lists found in your Etchmail account.', false);
                }
            }, 'json').fail(() => {
                $loadLists.prop('disabled', false).find('.dashicons').removeClass('etchmail-spin');
                showNotice('Network error while loading lists. Please try again.', false);
            });
        }

        $loadLists.on('click', loadLists);

        function buildSelect($sel, cfType) {
            const compat = window.emfiCompat || {};
            const allowed = compat[cfType] || ['text'];           // fallback
            const current = $sel.val();
            $sel.empty().append('<option value="">— Don’t map —</option>');

            list_fields.forEach(lf => {
                if (!allowed.includes(lf.type)) return;
                $('<option>')
                    .val(lf.tag)
                    .text(`${lf.label}${lf.required === 'yes' ? ' (required)' : ''}`)
                    .prop('selected', current === lf.tag)
                    .appendTo($sel);
            });
        }

        /* ─────────────── When a list is chosen ─────────────── */
        function handleListChange() {
            if (!$listSelect.val()) {
                $mappingBox.hide();
                return;
            }

            $mappingBox.addClass('etchmail-loading');

            $.post(ajaxurl, {
                action: 'emfi_get_list_fields',
                nonce: nonce,
                form_id: formId,
                list_uid: $listSelect.val()
            }, res => {
                $mappingBox.removeClass('etchmail-loading');

                if (!res.success) {
                    showNotice(res.data, false);
                    return;
                }

                ({list_fields, form_fields, saved_map: mapped_fields} = res.data);
                renderMappingRows();
                $mappingBox.show();
                runMisConfigCheck();
            }, 'json');
        }

        /* ---------- on list-change: (re)build each row ---------- */
        function renderMappingRows() {
            const $body = $('.etchmail-mapping-body').empty();

            form_fields.forEach(f => {
                if (!f.name) return;

                const cfType = f.basetype || 'text';

                const $row = $('<div class="etchmail-mapping-row">').append(
                    $('<div class="etchmail-mapping-col">').html(
                        `<span class="etchmail-field-name">${f.name}</span>
					 <span class="etchmail-field-type">${cfType}</span>`
                    )
                );

                const $sel = $('<select>', {
                    name: `emfi_field_map[${f.name}]`,
                    class: 'emfi-map-select etchmail-select',
                    'data-cf7-field': f.name,
                    'data-type': cfType
                });
                buildSelect($sel, cfType);
                if (mapped_fields[f.name]) $sel.val(mapped_fields[f.name]);

                $row.append($('<div class="etchmail-mapping-col">').append($sel));

                const compat = window.emfiCompat?.[cfType] || ['text'];
                $row.append(`
				<div class="etchmail-mapping-col etchmail-compatibility">
					<span class="etchmail-info-icon dashicons dashicons-info"></span>
					<div class="etchmail-tooltip">
						<strong>Compatible Etchmail types:</strong><br>${compat.join(', ')}
					</div>
				</div>`);

                $body.append($row);
            });

            refreshDropdownDisabling();
        }


        $listSelect.on('change', handleListChange);

        /* ─────────────── Save mapping ─────────────── */
        $saveBtn.on('click', () => {
            const mapped = {}, missing = [];
            $('.emfi-map-select').each(function () {
                const cf = $(this).data('cf7-field'), dest = $(this).val();
                if (dest) mapped[cf] = dest;
                else if ($(this).find('option:selected').text().includes('(required)'))
                    missing.push($(this).find('option:selected').text().replace(/ \(required\)$/, ''));
            });

            if (missing.length) {
                showNotice('Please map all required fields: ' + missing.join(', '), false);
                return;
            }

            $saveBtn.prop('disabled', true).find('.dashicons').addClass('etchmail-spin');

            $.post(ajaxurl, {
                action: 'emfi_save_cf7_settings',
                nonce,
                form_id: formId,
                enabled: plugin_enabled ? '1' : '0',
                list_uid: $listSelect.val(),
                mapped_fields: mapped
            }, res => {
                $saveBtn.prop('disabled', false).find('.dashicons').removeClass('etchmail-spin');

                if (res.success) {
                    mapped_fields = mapped;
                    runMisConfigCheck();
                    showNotice('Field mapping saved successfully!');
                } else {
                    showNotice('Error: ' + res.data, false);
                }
            }, 'json').fail(() => {
                $saveBtn.prop('disabled', false).find('.dashicons').removeClass('etchmail-spin');
                showNotice('Failed to save settings. Please try again.', false);
            });
        });

        function refreshDropdownDisabling() {
            const selected = new Set($('.emfi-map-select').map(function () {
                return $(this).val() || null;
            }).get());

            $('.emfi-map-select').each(function () {
                const cur = $(this).val();
                $(this).find('option').each(function () {
                    const v = $(this).val();
                    $(this).prop('disabled', v && v !== cur && selected.has(v));
                });
            });
        }


        $(document).on('change', '.emfi-map-select', refreshDropdownDisabling);

        /* ─────────────── Init ─────────────── */
        toggleSettings();        // sets visibility & re-runs check
        loadLists();             // fills dropdown
        if ($listSelect.val()) $listSelect.trigger('change');
        /* init */
        renderMappingRows();
        refreshDropdownDisabling();
        $(document).on('change', '.emfi-map-select', refreshDropdownDisabling);
        runMisConfigCheck();     // initial run (if enabled)

    });
</script>


<style>
    .etchmail-settings-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    .etchmail-header {
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .etchmail-header h2 {
        margin: 10px 0 5px;
        color: #23282d;
        font-size: 24px;
        font-weight: 600;
    }

    .etchmail-header .description {
        margin: 0;
        color: #666;
        font-size: 14px;
    }

    .etchmail-logo {
        height: 32px;
        width: auto;
        margin-right: 10px;
        vertical-align: middle;
    }

    .etchmail-card {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .etchmail-card-header {
        display:flex;
        align-items:center;
        padding: 15px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
    }

    .etchmail-card-body {
        padding: 20px;
    }

    .etchmail-section {
        margin-bottom: 25px;
    }

    .etchmail-formid{
        margin-left:auto;           /* pushes the ID all the way to the right */
        color:#666;
        font-size:12px;
    }

    .etchmail-section-title {
        margin: 0 0 15px;
        font-size: 18px;
        font-weight: 600;
        color: #23282d;
    }

    .etchmail-section-description {
        margin: -10px 0 15px;
        color: #666;
        font-size: 13px;
    }

    .etchmail-field-group {
        margin-bottom: 20px;
    }

    .etchmail-select-wrapper {
        display: flex;
        gap: 10px;
        margin-bottom: 5px;
    }

    .etchmail-select {
        flex: 1;
        min-width: 200px;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
        background-color: #fff;
        color: #32373c;
        font-size: 14px;
        line-height: 1.4;
        height: 36px;
    }

    .etchmail-field-description {
        margin: 5px 0 0;
        color: #666;
        font-size: 13px;
        font-style: italic;
    }

    .etchmail-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
        background: #f7f7f7;
        color: #23282d;
        font-size: 13px;
        line-height: 1.4;
        cursor: pointer;
        transition: all 0.2s;
        height: 36px;
    }

    .etchmail-button:hover {
        background: #f0f0f0;
        border-color: #bbb;
    }

    .etchmail-button-primary {
        background: #007cba;
        border-color: #007cba;
        color: #fff;
    }

    .etchmail-button-primary:hover {
        background: #006ba1;
        border-color: #006ba1;
    }

    .etchmail-button-secondary {
        background: #f0f0f0;
        border-color: #ccc;
    }

    .etchmail-button-secondary:hover {
        background: #e0e0e0;
        border-color: #999;
    }

    .etchmail-button .dashicons {
        margin-right: 5px;
        font-size: 16px;
    }

    .etchmail-toggle {
        position: relative;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
    }

    .etchmail-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .etchmail-toggle-slider {
        position: relative;
        width: 50px;
        height: 24px;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
        margin-right: 10px;
    }

    .etchmail-toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    .etchmail-toggle input:checked + .etchmail-toggle-slider {
        background-color: #007cba;
    }

    .etchmail-toggle input:checked + .etchmail-toggle-slider:before {
        transform: translateX(26px);
    }

    .etchmail-toggle-label {
        font-weight: 600;
        font-size: 15px;
    }

    .etchmail-mapping-table {
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
    }

    .etchmail-mapping-header {
        display: flex;
        background: #f8f9fa;
        font-weight: 600;
        padding: 10px 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .etchmail-mapping-body {
        max-height: 1000px;
        overflow-y: auto;
    }

    .etchmail-mapping-row {
        display: flex;
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        align-items: center;
    }

    .etchmail-mapping-row:last-child {
        border-bottom: none;
    }

    .etchmail-mapping-col {
        flex: 1;
        padding: 0 5px;
    }

    .etchmail-field-name {
        font-weight: 500;
        display: block;
    }

    .etchmail-field-type {
        font-size: 12px;
        color: #666;
        display: block;
        margin-top: 2px;
    }

    .etchmail-actions {
        margin-top: 20px;
        text-align: right;
    }

    .etchmail-notice {
        padding: 10px 15px;
        margin: 0 0 20px;
        border-radius: 4px;
        border-left: 4px solid transparent;
    }

    .etchmail-notice-success {
        background-color: #f0f9eb;
        border-left-color: #67c23a;
    }

    .etchmail-notice-error {
        background-color: #fef0f0;
        border-left-color: #f56c6c;
    }

    .etchmail-alert {
        padding: 15px;
        margin: 0 0 20px;
        border-radius: 4px;
    }

    .etchmail-alert-error {
        background-color: #fef0f0;
        padding: 15px;
        border: 1px solid #f56c6c;
    }

    .etchmail-alert h4 {
        margin: 0 0 10px;
        color: #f56c6c;
        font-size: 16px;
    }

    .etchmail-alert ul {
        margin: 0;
        padding-left: 20px;
    }

    .etchmail-alert li {
        margin-bottom: 5px;
    }

    @keyframes etchmail-spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .etchmail-compatibility {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        min-width: 30px;
    }

    .etchmail-info-icon {
        color: #666;
        cursor: help;
        font-size: 18px;
        transition: color 0.2s;
    }

    .etchmail-info-icon:hover {
        color: #0073aa;
    }

    .etchmail-tooltip {
        display: none;
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateX(-100%);
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 10px;
        width: 200px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        z-index: 100;
        font-size: 13px;
        line-height: 1.4;
    }

    .etchmail-compatibility:hover .etchmail-tooltip {
        display: block;
    }

    /* Adjust the mapping table columns */
    .etchmail-mapping-header .etchmail-mapping-col:nth-child(3),
    .etchmail-mapping-row .etchmail-mapping-col:nth-child(3) {
        flex: 0 0 30px;
        text-align: center;
    }

    /* Responsive adjustment */
    @media (max-width: 768px) {
        .etchmail-tooltip {
            left: auto;
            right: 100%;
        }
    }
</style>