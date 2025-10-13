<?php defined( 'ABSPATH' ) || exit;

?>

<div class="etchfoin-settings-wrap">

    <div class="etchfoin-notice" id="etchfoin-notice" style="display:none;margin-top:15px;"></div>

    <div class="etchfoin-card">
        <div class="etchfoin-card-header">
            <label class="etchfoin-toggle">
                <input type="checkbox" name="etchfoin_enabled" value="0">
                <span class="etchfoin-toggle-slider"></span>
                <span class="etchfoin-toggle-label">Enable Etchmail Integration</span>
            </label>

            <span class="etchfoin-formid" data-form-id="<?php echo esc_attr( (int) $this->form->id ); ?>">
                <small>Form ID: <?php echo esc_html( (int) $this->form->id ); ?></small>
            </span>
        </div>

        <div id="etchfoin-settings" class="etchfoin-card-body" style="display:none">
            <div class="etchfoin-section">
                <h3 class="etchfoin-section-title">Mailing List</h3>
                <div class="etchfoin-field-group">
                    <div class="etchfoin-select-wrapper">
                        <select name="etchfoin_list_id" id="etchfoin_list_id" class="etchfoin-select">
                            <option value="">Select a mailing list...</option>
                        </select>

                    </div>
                    <p class="etchfoin-field-description">Choose which Etchmail list will receive submissions from this
                        form</p>
                </div>
            </div>

            <div class="etchfoin-mapping-table" id="etchfoin-mapping-table" style="display:none">
                <div class="etchfoin-mapping-header" style="display:none">
                    <div class="etchfoin-mapping-col">Contact Form Field</div>
                    <div class="etchfoin-mapping-col">Etchmail Field</div>
                    <div class="etchfoin-mapping-col"></div>
                </div>
                <div class="etchfoin-mapping-body" id="etchfoin-mapping-body">
                    <!-- JS will populate rows here -->
                </div>
            </div>

            <div class="etchfoin-save-button-wrapper" id="etchfoin-save-button">

            </div>

        </div>



    </div>

</div>