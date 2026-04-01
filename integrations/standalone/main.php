<?php defined( 'ABSPATH' ) || exit;

class ETCHFOIN_Standalone {

	private static $instance = null;

	/** Default shortcode attributes */
	private static $defaults = [
		'list'               => '',          // list UID (required)
		// Layout & General
		'theme'              => 'light',     // light | dark | minimal
		'layout'             => 'stacked',   // stacked | inline
		'max_width'          => '600',       // px
		'form_padding'       => '24',        // px
		'field_gap'          => '16',        // px
		'bg_color'           => '',          // form background
		'form_border_color'  => '',          // form outer border
		'border_radius'      => '6',         // form border radius px
		'class'              => '',          // extra CSS class
		// Labels
		'show_labels'        => 'true',
		'label_color'        => '',
		'label_size'         => '13',        // px
		'label_weight'       => '600',
		'label_spacing'      => '4',         // px
		// Input fields
		'field_bg'           => '',
		'text_color'         => '',
		'field_border_color' => '',
		'accent_color'       => '#0073aa',
		'field_border_width' => '1',         // px
		'field_border_radius'=> '6',         // px
		'field_font_size'    => '15',        // px
		'field_padding_v'    => '10',        // px
		'field_padding_h'    => '12',        // px
		'field_height'       => '44',        // px min-height
		'placeholder_color'  => '',
		// Button
		'button_text'        => 'Subscribe',
		'button_bg'          => '',
		'button_text_color'  => '',
		'button_font_size'   => '15',
		'button_font_weight' => '600',
		'button_padding_v'   => '10',
		'button_padding_h'   => '24',
		'button_radius'      => '6',
		'button_full_width'  => 'no',
		'button_margin_top'  => '0',
		'button_margin_bottom'=> '0',
		// Messages
		'success_message'    => 'Thank you for subscribing!',
		'error_message'      => 'Something went wrong. Please try again.',
		'success_color'      => '',
		'error_color'        => '',
		// Popup
		'popup_enabled'      => 'no',        // yes | no
		'popup_delay'        => '3',         // seconds before showing
		'popup_exit_intent'  => 'no',        // trigger on mouse leave
		'popup_scroll'       => '0',         // % of page scrolled (0 = disabled)
		'popup_show_once'    => 'yes',       // cookie: show only once
		'popup_cookie_days'  => '30',        // days to remember dismissal
		'popup_close_overlay'=> 'yes',       // close when clicking overlay
		'popup_overlay_color'=> 'rgba(0,0,0,0.6)',
		'popup_hide_on_submit'=> 'yes',      // auto-close after successful submit
	];

	/* ------------------------------------------------------------------ */

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'etchmail_form', [ $this, 'render_shortcode' ] );

		// Frontend AJAX handlers (available for logged-in AND logged-out visitors)
		add_action( 'wp_ajax_etchfoin_standalone_submit',        [ $this, 'ajax_submit' ] );
		add_action( 'wp_ajax_nopriv_etchfoin_standalone_submit', [ $this, 'ajax_submit' ] );

		// Admin settings page for managing standalone forms
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

			// Admin AJAX
			add_action( 'wp_ajax_etchfoin_standalone_get_lists',       [ $this, 'ajax_admin_get_lists' ] );
			add_action( 'wp_ajax_etchfoin_standalone_get_list_fields', [ $this, 'ajax_admin_get_list_fields' ] );
			add_action( 'wp_ajax_etchfoin_standalone_get_saved_fields',[ $this, 'ajax_admin_get_saved_fields' ] );
			add_action( 'wp_ajax_etchfoin_standalone_save_form',       [ $this, 'ajax_admin_save_form' ] );
			add_action( 'wp_ajax_etchfoin_standalone_delete_form',     [ $this, 'ajax_admin_delete_form' ] );
			add_action( 'wp_ajax_etchfoin_standalone_save_styles',     [ $this, 'ajax_admin_save_styles' ] );
			add_action( 'wp_ajax_etchfoin_standalone_get_styles',      [ $this, 'ajax_admin_get_styles' ] );
		}
	}

	/* ================================================================== */
	/*  SHORTCODE                                                         */
	/* ================================================================== */

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( self::$defaults, $atts, 'etchmail_form' );

		$list_uid = sanitize_text_field( $atts['list'] );
		if ( '' === $list_uid ) {
			return '<!-- Etchmail: list attribute is required -->';
		}

		// Fetch stored field config for this list
		$fields = $this->get_form_fields( $list_uid );
		if ( empty( $fields ) ) {
			return '<!-- Etchmail: no fields configured for list ' . esc_attr( $list_uid ) . ' -->';
		}

		// Merge saved admin styles as base; shortcode attributes override
		$saved_styles = $this->get_styles( $list_uid );
		foreach ( self::$defaults as $key => $def ) {
			// If shortcode attribute wasn't provided (still equals default) but saved style exists, use it
			if ( $atts[ $key ] === $def && isset( $saved_styles[ $key ] ) && '' !== $saved_styles[ $key ] ) {
				$atts[ $key ] = $saved_styles[ $key ];
			}
		}

		// Enqueue assets only when shortcode is actually used
		$this->enqueue_frontend_assets();

		// Build inline CSS variables
		$css_vars = $this->build_css_vars( $atts );

		$theme       = in_array( $atts['theme'], [ 'light', 'dark', 'minimal' ], true ) ? $atts['theme'] : 'light';
		$layout      = in_array( $atts['layout'], [ 'stacked', 'inline' ], true ) ? $atts['layout'] : 'stacked';
		$show_labels = 'true' === $atts['show_labels'];
		$extra_class = sanitize_html_class( $atts['class'] );
		$btn_full    = 'yes' === $atts['button_full_width'];

		$form_id = 'etchmail-form-' . wp_unique_id();

		$is_popup         = 'yes' === $atts['popup_enabled'];
		$popup_delay      = absint( $atts['popup_delay'] );
		$popup_exit_intent = 'yes' === $atts['popup_exit_intent'];
		$popup_scroll     = absint( $atts['popup_scroll'] );
		$popup_show_once  = 'yes' === $atts['popup_show_once'];
		$popup_cookie_days = absint( $atts['popup_cookie_days'] ?: 30 );
		$popup_close_overlay = 'yes' === $atts['popup_close_overlay'];
		$popup_hide_on_submit = 'yes' === $atts['popup_hide_on_submit'];
		$popup_overlay_color  = sanitize_text_field( $atts['popup_overlay_color'] );

		ob_start();

		if ( $is_popup ) :
		?>
		<div id="<?php echo esc_attr( $form_id ); ?>-popup"
		     class="etchmail-popup"
		     style="display:none;--etchmail-popup-overlay:<?php echo esc_attr( $popup_overlay_color ); ?>;"
		     data-delay="<?php echo esc_attr( $popup_delay ); ?>"
		     data-exit-intent="<?php echo esc_attr( $atts['popup_exit_intent'] ); ?>"
		     data-scroll="<?php echo esc_attr( $popup_scroll ); ?>"
		     data-show-once="<?php echo esc_attr( $atts['popup_show_once'] ); ?>"
		     data-cookie-days="<?php echo esc_attr( $popup_cookie_days ); ?>"
		     data-close-overlay="<?php echo esc_attr( $atts['popup_close_overlay'] ); ?>"
		     data-hide-on-submit="<?php echo esc_attr( $atts['popup_hide_on_submit'] ); ?>"
		     data-list-uid="<?php echo esc_attr( $list_uid ); ?>">
			<div class="etchmail-popup__overlay"></div>
			<div class="etchmail-popup__dialog" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $atts['button_text'] ); ?>">
				<button type="button" class="etchmail-popup__close" aria-label="Close">&times;</button>
		<?php endif; ?>

		<div <?php if ( ! $is_popup ) : ?>id="<?php echo esc_attr( $form_id ); ?>"<?php endif; ?>
		     class="etchmail-form etchmail-form--<?php echo esc_attr( $theme ); ?> etchmail-form--<?php echo esc_attr( $layout ); ?> <?php echo esc_attr( $extra_class ); ?>"
		     style="<?php echo esc_attr( $css_vars ); ?>">

			<form class="etchmail-form__inner" novalidate>
				<?php wp_nonce_field( 'etchfoin_standalone_submit', '_etchmail_nonce', false ); ?>
				<input type="hidden" name="list_uid" value="<?php echo esc_attr( $list_uid ); ?>" />

				<div class="etchmail-form__fields">
					<?php foreach ( $fields as $field ) : ?>
						<?php $this->render_field( $field, $show_labels ); ?>
					<?php endforeach; ?>
				</div>

				<div class="etchmail-form__actions">
					<button type="submit" class="etchmail-form__submit<?php echo $btn_full ? ' etchmail-form__submit--full' : ''; ?>">
						<?php echo esc_html( $atts['button_text'] ); ?>
					</button>
				</div>

				<div class="etchmail-form__message" role="alert" aria-live="polite"
				     data-success="<?php echo esc_attr( $atts['success_message'] ); ?>"
				     data-error="<?php echo esc_attr( $atts['error_message'] ); ?>">
				</div>
			</form>

		</div>

		<?php if ( $is_popup ) : ?>
			</div><!-- .etchmail-popup__dialog -->
		</div><!-- .etchmail-popup -->
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/* ================================================================== */
	/*  FIELD RENDERING                                                   */
	/* ================================================================== */

	private function render_field( array $field, bool $show_labels ) {
		$tag      = sanitize_text_field( $field['tag'] ?? '' );
		$label    = esc_html( $field['label'] ?? $tag );
		$type     = sanitize_text_field( $field['type'] ?? 'text' );
		$required = ! empty( $field['required'] ) && 'no' !== $field['required'];

		$html_type = $this->map_field_type( $type );
		$req_attr  = $required ? ' required' : '';
		$req_star  = $required ? ' <span class="etchmail-form__required">*</span>' : '';

		echo '<div class="etchmail-form__field etchmail-form__field--' . esc_attr( $html_type ) . '">';

		if ( $show_labels ) {
			echo '<label class="etchmail-form__label" for="etchmail-' . esc_attr( $tag ) . '">' . $label . $req_star . '</label>';
		}

		$placeholder = $show_labels ? '' : ' placeholder="' . esc_attr( strip_tags( $label ) ) . ( $required ? ' *' : '' ) . '"';

		$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];

		switch ( $html_type ) {
			case 'textarea':
				echo '<textarea class="etchmail-form__input" id="etchmail-' . esc_attr( $tag ) . '" name="' . esc_attr( $tag ) . '"' . $placeholder . $req_attr . ' rows="4"></textarea>';
				break;

			case 'checkbox':
				echo '<label class="etchmail-form__checkbox-label">';
				echo '<input type="checkbox" class="etchmail-form__input" id="etchmail-' . esc_attr( $tag ) . '" name="' . esc_attr( $tag ) . '" value="1"' . $req_attr . ' />';
				if ( ! $show_labels ) {
					echo ' ' . $label;
				}
				echo '</label>';
				break;

			case 'select':
				echo '<select class="etchmail-form__input etchmail-form__select" id="etchmail-' . esc_attr( $tag ) . '" name="' . esc_attr( $tag ) . '"' . $req_attr . '>';
				echo '<option value="">' . esc_html__( '— Select —', 'etchfoin' ) . '</option>';
				foreach ( $options as $val => $name ) {
					echo '<option value="' . esc_attr( $val ) . '">' . esc_html( $name ) . '</option>';
				}
				echo '</select>';
				break;

			case 'multiselect':
				echo '<select class="etchmail-form__input etchmail-form__select" id="etchmail-' . esc_attr( $tag ) . '" name="' . esc_attr( $tag ) . '[]" multiple' . $req_attr . '>';
				foreach ( $options as $val => $name ) {
					echo '<option value="' . esc_attr( $val ) . '">' . esc_html( $name ) . '</option>';
				}
				echo '</select>';
				break;

			case 'radiolist':
				echo '<div class="etchmail-form__radio-group" id="etchmail-' . esc_attr( $tag ) . '">';
				foreach ( $options as $val => $name ) {
					$opt_id = 'etchmail-' . esc_attr( $tag ) . '-' . esc_attr( $val );
					echo '<label class="etchmail-form__radio-label" for="' . $opt_id . '">';
					echo '<input type="radio" id="' . $opt_id . '" name="' . esc_attr( $tag ) . '" value="' . esc_attr( $val ) . '"' . $req_attr . ' /> ';
					echo esc_html( $name );
					echo '</label>';
				}
				echo '</div>';
				break;

			case 'checkboxlist':
				echo '<div class="etchmail-form__checkbox-group" id="etchmail-' . esc_attr( $tag ) . '">';
				foreach ( $options as $val => $name ) {
					$opt_id = 'etchmail-' . esc_attr( $tag ) . '-' . esc_attr( $val );
					echo '<label class="etchmail-form__checkbox-label" for="' . $opt_id . '">';
					echo '<input type="checkbox" id="' . $opt_id . '" name="' . esc_attr( $tag ) . '[]" value="' . esc_attr( $val ) . '" /> ';
					echo esc_html( $name );
					echo '</label>';
				}
				echo '</div>';
				break;

			default:
				echo '<input type="' . esc_attr( $html_type ) . '" class="etchmail-form__input" id="etchmail-' . esc_attr( $tag ) . '" name="' . esc_attr( $tag ) . '"' . $placeholder . $req_attr . ' />';
				break;
		}

		echo '</div>';
	}

	private function map_field_type( string $api_type ): string {
		$map = [
			'text'             => 'text',
			'email'            => 'email',
			'url'              => 'url',
			'phonenumber'      => 'tel',
			'date'             => 'date',
			'datetime'         => 'datetime-local',
			'textarea'         => 'textarea',
			'checkbox'         => 'checkbox',
			'consentcheckbox'  => 'checkbox',
			'dropdown'         => 'select',
			'multiselect'      => 'multiselect',
			'radiolist'        => 'radiolist',
			'checkboxlist'     => 'checkboxlist',
			'yearsrange'       => 'select',
			'number'           => 'number',
		];
		return $map[ $api_type ] ?? 'text';
	}

	/* ================================================================== */
	/*  CSS VARIABLES                                                     */
	/* ================================================================== */

	private function build_css_vars( array $atts ): string {
		$vars = [];

		// Layout & general
		$vars[] = '--etchmail-accent:' . sanitize_hex_color( $atts['accent_color'] ?: '#0073aa' );
		$vars[] = '--etchmail-radius:' . absint( $atts['border_radius'] ) . 'px';
		$vars[] = '--etchmail-max-width:' . absint( $atts['max_width'] ?: 600 ) . 'px';
		$vars[] = '--etchmail-form-padding:' . absint( $atts['form_padding'] ?: 24 ) . 'px';
		$vars[] = '--etchmail-field-gap:' . absint( $atts['field_gap'] ?: 16 ) . 'px';

		if ( '' !== $atts['bg_color'] ) {
			$vars[] = '--etchmail-bg:' . sanitize_hex_color( $atts['bg_color'] );
		}
		if ( '' !== $atts['form_border_color'] ) {
			$vars[] = '--etchmail-border:' . sanitize_hex_color( $atts['form_border_color'] );
		}

		// Labels
		if ( '' !== $atts['label_color'] ) {
			$vars[] = '--etchmail-label-color:' . sanitize_hex_color( $atts['label_color'] );
		}
		$vars[] = '--etchmail-label-size:' . absint( $atts['label_size'] ?: 13 ) . 'px';
		$vars[] = '--etchmail-label-weight:' . absint( $atts['label_weight'] ?: 600 );
		$vars[] = '--etchmail-label-spacing:' . absint( $atts['label_spacing'] ) . 'px';

		// Input fields
		if ( '' !== $atts['field_bg'] ) {
			$vars[] = '--etchmail-field-bg:' . sanitize_hex_color( $atts['field_bg'] );
		}
		if ( '' !== $atts['text_color'] ) {
			$vars[] = '--etchmail-field-text:' . sanitize_hex_color( $atts['text_color'] );
			$vars[] = '--etchmail-text:' . sanitize_hex_color( $atts['text_color'] );
		}
		if ( '' !== $atts['field_border_color'] ) {
			$vars[] = '--etchmail-field-border:' . sanitize_hex_color( $atts['field_border_color'] );
		}
		$vars[] = '--etchmail-field-border-w:' . absint( $atts['field_border_width'] ?: 1 ) . 'px';
		$vars[] = '--etchmail-field-radius:' . absint( $atts['field_border_radius'] ?: 6 ) . 'px';
		$vars[] = '--etchmail-field-font-size:' . absint( $atts['field_font_size'] ?: 15 ) . 'px';
		$vars[] = '--etchmail-field-pad-v:' . absint( $atts['field_padding_v'] ?: 10 ) . 'px';
		$vars[] = '--etchmail-field-pad-h:' . absint( $atts['field_padding_h'] ?: 12 ) . 'px';
		$vars[] = '--etchmail-field-height:' . absint( $atts['field_height'] ?: 44 ) . 'px';

		if ( '' !== $atts['placeholder_color'] ) {
			$vars[] = '--etchmail-placeholder:' . sanitize_hex_color( $atts['placeholder_color'] );
		}

		// Button
		if ( '' !== $atts['button_bg'] ) {
			$vars[] = '--etchmail-btn-bg:' . sanitize_hex_color( $atts['button_bg'] );
		}
		if ( '' !== $atts['button_text_color'] ) {
			$vars[] = '--etchmail-btn-text:' . sanitize_hex_color( $atts['button_text_color'] );
		}
		$vars[] = '--etchmail-btn-font-size:' . absint( $atts['button_font_size'] ?: 15 ) . 'px';
		$vars[] = '--etchmail-btn-font-weight:' . absint( $atts['button_font_weight'] ?: 600 );
		$vars[] = '--etchmail-btn-pad-v:' . absint( $atts['button_padding_v'] ?: 10 ) . 'px';
		$vars[] = '--etchmail-btn-pad-h:' . absint( $atts['button_padding_h'] ?: 24 ) . 'px';
		$vars[] = '--etchmail-btn-radius:' . absint( $atts['button_radius'] ?: 6 ) . 'px';
		$vars[] = '--etchmail-btn-margin-top:' . absint( $atts['button_margin_top'] ) . 'px';
		$vars[] = '--etchmail-btn-margin-bottom:' . absint( $atts['button_margin_bottom'] ) . 'px';

		// Messages
		if ( '' !== $atts['success_color'] ) {
			$vars[] = '--etchmail-success:' . sanitize_hex_color( $atts['success_color'] );
		}
		if ( '' !== $atts['error_color'] ) {
			$vars[] = '--etchmail-error:' . sanitize_hex_color( $atts['error_color'] );
		}

		return implode( ';', $vars );
	}

	/* ================================================================== */
	/*  FRONTEND ASSETS                                                   */
	/* ================================================================== */

	private $assets_enqueued = false;

	private function enqueue_frontend_assets() {
		if ( $this->assets_enqueued ) {
			return;
		}
		$this->assets_enqueued = true;

		$base = plugins_url( 'integrations/standalone/assets/', ETCHFOIN_PLUGIN );

		wp_enqueue_style(
			'etchfoin-standalone',
			$base . 'etchfoin-standalone.css',
			[],
			ETCHFOIN_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'etchfoin-standalone',
			$base . 'etchfoin-standalone.js',
			[],
			ETCHFOIN_PLUGIN_VERSION,
			true
		);

		wp_localize_script( 'etchfoin-standalone', 'etchfoinStandalone', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'debug'   => defined( 'ETCHFOIN_DEBUG' ) && ETCHFOIN_DEBUG,
		] );
	}

	/* ================================================================== */
	/*  FRONTEND AJAX – FORM SUBMISSION                                   */
	/* ================================================================== */

	public function ajax_submit() {
		$this->debug_log( '=== STANDALONE SUBMIT START ===' );
		$this->debug_log( 'POST keys: ' . implode( ', ', array_keys( $_POST ) ) );

		// Verify nonce
		if ( ! isset( $_POST['_etchmail_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_etchmail_nonce'] ) ), 'etchfoin_standalone_submit' ) ) {
			$this->debug_log( 'FAIL: Nonce verification failed. Nonce present: ' . ( isset( $_POST['_etchmail_nonce'] ) ? 'yes' : 'no' ) );
			wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
			return;
		}
		$this->debug_log( 'OK: Nonce verified' );

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		$this->debug_log( 'list_uid: ' . $list_uid );
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			$this->debug_log( 'FAIL: Invalid list UID format' );
			wp_send_json_error( [ 'message' => 'Invalid list.' ] );
			return;
		}

		// Rate-limit: simple transient-based throttle per IP
		$ip_raw    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		$ip        = filter_var( $ip_raw, FILTER_VALIDATE_IP ) ? $ip_raw : '0.0.0.0';
		$throttle  = 'etchmail_sub_' . md5( $ip . $list_uid );
		if ( get_transient( $throttle ) ) {
			$this->debug_log( 'FAIL: Rate-limited (IP: ' . $ip . ')' );
			wp_send_json_error( [ 'message' => 'Please wait a moment before submitting again.' ] );
			return;
		}
		set_transient( $throttle, 1, 10 ); // 10-second cooldown

		// Get configured fields for this list
		$configured_fields = $this->get_form_fields( $list_uid );
		$this->debug_log( 'Configured fields count: ' . count( $configured_fields ) );
		if ( empty( $configured_fields ) ) {
			$this->debug_log( 'FAIL: No configured fields found for list ' . $list_uid );
			wp_send_json_error( [ 'message' => 'Form not configured.' ] );
			return;
		}
		$this->debug_log( 'Configured fields: ' . wp_json_encode( array_column( $configured_fields, 'tag' ) ) );

		// Build data array from submitted values
		$data = [];
		foreach ( $configured_fields as $field ) {
			$tag  = $field['tag'];
			$type = $field['type'] ?? 'text';

			// Etchmail API may report the email field with type "text" —
			// force it to "email" so submitToList() can identify it.
			if ( strtolower( $tag ) === 'email' ) {
				$type = 'email';
			}

			if ( ! isset( $_POST[ $tag ] ) ) {
				$this->debug_log( 'SKIP: Field "' . $tag . '" not in POST data' );
				continue;
			}

			$raw_value = wp_unslash( $_POST[ $tag ] );

			// Handle array values from multiselect / checkboxlist
			if ( is_array( $raw_value ) ) {
				$raw_value = array_map( 'sanitize_text_field', $raw_value );
				$raw_value = implode( ',', $raw_value );
			}

			$mapped_type = $this->api_type_to_submit_type( $type );
			$this->debug_log( 'Field "' . $tag . '": api_type=' . $type . ' mapped=' . $mapped_type . ' value="' . $raw_value . '"' );

			$data[] = [
				'tag'   => $tag,
				'type'  => $mapped_type,
				'value' => $raw_value,
			];
		}

		$this->debug_log( 'Data array count: ' . count( $data ) );
		$this->debug_log( 'Calling submitToList( ' . $list_uid . ' )' );

		ETCHFOINConfig::submitToList( $list_uid, $data, $ip );

		$this->debug_log( '=== STANDALONE SUBMIT END (success) ===' );
		wp_send_json_success( [ 'message' => 'Subscribed successfully.' ] );
	}

	private function api_type_to_submit_type( string $api_type ): string {
		$map = [
			'text'             => 'text',
			'email'            => 'email',
			'url'              => 'url',
			'phonenumber'      => 'tel',
			'date'             => 'date',
			'datetime'         => 'date',
			'textarea'         => 'textarea',
			'checkbox'         => 'checkbox',
			'consentcheckbox'  => 'bool',
			'dropdown'         => 'text',
			'multiselect'      => 'checkbox',
			'radiolist'        => 'text',
			'checkboxlist'     => 'checkbox',
			'yearsrange'       => 'text',
			'number'           => 'number',
		];
		return $map[ $api_type ] ?? 'text';
	}

	/* ================================================================== */
	/*  ADMIN – STANDALONE FORMS MANAGER                                  */
	/* ================================================================== */

	public function add_admin_page() {
		add_submenu_page(
			'options-general.php',
			'Etchmail Standalone Forms',
			'Etchmail Forms',
			'manage_options',
			'etchmail-standalone-forms',
			[ $this, 'render_admin_page' ]
		);
	}

	public function render_admin_page() {
		include ETCHFOIN_PLUGIN_DIR . 'integrations/standalone/assets/etchfoin-view-standalone.php';
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_etchmail-standalone-forms' !== $hook ) {
			return;
		}

		$base = plugins_url( 'integrations/standalone/assets/', ETCHFOIN_PLUGIN );

		// Frontend CSS is needed for the live preview
		wp_enqueue_style(
			'etchfoin-standalone',
			$base . 'etchfoin-standalone.css',
			[],
			ETCHFOIN_PLUGIN_VERSION
		);

		wp_enqueue_style(
			'etchfoin-admin-standalone',
			$base . 'etchfoin-admin-standalone.css',
			[ 'etchfoin-standalone' ],
			ETCHFOIN_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'etchfoin-admin-standalone',
			$base . 'etchfoin-admin-standalone.js',
			[ 'jquery' ],
			ETCHFOIN_PLUGIN_VERSION,
			true
		);

		wp_localize_script( 'etchfoin-admin-standalone', 'etchfoinStandaloneAdmin', [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'etchmail_nonce' ),
			'selectedList' => get_option( 'etchfoin_standalone_selected_list', '' ),
		] );
	}

	/* ================================================================== */
	/*  ADMIN AJAX                                                        */
	/* ================================================================== */

	public function ajax_admin_get_lists() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$lists = ETCHFOINConfig::getLists();
		if ( null === $lists ) {
			wp_send_json_error( 'Could not fetch lists. Check your API settings.' );
			return;
		}

		wp_send_json_success( $lists );
	}

	public function ajax_admin_get_list_fields() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			wp_send_json_error( 'Invalid list UID.' );
			return;
		}

		$fields = ETCHFOINConfig::getFields( $list_uid );
		if ( null === $fields ) {
			wp_send_json_error( 'Could not fetch fields.' );
			return;
		}

		wp_send_json_success( $fields );
	}

	public function ajax_admin_get_saved_fields() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			wp_send_json_error( 'Invalid list UID.' );
			return;
		}

		$fields = $this->get_form_fields( $list_uid );
		wp_send_json_success( $fields );
	}

	public function ajax_admin_save_form() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			wp_send_json_error( 'Invalid list UID.' );
			return;
		}

		$raw_fields = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]';
		$fields     = json_decode( $raw_fields, true );
		if ( ! is_array( $fields ) ) {
			wp_send_json_error( 'Invalid fields data.' );
			return;
		}

		// Sanitize each field entry
		$clean_fields = [];
		foreach ( $fields as $f ) {
			if ( ! is_array( $f ) || empty( $f['tag'] ) ) {
				continue;
			}
			$entry = [
				'tag'      => sanitize_text_field( $f['tag'] ),
				'label'    => sanitize_text_field( $f['label'] ?? $f['tag'] ),
				'type'     => sanitize_text_field( $f['type'] ?? 'text' ),
				'required' => sanitize_text_field( $f['required'] ?? 'no' ),
			];
			if ( ! empty( $f['options'] ) && is_array( $f['options'] ) ) {
				$clean_opts = [];
				foreach ( $f['options'] as $val => $name ) {
					$clean_opts[ sanitize_text_field( $val ) ] = sanitize_text_field( $name );
				}
				$entry['options'] = $clean_opts;
			}
			$clean_fields[] = $entry;
		}

		update_option( "etchfoin_standalone_{$list_uid}_fields", $clean_fields );
		update_option( 'etchfoin_standalone_selected_list', $list_uid );
		wp_send_json_success( 'Form saved.' );
	}

	public function ajax_admin_delete_form() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			wp_send_json_error( 'Invalid list UID.' );
			return;
		}

		delete_option( "etchfoin_standalone_{$list_uid}_fields" );
		wp_send_json_success( 'Form deleted.' );
	}

	/* ================================================================== */
	/*  ADMIN AJAX – STYLES                                               */
	/* ================================================================== */

	public function ajax_admin_save_styles() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			wp_send_json_error( 'Invalid list UID.' );
			return;
		}

		$raw = isset( $_POST['styles'] ) ? wp_unslash( $_POST['styles'] ) : '{}';
		$styles = json_decode( $raw, true );
		if ( ! is_array( $styles ) ) {
			wp_send_json_error( 'Invalid styles data.' );
			return;
		}

		// Only keep keys that exist in our defaults to prevent arbitrary data
		$clean = [];
		foreach ( $styles as $key => $val ) {
			if ( array_key_exists( $key, self::$defaults ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $val );
			}
		}

		update_option( "etchfoin_standalone_{$list_uid}_styles", $clean );
		wp_send_json_success( 'Styles saved.' );
	}

	public function ajax_admin_get_styles() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$list_uid = isset( $_POST['list_uid'] ) ? sanitize_text_field( wp_unslash( $_POST['list_uid'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_\-]+$/', $list_uid ) ) {
			wp_send_json_error( 'Invalid list UID.' );
			return;
		}

		$styles = $this->get_styles( $list_uid );
		wp_send_json_success( $styles );
	}

	/* ================================================================== */
	/*  HELPERS                                                           */
	/* ================================================================== */

	private function get_form_fields( string $list_uid ): array {
		$fields = get_option( "etchfoin_standalone_{$list_uid}_fields", [] );
		return is_array( $fields ) ? $fields : [];
	}

	private function get_styles( string $list_uid ): array {
		$styles = get_option( "etchfoin_standalone_{$list_uid}_styles", [] );
		return is_array( $styles ) ? $styles : [];
	}

	/* ================================================================== */
	/*  DEBUG LOGGING                                                     */
	/* ================================================================== */

	private function debug_log( string $message ): void {
		if ( ! defined( 'ETCHFOIN_DEBUG' ) || ! ETCHFOIN_DEBUG ) {
			return;
		}
		etchfoin_logging( '[Standalone] ' . $message, 'debug' );
	}
}

ETCHFOIN_Standalone::instance();
