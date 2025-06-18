<?php defined('ABSPATH') || exit; // integrations/cf7/assets/view.php ?>

<h2>Etchmail Integration Settings</h2>

<fieldset>
    <label>
        <input type="checkbox" name="etchmail_enabled"
               value="1" <?php checked(get_option('emfi_cf7_enabled'), true); ?> />
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
                <select name="etchmail_list_id" id="etchmail_list_id">
                    <option value="">Select a list...</option>
                </select>
                <button type="button" id="load-etchmail-lists" class="button">Refresh</button>
                <p class="description">Select the Etchmail list where subscribers will be added.</p>
            </td>
        </tr>
    </table>
    <br/>
    <button type="button" id="add-field-mapping" class="button">Add Field Mapping</button>
</fieldset>
<button type="button" id="save-emfi-settings" class="button-primary">save</button>
<script>
    jQuery(document).ready(function ($) {
        const $checkbox   = $('input[name="etchmail_enabled"]');
        const $settings   = $('#etchmail-settings');
        const $listSelect = $('#etchmail_list_id');
        const $loadButton = $('#load-etchmail-lists');
        const nonce       = '<?php echo wp_create_nonce('etchmail_nonce'); ?>';

        if (!$checkbox.length || !$settings.length || !$listSelect.length) return;

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
                        $listSelect.append(
                            $('<option>', {
                                value: list.list_uid,
                                text: list.name
                            })
                        );
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
    });
</script>

