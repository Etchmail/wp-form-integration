<?php defined( 'ABSPATH' ) || exit;
class ETCHFOINConfig {
	const OPTION_GROUP = 'ETCHFOIN';
	const OPTION_PAGE = 'ETCHFOIN';

	private static $fields = [
		'api_url'      => [
			'label'   => 'API URL',
			'type'    => 'text',
			'default' => '',
		],
		'api_key'      => [
			'label'   => 'Private API Key',
			'type'    => 'text',
			'default' => '',
		],
		'enabled_form' => [
			'label'   => 'Select Form Integration',
			'type'    => 'select',
			'options' => [
				'none'          => 'Disabled',
				'contactform7'  => 'Contact Form 7',
			],
			'default' => 'none',
		],
		'enable_standalone' => [
			'label'   => 'Enable Standalone Shortcode Form',
			'type'    => 'select',
			'options' => [
				'no'  => 'Disabled',
				'yes' => 'Enabled',
			],
			'default' => 'no',
		],
		'recaptcha_enabled' => [
			'label'   => 'Enable reCAPTCHA v3',
			'type'    => 'select',
			'options' => [
				'no'  => 'Disabled',
				'yes' => 'Enabled',
			],
			'default' => 'no',
		],
		'recaptcha_site_key' => [
			'label'   => 'reCAPTCHA Site Key',
			'type'    => 'text',
			'default' => '',
		],
		'recaptcha_secret_key' => [
			'label'   => 'reCAPTCHA Secret Key',
			'type'    => 'text',
			'default' => '',
		],
	];

	/** Sanitize a saved option based on its key */
	private static function sanitize_option_value( string $key, $value ) {
		switch ( $key ) {
			case 'api_url':
				return esc_url_raw( (string) $value );
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	// Register settings & fields
	public static function register() {
		foreach ( self::$fields as $key => $field ) {
			register_setting(
				self::OPTION_GROUP,
				"etchfoin_$key",
				[
					'sanitize_callback' => function ( $val ) use ( $key ) {
						return self::sanitize_option_value( $key, $val );
					}
				]
			);

			add_settings_field(
				"etchfoin_$key",
				esc_html( $field['label'] ),
				function () use ( $key, $field ) {
					$name  = "etchfoin_$key";
					$value = get_option( $name, $field['default'] );

					if ( 'select' === $field['type'] ) {
						echo '<select name="' . esc_attr( $name ) . '">';
						foreach ( $field['options'] as $optionValue => $label ) {
							$selected = selected( $value, $optionValue, false );
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() returns safe HTML attribute.
						echo '<option value="' . esc_attr( $optionValue ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
						}
						echo '</select>';
					} else {
						echo '<input type="text" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
					}
				},
				self::OPTION_PAGE,
				'etchfoin_config_section'
			);
		}

		add_settings_section(
			'etchfoin_config_section',
			esc_html__( 'Etchmail Settings', 'etchfoin' ),
			null,
			self::OPTION_PAGE
		);
	}

	// Get single option
	public static function get( $key ) {
		if ( ! isset( self::$fields[ $key ] ) ) {
			return null;
		}

		$value = get_option( "etchfoin_$key", self::$fields[ $key ]['default'] );

		return self::sanitize_option_value( $key, $value );
	}

	// Get all config as array
	public static function all() {
		$out = [];
		foreach ( self::$fields as $key => $meta ) {
			$out[ $key ] = self::get( $key );
		}

		return $out;
	}

	public static function getLists() {

		$base_url = esc_url_raw( self::get( 'api_url' ) );
		$endpoint = rtrim( $base_url, '/' ) . '/lists';

		$response = etchfoin_api_v2_request( 'GET', $endpoint );

		if ( ! $response || ! isset( $response['data']['records'] ) ) {
			return null;
		}

		$lists = array();
		foreach ( $response['data']['records'] as $list ) {
			$lists[ $list['general']['list_uid'] ] = $list['general']['name'];
		}

		return $lists;

	}

	public static function getFields( $list_uid = null ) {

		if ( null == $list_uid ) {
			return null;
		}

		$base_url = esc_url_raw( self::get( 'api_url' ) );
		$endpoint = rtrim( $base_url, '/' ) . "/lists/{$list_uid}/fields";

		$response = etchfoin_api_v2_request( 'GET', $endpoint );

		if ( ! $response || ! isset( $response['data']['records'] ) ) {
			return null;
		}

		$fields = array();

		foreach ( $response['data']['records'] as $field ) {
			$entry = [
					'label'         => $field['label'],
					'tag'           => $field['tag'],
					'default_value' => $field['default_value'],
					'required'      => $field['required'],
					'type'          => $field['type']['identifier'],
				];
			if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
				$entry['options'] = $field['options'];
			}
			$fields[] = $entry;
		}

		return $fields;

	}

	/**
	 * Submit mapped form data to a specific Etchmail list.
	 *
	 * @param string $list_uid Target list UID.
	 * @param array $data Array of mapped fields.
	 * @param string|null $ip_address Optional originating IP address.
	 */
	public static function submitToList( string $list_uid, array $data, ?string $ip_address = null ): void {
		$dbg = defined( 'ETCHFOIN_DEBUG' ) && ETCHFOIN_DEBUG;

		if ( $dbg ) { etchfoin_logging( '[submitToList] START — list_uid=' . $list_uid . ' fields=' . count( $data ), 'debug' ); }

		/* 1. Gather the mapped fields ------------------------------------ */
		$body  = [];
		$email = '';

		// Map CF7 "type" → sanitization profile used by user_input()
		$type2filter = [
			'text'     => 'text',
			'textarea' => 'textarea',
			'email'    => 'email',
			'tel'      => 'tel',
			'url'      => 'url',
			'number'   => 'number',
			'date'     => 'date',
			'radio'    => 'radio',
			'checkbox' => 'checkbox',
			'select'   => 'checkbox', // comma-separated string (multi/single both handled)
			'bool'     => 'bool',
		];

		foreach ( $data as $field ) {
			if ( !is_array($field) ) {
				if ( $dbg ) { etchfoin_logging( '[submitToList] Skipping non-array field', 'debug' ); }
				continue;
			}

			$type_key = isset($field['type']) && is_string($field['type']) ? strtolower($field['type']) : 'text';
			if ( ! isset( $type2filter[ $type_key ] ) ) {
				if ( $dbg ) { etchfoin_logging( '[submitToList] Unknown type "' . $type_key . '" for tag "' . ($field['tag'] ?? '?') . '" — skipping', 'debug' ); }
				continue;
			}

			$tag_raw   = isset($field['tag']) ? $field['tag'] : '';
			$value_raw = $field['value'] ?? '';

			// Preserve exact tag case; strip unsafe chars in user_input()
			$tag   = self::user_input( $tag_raw, 'text' ); // key scrubbing
			$value = self::user_input( $value_raw, $type2filter[ $type_key ] );

			if ('' === $tag) {
				if ( $dbg ) { etchfoin_logging( '[submitToList] Empty tag after sanitization — skipping', 'debug' ); }
				continue;
			}

			if ( 'email' === $type_key && '' === $email ) {
				$email = $value; // first valid email wins
			}

			$tag_upper = strtoupper( $tag );
			if ( $dbg ) { etchfoin_logging( '[submitToList] Mapped: ' . $tag_upper . ' = "' . $value . '" (type: ' . $type_key . ')', 'debug' ); }
			$body[ $tag_upper ] = $value;
		}

		if ( $dbg ) { etchfoin_logging( '[submitToList] Body keys: ' . implode( ', ', array_keys( $body ) ) . ' | email: "' . $email . '"', 'debug' ); }

		if ( '' === $email ) {
			if ( $dbg ) { etchfoin_logging( '[submitToList] ABORT: No email found — Etchmail requires EMAIL', 'debug' ); }
			return; // Etchmail requires EMAIL
		}

		$body['EMAIL']               = $email;
		$body['details[source]']     = 'web';

		if ( $ip_address || isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $ip_address ? $ip_address : sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				$body['details[ip_address]'] = $ip;
			}
		}

		/* 2. Build & call endpoint safely -------------------------------- */

		// (a) Ensure https
		$base_url = (string) self::get('api_url');
		$base_url = esc_url_raw( $base_url );
		if ( stripos($base_url, 'https://') !== 0 ) {
			etchfoin_logging('[Etchmail] Insecure API URL blocked');
			return;
		}

		// (b) Optional: allowlist host (uncomment and set domain if you want strict SSRF protection)
		// $host = wp_parse_url($base_url, PHP_URL_HOST);
		// if (!in_array($host, ['api.etchmail.com'], true)) {
		//     etchfoin_logging('[Etchmail] Disallowed API host: ' . $host);
		//     return;
		// }

		// (c) List UID should be safe path-segment characters (adjust regex to your format)
		if ( !preg_match('/^[A-Za-z0-9_\-]+$/', $list_uid) ) {
			etchfoin_logging('[Etchmail] Invalid list UID');
			return;
		}

		$endpoint = rtrim( $base_url, '/' ) . "/lists/{$list_uid}/subscribers";

		if ( $dbg ) { etchfoin_logging( '[submitToList] Endpoint: ' . $endpoint, 'debug' ); }
		if ( $dbg ) { etchfoin_logging( '[submitToList] Request body: ' . wp_json_encode( $body ), 'debug' ); }

		$resp = etchfoin_api_v2_request( 'POST', $endpoint, $body );

		if ( $dbg ) { etchfoin_logging( '[submitToList] Response: ' . wp_json_encode( $resp ), 'debug' ); }

		if ( ! is_array( $resp ) || 'success' !== ( $resp['status'] ?? '' ) ) {
			if ( isset( $resp['error'] ) && 'The subscriber already exists in this list.' === $resp['error'] ) {
				return; // benign duplicate
			}
			etchfoin_logging( '[Etchmail] API error: ' . wp_json_encode( $resp ) );
		}
	}


	public static function user_input( $str, ?string $type = 'text' ): string {
		switch ( $type ) {
			case 'email':
				return sanitize_email( (string) $str );

			case 'url':
				return esc_url_raw( (string) $str );

			case 'tel':
				// Strip non-numeric, allow leading +
				return preg_replace( '/[^\d\+]/', '', (string) $str );

			case 'number':
				return is_numeric( $str ) ? (string) $str : '';

			case 'date':
				// YYYY-MM-DD only
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string)$str ) ? (string)$str : '';

			case 'checkbox':
				if ( is_array( $str ) ) {
					$str = array_map( 'sanitize_text_field', $str );
					return implode( ',', $str );
				}
				// SINGLE VALUE CHECKBOX — sanitize (previously unsanitized)
				return sanitize_text_field( (string) $str );

			case 'radio':
				if ( is_array( $str ) ) {
					$str = array_map( 'sanitize_text_field', $str );
					return implode( ',', $str );
				}
				return sanitize_text_field( (string) $str );

			case 'textarea':
				return sanitize_textarea_field( (string) $str );

			case 'bool':
				return ( 'on' === $str || '1' === $str || true === $str ) ? '1' : '0';

			case 'text':
			default:
				// Also used to scrub tag keys (restrict characters)
				$s = sanitize_text_field( (string) $str );
				// Optionally tighten when used for keys: keep wordish characters and dashes/underscores/brackets
				// return preg_replace('/[^A-Za-z0-9_\-\.\[\]]/', '', $s);
				return $s;
		}
	}
}
