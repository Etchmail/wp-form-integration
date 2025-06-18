<?php defined('ABSPATH') || exit; // integrations/cf7/assets/view.php

$formid = $this->form->id;
$enabled = get_option("emfi_cf7_{$formid}_enabled", false);
?>

<div id="emfi-notice" style="display:none; margin-top: 15px;"></div>
<br/>
<h2>Etchmail Integration Settings <?php echo $formid?></h2>

<fieldset>
    <label>
        <input type="checkbox" name="etchmail_enabled"
               value="1" <?php checked((bool) $enabled); ?> />
        Enable Etchmail integration for this form
    </label>
</fieldset>

<br/>

<fieldset id="etchmail-settings" style="<?php echo get_option('emfi_cf7_enabled') ? '' : 'display:none;'; ?>">
    <h3>Etchmail List Selection</h3>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="etchmail_list_id">Mailing List</label>
            </th>
            <td>
                <select name="etchmail_list_id" id="etchmail_list_id" >
                </select>
                <button type="button" id="load-etchmail-lists" class="button">Refresh</button>
                <p class="description">Select the Etchmail list where subscribers will be added.</p>
            </td>
        </tr>
    </table>
    <button type="button" id="add-field-mapping" class="button" style="margin-bottom: 20px">Add Field Mapping</button>
</fieldset>

<button type="button" id="save-emfi-settings" class="button-primary">save</button>
<script>
    jQuery(document).ready(function ($) {
        const $checkbox   = $('input[name="etchmail_enabled"]');
        const $settings   = $('#etchmail-settings');
        const $listSelect = $('#etchmail_list_id');
        const $loadButton = $('#load-etchmail-lists');
        const $saveButton = $('#save-emfi-settings');

        const formId = <?php echo (int) $formid; ?>; // Output the form ID into JS
        const nonce         = '<?php echo wp_create_nonce('etchmail_nonce'); ?>';
        const savedListUid = '<?php echo esc_js(get_option("emfi_cf7_{$formid}_list_uid")); ?>';

        if (!$checkbox.length || !$settings.length || !$listSelect.length) return;

        $listSelect.empty().append('<option value="">Select a list...</option>');

        function toggleSettings() {
            $settings.toggle($checkbox.is(':checked'));
        }

        function loadLists() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'emfi_get_lists',
                    nonce: nonce
                },
                success: function (response) {
                    if (!response.success || !Array.isArray(response.data)) {
                        console.error('List loading failed', response);
                        return;
                    }

                    $listSelect.empty().append('<option value="">Select a list...</option>');

                    response.data.forEach(function (list) {
                        const $option = $('<option>', {
                            value: list.list_uid,
                            text: list.name
                        });

                        if (list.list_uid === savedListUid) {
                            $option.prop('selected', true);
                        }

                        $listSelect.append($option);
                    });
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        }

        // Init
        toggleSettings();
        loadLists();

        // Events
        $checkbox.on('change', toggleSettings);
        $loadButton.on('click', loadLists);

        $saveButton.on('click', function () {
            const isEnabled = $checkbox.is(':checked') ? '1' : '0';
            const listUid   = $listSelect.val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'emfi_save_cf7_settings',
                    nonce: nonce,
                    form_id: formId,
                    enabled: isEnabled,
                    list_uid: listUid
                },
                success: function (res) {
                    if (res.success) {
                        showNotice('Settings saved successfully.', 'success');
                    } else {
                        showNotice('Failed to save settings: ' + res.data, 'error');
                    }
                },
                error: function () {
                    showNotice('AJAX error while saving settings.', 'error');
                }
            });
        });

        function showNotice(message, type = 'success') {
            const $notice = $('#emfi-notice');
            const classes = type === 'success' ? 'notice notice-success' : 'notice notice-error';

            $notice
                .hide()
                .removeClass()
                .addClass(classes)
                .html('<p>' + message + '</p>')
                .fadeIn();

            setTimeout(() => {
                $notice.fadeOut();
            }, 4000);
        }


    });
</script>

