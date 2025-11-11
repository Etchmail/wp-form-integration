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
				'none' => 'Disabled',
				'contactform7' => 'Contact Form 7',
//				'formidable' => 'Formidable Forms', // todo
//				'fluent' => 'Fluent Forms', // todo
			],
			'default' => 'none',
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

					if ( $field['type'] === 'select' ) {
						echo '<select name="' . esc_attr( $name ) . '">';
						foreach ( $field['options'] as $optionValue => $label ) {
							$selected = selected( $value, $optionValue, false );
							echo '<option value="' . esc_attr( $optionValue ) . '" ' . esc_attr($selected) . '>' . esc_html( $label ) . '</option>';
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
			'Etchmail Settings',
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

		if ( $list_uid == null ) {
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
			$fields[] = [
					'label'         => $field['label'],
					'tag'           => strtolower($field['tag']),
					'default_value' => $field['default_value'],
					'required'      => $field['required'],
					'type'          => $field['type']['identifier'],
				];
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
			if ( !is_array($field) ) { continue; }

			$type_key = isset($field['type']) && is_string($field['type']) ? strtolower($field['type']) : 'text';
			if ( ! isset( $type2filter[ $type_key ] ) ) {
				continue;
			}

			$tag_raw   = isset($field['tag']) ? $field['tag'] : '';
			$value_raw = $field['value'] ?? '';

			// Preserve exact tag case; strip unsafe chars in user_input()
			$tag   = self::user_input( $tag_raw, 'text' ); // key scrubbing
			$value = self::user_input( $value_raw, $type2filter[ $type_key ] );

			if ($tag === '') { continue; }

			if ( $type_key === 'email' && $email === '' ) {
				$email = $value; // first valid email wins
			}

			$body[ $tag ] = $value;
		}

		if ( $email === '' ) {
			return; // Etchmail requires EMAIL
		}

		$body['EMAIL']               = $email;
		$body['details[source]']     = 'web';

		if ( $ip_address || isset($_SERVER['REMOTE_ADDR']) ) {
			$body['details[ip_address]'] = $ip_address ?: sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
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

		$resp = etchfoin_api_v2_request( 'POST', $endpoint, $body );

		if ( ! is_array( $resp ) || ( $resp['status'] ?? '' ) !== 'success' ) {
			if ( isset( $resp['error'] ) && $resp['error'] === 'The subscriber already exists in this list.' ) {
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
				return ( $str === 'on' || $str === '1' || $str === true ) ? '1' : '0';

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
