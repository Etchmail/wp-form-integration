<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap">
	<h1>Etchmail Standalone Forms</h1>
	<p>Configure standalone signup forms that can be embedded anywhere via shortcode.</p>

	<div id="etchfoin-standalone-app">

		<!-- Step 1: Select a list -->
		<div class="etchfoin-sa-section" id="etchfoin-sa-list-section">
			<h2>1. Select a Mailing List</h2>
			<p>Choose which list this form will subscribe visitors to.</p>

			<div class="etchfoin-sa-row">
				<select id="etchfoin-sa-list-select" class="regular-text">
					<option value="">— Loading lists… —</option>
				</select>
				<button type="button" class="button" id="etchfoin-sa-refresh-lists">Refresh</button>
			</div>
		</div>

		<!-- Step 2: Configure fields -->
		<div class="etchfoin-sa-section" id="etchfoin-sa-fields-section" style="display:none;">
			<h2>2. Select Fields to Display</h2>
			<p>Choose which fields from your list should appear on the form. Drag to reorder.</p>

			<table class="widefat fixed" id="etchfoin-sa-fields-table">
				<thead>
					<tr>
						<th class="check-column"><input type="checkbox" id="etchfoin-sa-check-all" /></th>
						<th>Field Label</th>
						<th>Tag</th>
						<th>Type</th>
						<th>Required</th>
					</tr>
				</thead>
				<tbody id="etchfoin-sa-fields-body">
					<tr><td colspan="5">Select a list first.</td></tr>
				</tbody>
			</table>

			<div class="etchfoin-sa-actions" style="margin-top:12px;">
				<button type="button" class="button button-primary" id="etchfoin-sa-save">Save Form Configuration</button>
				<button type="button" class="button button-link-delete" id="etchfoin-sa-delete" style="margin-left:12px;">Delete Configuration</button>
				<span id="etchfoin-sa-status" style="margin-left:12px;"></span>
			</div>
		</div>

		<!-- Step 3: Form Styling -->
		<div class="etchfoin-sa-section" id="etchfoin-sa-style-section" style="display:none;">
			<h2>3. Form Styling</h2>
			<p>Customise the appearance of your form. Changes reflect in the live preview below.</p>

			<div class="etchfoin-sa-style-grid">

				<!-- ── Layout & General ────────────────────────── -->
				<fieldset class="etchfoin-sa-fieldset">
					<legend>Layout &amp; General</legend>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-theme">Theme</label>
						<select id="etchfoin-style-theme">
							<option value="light" selected>Light</option>
							<option value="dark">Dark</option>
							<option value="minimal">Minimal</option>
						</select>
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-layout">Layout</label>
						<select id="etchfoin-style-layout">
							<option value="stacked" selected>Stacked</option>
							<option value="inline">Inline</option>
						</select>
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-max-width">Max Width (px)</label>
						<input type="number" id="etchfoin-style-max-width" value="600" min="200" max="1200" step="10" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-form-padding">Form Padding (px)</label>
						<input type="number" id="etchfoin-style-form-padding" value="24" min="0" max="80" step="2" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-gap">Field Spacing (px)</label>
						<input type="number" id="etchfoin-style-field-gap" value="16" min="0" max="48" step="2" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-form-bg">Form Background</label>
						<input type="color" id="etchfoin-style-form-bg" value="#ffffff" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-form-border-color">Form Border Colour</label>
						<input type="color" id="etchfoin-style-form-border-color" value="#cccccc" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-form-border-radius">Form Border Radius (px)</label>
						<input type="number" id="etchfoin-style-form-border-radius" value="6" min="0" max="40" step="1" />
					</div>
				</fieldset>

				<!-- ── Label Settings ──────────────────────────── -->
				<fieldset class="etchfoin-sa-fieldset">
					<legend>Labels</legend>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-show-labels">Display</label>
						<select id="etchfoin-style-show-labels">
							<option value="true" selected>Show Labels</option>
							<option value="false">Placeholders Only</option>
						</select>
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-label-color">Label Colour</label>
						<input type="color" id="etchfoin-style-label-color" value="#333333" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-label-size">Label Font Size (px)</label>
						<input type="number" id="etchfoin-style-label-size" value="13" min="10" max="24" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-label-weight">Label Weight</label>
						<select id="etchfoin-style-label-weight">
							<option value="400">Normal (400)</option>
							<option value="500">Medium (500)</option>
							<option value="600" selected>Semi-Bold (600)</option>
							<option value="700">Bold (700)</option>
						</select>
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-label-spacing">Label Bottom Spacing (px)</label>
						<input type="number" id="etchfoin-style-label-spacing" value="4" min="0" max="16" step="1" />
					</div>
				</fieldset>

				<!-- ── Field / Input Settings ─────────────────── -->
				<fieldset class="etchfoin-sa-fieldset">
					<legend>Input Fields</legend>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-bg">Background</label>
						<input type="color" id="etchfoin-style-field-bg" value="#ffffff" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-text-color">Text Colour</label>
						<input type="color" id="etchfoin-style-field-text-color" value="#333333" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-border-color">Border Colour</label>
						<input type="color" id="etchfoin-style-field-border-color" value="#cccccc" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-focus-color">Focus / Accent Colour</label>
						<input type="color" id="etchfoin-style-field-focus-color" value="#0073aa" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-border-width">Border Width (px)</label>
						<input type="number" id="etchfoin-style-field-border-width" value="1" min="0" max="5" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-border-radius">Border Radius (px)</label>
						<input type="number" id="etchfoin-style-field-border-radius" value="6" min="0" max="30" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-font-size">Font Size (px)</label>
						<input type="number" id="etchfoin-style-field-font-size" value="15" min="10" max="24" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-padding-v">Padding Vertical (px)</label>
						<input type="number" id="etchfoin-style-field-padding-v" value="10" min="4" max="24" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-padding-h">Padding Horizontal (px)</label>
						<input type="number" id="etchfoin-style-field-padding-h" value="12" min="4" max="24" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-field-height">Input Height (px)</label>
						<input type="number" id="etchfoin-style-field-height" value="44" min="30" max="80" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-placeholder-color">Placeholder Colour</label>
						<input type="color" id="etchfoin-style-placeholder-color" value="#999999" />
					</div>
				</fieldset>

				<!-- ── Button Settings ─────────────────────────── -->
				<fieldset class="etchfoin-sa-fieldset">
					<legend>Submit Button</legend>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-text">Button Label</label>
						<input type="text" id="etchfoin-style-button-text" value="Subscribe" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-bg">Background</label>
						<input type="color" id="etchfoin-style-button-bg" value="#0073aa" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-text-color">Text Colour</label>
						<input type="color" id="etchfoin-style-button-text-color" value="#ffffff" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-font-size">Font Size (px)</label>
						<input type="number" id="etchfoin-style-button-font-size" value="15" min="10" max="24" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-font-weight">Font Weight</label>
						<select id="etchfoin-style-button-font-weight">
							<option value="400">Normal (400)</option>
							<option value="500">Medium (500)</option>
							<option value="600" selected>Semi-Bold (600)</option>
							<option value="700">Bold (700)</option>
						</select>
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-padding-v">Padding Vertical (px)</label>
						<input type="number" id="etchfoin-style-button-padding-v" value="10" min="4" max="24" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-padding-h">Padding Horizontal (px)</label>
						<input type="number" id="etchfoin-style-button-padding-h" value="24" min="8" max="60" step="2" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-radius">Border Radius (px)</label>
						<input type="number" id="etchfoin-style-button-radius" value="6" min="0" max="40" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-full-width">Full Width</label>
						<select id="etchfoin-style-button-full-width">
							<option value="no" selected>No</option>
							<option value="yes">Yes</option>
						</select>
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-margin-top">Margin Top (px)</label>
						<input type="number" id="etchfoin-style-button-margin-top" value="0" min="0" max="60" step="1" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-button-margin-bottom">Margin Bottom (px)</label>
						<input type="number" id="etchfoin-style-button-margin-bottom" value="0" min="0" max="60" step="1" />
					</div>
				</fieldset>

				<!-- ── Messages ────────────────────────────────── -->
				<fieldset class="etchfoin-sa-fieldset">
					<legend>Messages</legend>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-success-msg">Success Message</label>
						<input type="text" id="etchfoin-style-success-msg" value="Thank you for subscribing!" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-error-msg">Error Message</label>
						<input type="text" id="etchfoin-style-error-msg" value="Something went wrong. Please try again." />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-success-color">Success Colour</label>
						<input type="color" id="etchfoin-style-success-color" value="#00a32a" />
					</div>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-error-color">Error Colour</label>
						<input type="color" id="etchfoin-style-error-color" value="#d63638" />
					</div>
				</fieldset>

				<!-- ── Popup Settings ──────────────────────────── -->
				<fieldset class="etchfoin-sa-fieldset">
					<legend>Popup</legend>

					<div class="etchfoin-sa-control">
						<label for="etchfoin-style-popup-enabled">Enable Popup</label>
						<select id="etchfoin-style-popup-enabled">
							<option value="no" selected>No</option>
							<option value="yes">Yes</option>
						</select>
					</div>

					<div class="etchfoin-sa-popup-options" style="display:none;">
						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-delay">Delay (seconds)</label>
							<input type="number" id="etchfoin-style-popup-delay" value="3" min="0" max="120" step="1" />
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-exit-intent">Exit Intent Trigger</label>
							<select id="etchfoin-style-popup-exit-intent">
								<option value="no" selected>No</option>
								<option value="yes">Yes</option>
							</select>
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-scroll">Scroll Trigger (%)</label>
							<input type="number" id="etchfoin-style-popup-scroll" value="0" min="0" max="100" step="5" />
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-show-once">Show Only Once</label>
							<select id="etchfoin-style-popup-show-once">
								<option value="yes" selected>Yes</option>
								<option value="no">No (every visit)</option>
							</select>
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-cookie-days">Remember Days</label>
							<input type="number" id="etchfoin-style-popup-cookie-days" value="30" min="1" max="365" step="1" />
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-close-overlay">Close on Overlay Click</label>
							<select id="etchfoin-style-popup-close-overlay">
								<option value="yes" selected>Yes</option>
								<option value="no">No</option>
							</select>
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-overlay-color">Overlay Colour</label>
							<input type="text" id="etchfoin-style-popup-overlay-color" value="rgba(0,0,0,0.6)" />
						</div>

						<div class="etchfoin-sa-control">
							<label for="etchfoin-style-popup-hide-on-submit">Close After Submit</label>
							<select id="etchfoin-style-popup-hide-on-submit">
								<option value="yes" selected>Yes</option>
								<option value="no">No</option>
							</select>
						</div>
					</div>
				</fieldset>

			</div><!-- .etchfoin-sa-style-grid -->

			<div class="etchfoin-sa-actions" style="margin-top:16px;">
				<button type="button" class="button button-primary" id="etchfoin-sa-save-styles">Save Styles</button>
				<button type="button" class="button" id="etchfoin-sa-reset-styles" style="margin-left:8px;">Reset to Defaults</button>
				<span id="etchfoin-sa-style-status" style="margin-left:12px;"></span>
			</div>

			<!-- Live preview -->
			<h3 style="margin-top:24px;">Live Preview</h3>
			<div id="etchfoin-sa-preview-wrap" style="padding:20px;background:#f0f0f1;border-radius:4px;">
				<div id="etchfoin-sa-preview"></div>
			</div>
		</div>

		<!-- Step 4: Shortcode -->
		<div class="etchfoin-sa-section" id="etchfoin-sa-shortcode-section" style="display:none;">
			<h2>4. Embed Shortcode</h2>
			<p>Copy this shortcode into any page, post, or widget to display the form.</p>

			<div class="etchfoin-sa-shortcode-box">
				<code id="etchfoin-sa-shortcode"></code>
				<button type="button" class="button button-small" id="etchfoin-sa-copy">Copy</button>
			</div>

			<h3>Shortcode Attributes</h3>
			<p>Style overrides in the shortcode take priority over saved admin styles. Omit an attribute to use the saved value.</p>
			<table class="widefat fixed" style="max-width:700px;">
				<thead>
					<tr><th>Attribute</th><th>Default</th><th>Description</th></tr>
				</thead>
				<tbody>
					<tr><td><code>list</code></td><td><em>(required)</em></td><td>The Etchmail list UID</td></tr>
					<tr><td colspan="3"><strong>Layout &amp; General</strong></td></tr>
					<tr><td><code>theme</code></td><td><code>light</code></td><td><code>light</code>, <code>dark</code>, or <code>minimal</code></td></tr>
					<tr><td><code>layout</code></td><td><code>stacked</code></td><td><code>stacked</code> or <code>inline</code></td></tr>
					<tr><td><code>max_width</code></td><td><code>600</code></td><td>Maximum form width in px</td></tr>
					<tr><td><code>form_padding</code></td><td><code>24</code></td><td>Form container padding in px</td></tr>
					<tr><td><code>field_gap</code></td><td><code>16</code></td><td>Spacing between fields in px</td></tr>
					<tr><td><code>bg_color</code></td><td><em>auto</em></td><td>Form background colour</td></tr>
					<tr><td><code>form_border_color</code></td><td><code>#cccccc</code></td><td>Form outer border colour</td></tr>
					<tr><td><code>border_radius</code></td><td><code>6</code></td><td>Form border radius in px</td></tr>
					<tr><td><code>class</code></td><td></td><td>Extra CSS class on wrapper</td></tr>
					<tr><td colspan="3"><strong>Labels</strong></td></tr>
					<tr><td><code>show_labels</code></td><td><code>true</code></td><td>Show labels (<code>true</code>) or use placeholders only (<code>false</code>)</td></tr>
					<tr><td><code>label_color</code></td><td><code>#333333</code></td><td>Label text colour</td></tr>
					<tr><td><code>label_size</code></td><td><code>13</code></td><td>Label font size in px</td></tr>
					<tr><td><code>label_weight</code></td><td><code>600</code></td><td>Label font weight (400–700)</td></tr>
					<tr><td><code>label_spacing</code></td><td><code>4</code></td><td>Gap below label in px</td></tr>
					<tr><td colspan="3"><strong>Input Fields</strong></td></tr>
					<tr><td><code>field_bg</code></td><td><code>#ffffff</code></td><td>Input background colour</td></tr>
					<tr><td><code>text_color</code></td><td><code>#333333</code></td><td>Input text colour</td></tr>
					<tr><td><code>field_border_color</code></td><td><code>#cccccc</code></td><td>Input border colour</td></tr>
					<tr><td><code>accent_color</code></td><td><code>#0073aa</code></td><td>Focus / accent colour</td></tr>
					<tr><td><code>field_border_width</code></td><td><code>1</code></td><td>Input border width in px</td></tr>
					<tr><td><code>field_border_radius</code></td><td><code>6</code></td><td>Input border radius in px</td></tr>
					<tr><td><code>field_font_size</code></td><td><code>15</code></td><td>Input font size in px</td></tr>
					<tr><td><code>field_padding_v</code></td><td><code>10</code></td><td>Input vertical padding in px</td></tr>
					<tr><td><code>field_padding_h</code></td><td><code>12</code></td><td>Input horizontal padding in px</td></tr>
					<tr><td><code>field_height</code></td><td><code>44</code></td><td>Input min-height in px</td></tr>
					<tr><td><code>placeholder_color</code></td><td><code>#999999</code></td><td>Placeholder text colour</td></tr>
					<tr><td colspan="3"><strong>Button</strong></td></tr>
					<tr><td><code>button_text</code></td><td><code>Subscribe</code></td><td>Submit button label</td></tr>
					<tr><td><code>button_bg</code></td><td><code>#0073aa</code></td><td>Button background colour</td></tr>
					<tr><td><code>button_text_color</code></td><td><code>#ffffff</code></td><td>Button text colour</td></tr>
					<tr><td><code>button_font_size</code></td><td><code>15</code></td><td>Button font size in px</td></tr>
					<tr><td><code>button_font_weight</code></td><td><code>600</code></td><td>Button font weight</td></tr>
					<tr><td><code>button_padding_v</code></td><td><code>10</code></td><td>Button vertical padding in px</td></tr>
					<tr><td><code>button_padding_h</code></td><td><code>24</code></td><td>Button horizontal padding in px</td></tr>
					<tr><td><code>button_radius</code></td><td><code>6</code></td><td>Button border radius in px</td></tr>
					<tr><td><code>button_full_width</code></td><td><code>no</code></td><td><code>yes</code> for full-width button</td></tr>
					<tr><td><code>button_margin_top</code></td><td><code>0</code></td><td>Button margin top in px</td></tr>
					<tr><td><code>button_margin_bottom</code></td><td><code>0</code></td><td>Button margin bottom in px</td></tr>
					<tr><td colspan="3"><strong>Messages</strong></td></tr>
					<tr><td><code>success_message</code></td><td>Thank you for subscribing!</td><td>Success text</td></tr>
					<tr><td><code>error_message</code></td><td>Something went wrong…</td><td>Error text</td></tr>
					<tr><td><code>success_color</code></td><td><code>#00a32a</code></td><td>Success message colour</td></tr>
					<tr><td><code>error_color</code></td><td><code>#d63638</code></td><td>Error message colour</td></tr>
					<tr><td colspan="3"><strong>Popup</strong></td></tr>
					<tr><td><code>popup_enabled</code></td><td><code>no</code></td><td><code>yes</code> to display as a popup overlay</td></tr>
					<tr><td><code>popup_delay</code></td><td><code>3</code></td><td>Seconds before popup appears</td></tr>
					<tr><td><code>popup_exit_intent</code></td><td><code>no</code></td><td><code>yes</code> to trigger when mouse leaves viewport</td></tr>
					<tr><td><code>popup_scroll</code></td><td><code>0</code></td><td>Page scroll % to trigger (0 = disabled)</td></tr>
					<tr><td><code>popup_show_once</code></td><td><code>yes</code></td><td><code>yes</code> to only show once per visitor</td></tr>
					<tr><td><code>popup_cookie_days</code></td><td><code>30</code></td><td>Days to remember dismissal</td></tr>
					<tr><td><code>popup_close_overlay</code></td><td><code>yes</code></td><td><code>yes</code> to close when clicking overlay</td></tr>
					<tr><td><code>popup_overlay_color</code></td><td><code>rgba(0,0,0,0.6)</code></td><td>Overlay background colour</td></tr>
					<tr><td><code>popup_hide_on_submit</code></td><td><code>yes</code></td><td><code>yes</code> to auto-close after successful subscribe</td></tr>
				</tbody>
			</table>

			<h3>Examples</h3>
			<pre>[etchmail_form list="abc123"]</pre>
			<pre>[etchmail_form list="abc123" theme="dark" accent_color="#e74c3c" layout="inline" button_text="Sign Up"]</pre>
			<pre>[etchmail_form list="abc123" theme="minimal" show_labels="false" bg_color="#f9f9f9"]</pre>
		</div>

	</div>
</div>
