<?php
/**  Etchmail × CF7 – editor-panel markup  */
defined( 'ABSPATH' ) || exit;

$formid  = $this->form->id;
$enabled = (bool) $this->enabled;
?>

<div>
    <div id="emfi-notice" style="display:none;margin-top:15px;"></div>
    <div id="emfi-problems" style="display:none;margin-top:15px;"></div>
    <br>
    <h2>Etchmail Integration Settings</h2>

    <fieldset>

        <label>
            <input type="checkbox" name="etchmail_enabled"
                   value="1" <?php checked( $enabled ); ?> >
            Enable Etchmail integration for this form
        </label>
    </fieldset>

    <br>

    <fieldset id="etchmail-settings" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
        <h3>Etchmail List Selection</h3>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="etchmail_list_id">Mailing List</label></th>
                <td>
                    <select name="etchmail_list_id" id="etchmail_list_id"></select>
                    <button type="button" id="load-etchmail-lists" class="button">Refresh</button>
                    <p class="description">Select the Etchmail list where subscribers will be added.</p>
                </td>
            </tr>
        </table>
    </fieldset>

    <fieldset id="etchmail-mapping"
              style="<?php echo ( $this->list_uid && $enabled ) ? '' : 'display:none;'; ?>">
        <table class="form-table" id="emfi-mapping-table">
            <thead>
            <tr>
                <th>Contact Form Field</th>
                <th>Map to Etchmail Field</th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $this->form_fields as $tag ) :
				if ( empty( $tag->name ) ) {
					continue;
				} ?>
                <tr>
                    <td><code><?php echo esc_html( $tag->name ); ?></code></td>
                    <td>
                        <select
                                name="emfi_field_map[<?php echo esc_attr( $tag->name ); ?>]"
                                id="emfi-map-<?php echo esc_attr( $tag->name ); ?>"
                                class="emfi-map-select"
                                data-cf7-field="<?php echo esc_attr( $tag->name ); ?>">
                            <option value="">— None —</option>
							<?php foreach ( $this->list_fields as $emfi ) : ?>
                                <option value="<?php echo esc_attr( $emfi['tag'] ); ?>"
									<?php selected( $this->mapped_fields[ $tag->name ] ?? '', $emfi['tag'] ); ?>>
									<?php echo esc_html( $emfi['label'] ); ?>
									<?php echo $emfi['required'] === 'yes' ? ' *' : ''; ?>
                                </option>
							<?php endforeach; ?>
                        </select>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

        <button type="button" id="save-emfi-settings" class="button-primary">Save Mapping</button>
    </fieldset>
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
                .html(`<p>Etchmail Integration: ${msg}</p>`)
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
                    issues.push(`Form field “${cf7}” no longer exists in the form.`);
                if (!listTags.has(etch))
                    issues.push(`Etchmail field “${etch}” no longer exists in the list.`);
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
                    .removeClass().addClass('notice notice-error')
                    .html('<p>Etchmail Intergation: ' + problems.join('<br>') + '</p>')
                    .show();

                showNotice('Etchmail mapping has issues. See details in the Etchmail tab below.', false);
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
                nonce, form_id: formId,
                enabled: plugin_enabled ? '1' : '0'
            });
            runMisConfigCheck();
        }

        $checkbox.on('change', toggleSettings);


        /* ─────────────── Load list dropdown ─────────────── */
        function loadLists() {
            $.post(ajaxurl, {action: 'emfi_get_lists', nonce}, res => {
                if (!res.success) {
                    console.error(res);
                    return;
                }
                $listSelect.empty().append('<option value="">Select a list…</option>');
                res.data.forEach(l => {
                    $listSelect.append(
                        $('<option>').val(l.list_uid).text(l.name)
                            .prop('selected', l.list_uid === '<?php echo esc_js( $this->list_uid ); ?>')
                    );
                });
            }, 'json');
        }

        $loadLists.on('click', loadLists);

        /* ─────────────── When a list is chosen ─────────────── */
        function handleListChange() {
            const listUid = $listSelect.val();
            $.post(ajaxurl, {
                action: 'emfi_save_cf7_list', nonce, form_id: formId,
                list_uid: listUid, enabled: plugin_enabled ? '1' : '0'
            });

            if (!listUid) {
                $mappingBox.hide();
                return;
            }

            $.post(ajaxurl, {
                action: 'emfi_get_list_fields', nonce,
                form_id: formId, list_uid: listUid
            }, res => {
                if (!res.success) {
                    showNotice(res.data, false);
                    return;
                }

                ({list_fields, form_fields, saved_map: mapped_fields} = res.data);
                const $tbody = $('#emfi-mapping-table tbody').empty();

                form_fields.forEach(f => {
                    if (!f.name) return;
                    const $row = $('<tr>').append(
                        $('<td>').html(`<code>${f.name}</code>`)
                    );
                    const $sel = $('<select>', {
                        name: `emfi_field_map[${f.name}]`,
                        class: 'emfi-map-select',
                        'data-cf7-field': f.name
                    }).append('<option value="">— None —</option>');
                    list_fields.forEach(lf => {
                        $sel.append($('<option>')
                            .val(lf.tag)
                            .text(lf.label + (lf.required === 'yes' ? ' *' : ''))
                            .prop('selected', mapped_fields[f.name] === lf.tag));
                    });
                    $row.append($('<td>').append($sel));
                    $tbody.append($row);
                });
                $mappingBox.show();
                runMisConfigCheck();
            }, 'json');
        }

        $listSelect.on('change', handleListChange);

        /* ─────────────── Save mapping ─────────────── */
        $saveBtn.on('click', () => {
            const mapped = {}, missing = [];
            $('.emfi-map-select').each(function () {
                const cf = $(this).data('cf7-field'), dest = $(this).val();
                if (dest) mapped[cf] = dest;
                else if ($(this).find('option:selected').text().endsWith(' *'))
                    missing.push($(this).find('option:selected').text().replace(/ \*$/, ''));
            });
            if (missing.length) {
                showNotice('Required fields not mapped: ' + missing.join(', '), false);
                return;
            }

            $.post(ajaxurl, {
                action: 'emfi_save_cf7_settings', nonce,
                form_id: formId, enabled: plugin_enabled ? '1' : '0',
                list_uid: $listSelect.val(), mapped_fields: mapped
            }, res => {
                if (res.success) {
                    mapped_fields = mapped;
                    runMisConfigCheck();
                }
                showNotice(res.success ? 'Settings saved.' : res.data, res.success);
            }, 'json');
        });

        /* ─────────────── Init ─────────────── */
        toggleSettings();        // sets visibility & re-runs check
        loadLists();             // fills dropdown
        if ($listSelect.val()) $listSelect.trigger('change');
        runMisConfigCheck();     // initial run (if enabled)
    });
</script>
