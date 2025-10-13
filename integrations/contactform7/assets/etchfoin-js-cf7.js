jQuery(function ($) {
    /* ----------------- DOM refs ----------------- */
    const etchfoin_cf7_$settingsWrap = $('#etchfoin-settings');            // wraps list + mapping UI
    const etchfoin_cf7_$notice       = $('#etchfoin-notice');
    const etchfoin_cf7_$listSelect   = $('#etchfoin_list_id');
    const etchfoin_cf7_$tableBody    = $('#etchfoin-mapping-body');        // rows go here
    const etchfoin_cf7_$table        = $('#etchfoin-mapping-table');       // table wrapper
    const etchfoin_cf7_$saveDiv      = $('#etchfoin-save-button');         // wrapper for Save button

    /* Will be created dynamically just above the table */
    let   etchfoin_cf7_$validation   = null;

    /* ----------------- State ----------------- */
    let etchfoin_cf7_plugin_enabled = false;
    let etchfoin_cf7_selectedList   = '';
    let etchfoin_cf7_mapped         = {};   // { cf7_name: etch_tag }
    let etchfoin_cf7_cf7Tags        = [];   // [{ name, basetype }]
    let etchfoin_cf7_listFields     = [];   // [{ tag, label, type, required }]
    let etchfoin_cf7_dirty          = false; // unsaved changes flag

    /* Validation state */
    let etchfoin_cf7_isValid        = true;
    let etchfoin_cf7_missingRequired = [];   // array of Etch field tags not selected by any CF7 field

    /* ----------------- Constants ----------------- */
    // Do not change these mappings (types ↔ types)
    const etchfoin_cf7_compatMap = {
        text:       ['text'],
        email:      ['text'],
        url:        ['text', 'url'],
        tel:        ['text', 'phonenumber'],
        number:     ['text', 'phonenumber'],
        date:       ['date'],
        textarea:   ['textarea'],
        select:     ['dropdown'],
        checkbox:   ['multiselect', 'checkbox'],
        radio:      ['radiolist', 'text'],
        acceptance: ['consentcheckbox', 'checkbox'],
    };

    /* ----------------- Utils ----------------- */
    function etchfoin_cf7_getFormId() {
        const $el = $('.etchfoin-formid');
        if (!$el.length) return null;
        const id = parseInt($el.data('form-id'), 10);
        return Number.isInteger(id) ? id : null;
    }

    function etchfoin_cf7_showNotice(msg, ok = true) {
        etchfoin_cf7_$notice
            .hide()
            .removeClass()
            .addClass(ok ? 'notice notice-success' : 'notice notice-error')
            .html(`<p>${msg}</p>`)
            .fadeIn();
        setTimeout(() => etchfoin_cf7_$notice.fadeOut(), 4000);
    }

    function etchfoin_cf7_post(action, data) {
        return $.post(
            ajaxurl,
            Object.assign({ action, nonce: etchfoinDataCF7.nonce }, data || {}),
            null,
            'json'
        );
    }

    function etchfoin_cf7_setDirty(isDirty = true) {
        etchfoin_cf7_dirty = !!isDirty;
        etchfoin_cf7_updateSaveButton();
    }

    function etchfoin_cf7_updateSaveButton() {
        const $btn = $('#etchfoin-save-mappings');
        if (!$btn.length) return;

        $btn.text(etchfoin_cf7_dirty ? 'Save field mappings' : 'Mappings saved');

        const shouldEnable = etchfoin_cf7_dirty && etchfoin_cf7_isValid;
        $btn.prop('disabled', !shouldEnable);
    }

    function ensureValidationBox() {
        if (!etchfoin_cf7_$validation) {
            etchfoin_cf7_$validation = $('<div id="etchfoin-validation" class="notice" style="display:none;"></div>');
            etchfoin_cf7_$validation.insertBefore(etchfoin_cf7_$table);
        }
        return etchfoin_cf7_$validation;
    }

    // hide/show mapping UI if no list selected
    function etchfoin_cf7_toggleMappingVisibility() {
        const hasList = !!(etchfoin_cf7_selectedList && etchfoin_cf7_selectedList.length);
        ensureValidationBox(); // make sure it exists

        if (hasList) {
            etchfoin_cf7_$table.show();
            etchfoin_cf7_$saveDiv.show();
            $('#etchfoin-save-mappings').show();
            $('#etchfoin-mapping-table').show();

            // Show banner only when invalid
            if (!etchfoin_cf7_isValid) {
                etchfoin_cf7_$validation.show();
            } else {
                etchfoin_cf7_$validation.hide();
            }
        } else {
            etchfoin_cf7_$table.hide();
            etchfoin_cf7_$saveDiv.hide();
            $('#etchfoin-save-mappings').hide();
            $('#etchfoin-mapping-table').hide();
            etchfoin_cf7_$validation.hide();
        }
    }

    /* ----------------- Enabled UI gate ----------------- */
    const etchfoin_cf7_$toggle = $('input[name="etchfoin_enabled"]');
    function etchfoin_cf7_applyEnabledUI() {
        if (etchfoin_cf7_plugin_enabled) {
            etchfoin_cf7_$settingsWrap.show();
            etchfoin_cf7_$settingsWrap.find('select,button,input,textarea')
                .prop('disabled', false).attr('aria-disabled', 'false');
            $('#etchfoin-save-mappings').show();
        } else {
            etchfoin_cf7_$settingsWrap.hide();
            etchfoin_cf7_$settingsWrap.find('select,button,input,textarea')
                .prop('disabled', true).attr('aria-disabled', 'true');
            $('#etchfoin-save-mappings').hide();
        }
        if (etchfoin_cf7_$toggle.length) {
            etchfoin_cf7_$toggle.prop('checked', !!etchfoin_cf7_plugin_enabled);
        }
    }

    if (etchfoin_cf7_$toggle.length) {
        etchfoin_cf7_$toggle.on('change', function () {
            const newEnabled = $(this).is(':checked');
            etchfoin_cf7_post('etchfoin_save_cf7_enabled', {
                form_id: etchfoin_cf7_getFormId(),
                enabled: newEnabled
            }).then(res => {
                if (!res.success) {
                    etchfoin_cf7_$toggle.prop('checked', !newEnabled);
                    etchfoin_cf7_showNotice(res.data || 'Could not save your changes', false);
                    return;
                }
                etchfoin_cf7_plugin_enabled = !!res.data.enabled;
                etchfoin_cf7_applyEnabledUI();
                etchfoin_cf7_showNotice(
                    etchfoin_cf7_plugin_enabled ? 'Etchmail integration enabled.' : 'Etchmail integration disabled.',
                    true
                );
            }).catch(() => {
                etchfoin_cf7_$toggle.prop('checked', !newEnabled);
                etchfoin_cf7_showNotice('Network error saving setting.', false);
            });
        });
    }

    /* ----------------- Fetchers ----------------- */
    function etchfoin_cf7_fetchSettings(formId) {
        return etchfoin_cf7_post('etchfoin_get_cf7_settings', { form_id: formId })
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Failed settings');
                etchfoin_cf7_plugin_enabled = !!res.data.enabled;
                etchfoin_cf7_selectedList   = res.data.list_uid || '';
                etchfoin_cf7_mapped         = res.data.mapped_fields || {};
                etchfoin_cf7_setDirty(false);
            });
    }

    function etchfoin_cf7_fetchFormTags(formId) {
        return etchfoin_cf7_post('etchfoin_get_cf7_form_tags', { form_id: formId })
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Failed form tags');
                etchfoin_cf7_cf7Tags = res.data.tags || [];
            });
    }

    function etchfoin_cf7_fetchLists() {
        return etchfoin_cf7_post('etchfoin_get_cf7_lists', { form_id: etchfoin_cf7_getFormId() })
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Failed lists');
                const lists = res.data.lists || res.data; // support older shape
                etchfoin_cf7_$listSelect
                    .empty()
                    .append('<option value="">Select a mailing list...</option>');
                (lists || []).forEach(l => {
                    etchfoin_cf7_$listSelect.append(
                        $('<option>').val(l.list_uid).text(l.name)
                    );
                });
                if (etchfoin_cf7_selectedList) {
                    etchfoin_cf7_$listSelect.val(etchfoin_cf7_selectedList);
                }
                if (!lists || lists.length === 0) {
                    etchfoin_cf7_showNotice('No mailing lists found in your Etchmail account.', false);
                }
                etchfoin_cf7_selectedList = etchfoin_cf7_$listSelect.val() || '';
                etchfoin_cf7_toggleMappingVisibility();
            });
    }

    function etchfoin_cf7_fetchListFields(listUid) {
        if (!listUid) {
            etchfoin_cf7_listFields = [];
            etchfoin_cf7_renderTable();
            return Promise.resolve();
        }
        return etchfoin_cf7_post('etchfoin_get_cf7_list_fields', { list_uid: listUid })
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Failed list fields');
                etchfoin_cf7_listFields = res.data.fields || [];
            });
    }

    /* ----------------- VALIDATION helpers ----------------- */

    function etchfoin_cf7_getCurrentSelections() {
        const byCf7 = {};
        const byEtch = {};
        $('.emfi-map-select').each(function () {
            const cfName = $(this).data('cf7-field');
            const etchTag = ($(this).val() || '').toString().trim();
            if (!cfName) return;
            byCf7[cfName] = etchTag;
            if (etchTag) byEtch[etchTag] = cfName;
        });
        return { byCf7, byEtch };
    }

    function etchfoin_cf7_validateSelections() {
        const requiredTags = etchfoin_cf7_listFields
            .filter(f => (f.required === 'yes'))
            .map(f => f.tag);

        const { byEtch } = etchfoin_cf7_getCurrentSelections();

        etchfoin_cf7_missingRequired = requiredTags.filter(tag => !byEtch[tag]);
        etchfoin_cf7_isValid = etchfoin_cf7_missingRequired.length === 0;

        etchfoin_cf7_renderValidationBanner();
        etchfoin_cf7_updateSaveButton();
        etchfoin_cf7_toggleMappingVisibility(); // keep banner visibility in sync with validity + list state
    }

    function etchfoin_cf7_renderValidationBanner() {
        ensureValidationBox();

        if (!etchfoin_cf7_isValid) {
            const names = etchfoin_cf7_missingRequired
                .map(tag => {
                    const f = etchfoin_cf7_listFields.find(x => x.tag === tag);
                    return f ? (f.label || f.tag) : tag;
                });

            etchfoin_cf7_$validation
                .removeClass('notice-success')
                .addClass('notice-error')
                .html(
                    `<p><strong>Required fields not mapped:</strong> ${names.join(', ')}.<br>
                     Please map these Etchmail fields before saving.</p>`
                )
                .show();
        } else {
            etchfoin_cf7_$validation
                .removeClass('notice-error notice-success')
                .empty()
                .hide(); // hide when valid so there’s no empty box
        }
    }

    /* ----------------- Save mappings (button only) ----------------- */
    function etchfoin_cf7_saveMappingsNow() {
        etchfoin_cf7_validateSelections();
        if (!etchfoin_cf7_isValid) {
            etchfoin_cf7_showNotice('Please map all required fields before saving.', false);
            return;
        }

        // Build clean payload (skip empty)
        const payload = {};
        $('.emfi-map-select').each(function () {
            const cfName = $(this).data('cf7-field');
            const val = ($(this).val() || '').toString().trim();
            if (!cfName) return;
            if (!val) return;
            payload[cfName] = val;
        });

        return $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'etchfoin_save_cf7_mapped_fields',
                nonce: etchfoinDataCF7.nonce,
                form_id: etchfoin_cf7_getFormId(),
                mapped_fields: JSON.stringify(payload),
            }
        })
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Save failed');
                etchfoin_cf7_mapped = res.data.mapped_fields || {};
                etchfoin_cf7_setDirty(false);
                etchfoin_cf7_validateSelections(); // refresh banner + button
                etchfoin_cf7_showNotice('Field mappings saved.', true);
            })
            .catch(e => etchfoin_cf7_showNotice(e.message || 'Failed to save mappings', false));
    }

    /* ----------------- Render (same styles/markup) ----------------- */
    function etchfoin_cf7_renderTable() {
        etchfoin_cf7_$tableBody.empty();
        ensureValidationBox(); // create hidden

        // Index list fields by tag for quick lookups
        const fieldByTag = {};
        etchfoin_cf7_listFields.forEach(f => { fieldByTag[f.tag] = f; });

        etchfoin_cf7_cf7Tags.forEach(tag => {
            if (!tag.name) return;

            const allowed = etchfoin_cf7_compatMap[tag.basetype || 'text'] || ['text'];
            const mappedTag = (etchfoin_cf7_mapped[tag.name] || '').toString().trim();

            const $row = $('<div>').addClass('etchfoin-mapping-row');

            const $col1 = $('<div>')
                .addClass('etchfoin-mapping-col')
                .append($('<span>').addClass('etchfoin-field-name').text(tag.name))
                .append($('<span>').addClass('etchfoin-field-type').text(tag.basetype || 'text'));

            const $select = $('<select>')
                .addClass('emfi-map-select etchfoin-select')
                .attr('data-cf7-field', tag.name)
                .append('<option value="">- Not Mapped -</option>');

            // Add all allowed options
            etchfoin_cf7_listFields.forEach(field => {
                if (!allowed.includes(field.type)) return;

                const reqBadge   = field.required === 'yes' ? ' (required)' : '';
                const label      = `${field.label}${reqBadge}`;

                $select.append(
                    $('<option>')
                        .val(field.tag)
                        .text(label)
                );
            });

            // Inject previously mapped incompatible option (so it shows & can be corrected)
            if (mappedTag && !$select.find(`option[value="${mappedTag}"]`).length) {
                const f = fieldByTag[mappedTag];
                const label = f
                    ? `${f.label}${f.required === 'yes' ? ' (required)' : ''} — (currently mapped; incompatible)`
                    : `${mappedTag} — (currently mapped; incompatible)`;
                $select.prepend(
                    $('<option>')
                        .val(mappedTag)
                        .text(label)
                );
            }

            if (mappedTag) {
                $select.val(mappedTag);
            }

            $select.on('change', function () {
                const newVal = ($(this).val() || '').toString().trim();

                // Prevent duplicate Etchmail field mapping
                if (newVal) {
                    $('.emfi-map-select').not(this).each(function () {
                        if ($(this).val() === newVal) {
                            $(this).val('');
                        }
                    });
                }

                etchfoin_cf7_mapped[tag.name] = newVal;
                etchfoin_cf7_setDirty(true);
                etchfoin_cf7_validateSelections();
            });

            const $col2 = $('<div>').addClass('etchfoin-mapping-col').append($select);

            const $col3 = $('<div>')
                .addClass('etchfoin-mapping-col etchfoin-compatibility')
                .append($('<span>').addClass('etchfoin-info-icon dashicons dashicons-info'))
                .append(
                    $('<div>')
                        .addClass('etchfoin-tooltip')
                        .html(`<strong>Compatible Etchmail field types:</strong><br>${allowed.join(', ')}`)
                );

            $row.append($col1, $col2, $col3);
            etchfoin_cf7_$tableBody.append($row);
        });

        // Add Save button (if missing). Default disabled until dirty AND valid.
        if ($('#etchfoin-save-mappings').length === 0) {
            const $saveBtn = $('<button type="button" class="button button-primary" id="etchfoin-save-mappings">Mappings saved</button>');
            $saveBtn.prop('disabled', true);
            $saveBtn.on('click', etchfoin_cf7_saveMappingsNow);
            etchfoin_cf7_$saveDiv.append($saveBtn);
        }

        // Apply current enabled/disabled visibility
        etchfoin_cf7_applyEnabledUI();
        etchfoin_cf7_toggleMappingVisibility();

        // Validate immediately after rendering (ensures banner + button state are correct)
        etchfoin_cf7_validateSelections();
    }

    /* ----------------- Handlers ----------------- */
    etchfoin_cf7_$listSelect.on('change', function () {
        etchfoin_cf7_selectedList = $(this).val() || '';

        // Persist list selection + feedback
        etchfoin_cf7_post('etchfoin_save_cf7_list_uid', {
            form_id: etchfoin_cf7_getFormId(),
            list_uid: etchfoin_cf7_selectedList
        })
            .then(res => {
                if (!res.success) throw new Error(res.data || 'Could not save list');
                etchfoin_cf7_showNotice('List selection saved.', true);
            })
            .catch(e => etchfoin_cf7_showNotice(e.message, false));

        etchfoin_cf7_toggleMappingVisibility();

        if (etchfoin_cf7_selectedList) {
            etchfoin_cf7_fetchListFields(etchfoin_cf7_selectedList)
                .then(() => {
                    etchfoin_cf7_renderTable();
                    etchfoin_cf7_showNotice('List fields loaded.', true);
                })
                .catch(e => etchfoin_cf7_showNotice(e.message || 'Failed to load fields', false));
        } else {
            // If cleared, also clear table content and validation
            etchfoin_cf7_listFields = [];
            etchfoin_cf7_$tableBody.empty();
            ensureValidationBox().hide().empty();
            etchfoin_cf7_isValid = true;
            etchfoin_cf7_missingRequired = [];
            etchfoin_cf7_setDirty(false);
        }
    });

    /* ----------------- Boot ----------------- */
    (async function etchfoin_cf7_init() {
        const formId = etchfoin_cf7_getFormId();
        if (!formId) return;

        try {
            await etchfoin_cf7_fetchSettings(formId);             // enabled, list_uid, mapped
            etchfoin_cf7_applyEnabledUI();                        // reflect state
            await etchfoin_cf7_fetchFormTags(formId);             // cf7 tags
            await etchfoin_cf7_fetchLists();                      // fill dropdown (preselect saved)
            await etchfoin_cf7_fetchListFields(etchfoin_cf7_selectedList); // fields for selected list
            etchfoin_cf7_renderTable();                           // build mapping UI
            etchfoin_cf7_toggleMappingVisibility();
        } catch (e) {
            etchfoin_cf7_showNotice(e.message || 'Initialisation error', false);
        }
    })();
});
