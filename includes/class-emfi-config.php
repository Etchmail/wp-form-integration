<?php defined( 'ABSPATH' ) || exit; // includes/class-emfi-config.php
class EmfiConfig {
	const OPTION_GROUP = 'EMFI';
	const OPTION_PAGE = 'EMFI';

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
				'none'       => 'Disabled',
				'cf7'        => 'Contact Form 7',
				'formidable' => 'Formidable Forms',
//				'fluent' => 'Fluent Forms',
			],
			'default' => 'none',
		],
	];

	/** Sanitize a saved option based on its key */
	private static function sanitize_option_value( string $key, $value ) {
		switch ( $key ) {
			case 'api_url':
				return esc_url_raw( (string) $value );
			case 'api_key':
			case 'enabled_form':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	// Register settings & fields
	public static function register() {
		foreach ( self::$fields as $key => $field ) {
			register_setting(
				self::OPTION_GROUP,
				"emfi_$key",
				[
					'sanitize_callback' => function ( $val ) use ( $key ) {
						return self::sanitize_option_value( $key, $val );
					}
				]
			);

			add_settings_field(
				"emfi_$key",
				esc_html( $field['label'] ),
				function () use ( $key, $field ) {
					$name  = "emfi_$key";
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
				'emfi_config_section'
			);
		}

		add_settings_section(
			'emfi_config_section',
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

		$value = get_option( "emfi_$key", self::$fields[ $key ]['default'] );

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

		$response = emfi_api_v2_request( 'GET', $endpoint );

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

		$response = emfi_api_v2_request( 'GET', $endpoint );

		if ( ! $response || ! isset( $response['data']['records'] ) ) {
			return null;
		}

		$fields = array();

		foreach ( $response['data']['records'] as $field ) {
			if ( $field['visibility'] == "visible" ) {
				$fields[] = [
					'label'         => $field['label'],
					'tag'           => $field['tag'],
					'default_value' => $field['default_value'],
					'required'      => $field['required'],
					'type'          => $field['type']['identifier'],
				];
			}
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
		$body  = [];     // final multipart payload
		$email = '';     // promoted address (first email field wins)

		$type2filter = [
			// Basic text-based input
			'text'     => 'text',
			'textarea' => 'textarea',

			// Contact & personal info
			'email'    => 'email',
			'tel'      => 'tel',
			'url'      => 'url',

			// Structured data
			'number'   => 'number',
			'date'     => 'date',

			// Selection inputs
			'radio'    => 'radio',
			'checkbox' => 'checkbox',
			'select'   => 'checkbox',

			// Misc
			'bool'     => 'bool',
		];

		foreach ( $data as $field ) {

			$type = $field['type'] ?? 'text';
			if ( ! isset( $type2filter[ $type ] ) ) {
				continue;
			}

			$tag   = self::user_input( $field['tag'] );
			$value = self::user_input( $field['value'], $type2filter[ $type ] );

			if ( $type === 'email' && $email === '' ) {
				$email = $value;          // only first email field
			}

			$body[ $tag ] = $value;       // FNAME, LNAME, custom tags …
		}

		if ( $email === '' ) {
			return;
		}

		$body['EMAIL']               = $email;          // Etchmail’s required field
		$body['details[source]']     = 'web';     // flat “details[…]” key
		if ( $ip_address != null ||  isset($_SERVER['REMOTE_ADDR'])) {
			$body['details[ip_address]'] = $ip_address ?? (sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) ?? null);
		}

		/* 2. Hit the endpoint ------------------------------------------- */

		$base_url = esc_url_raw( self::get( 'api_url' ) );
		$endpoint = rtrim( $base_url, '/' ) . "/lists/{$list_uid}/subscribers";
		$resp     = emfi_api_v2_request( 'POST', $endpoint, $body );

		if ( ! is_array( $resp ) || ( $resp['status'] ?? '' ) !== 'success' ) {
			// Suppress logging if it's the known duplicate subscriber warning
			if ( isset( $resp['error'] ) && $resp['error'] === 'The subscriber already exists in this list.' ) {
				return;
			}

			log_emfi( '[Etchmail] API error: ' . wp_json_encode( $resp ) );
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
				// Match YYYY-MM-DD format only
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $str ) ? $str : '';

			case 'checkbox':
				// If it's an array (e.g. from checkboxes), implode values with comma
				if ( is_array( $str ) ) {
					// Ensure each value is a string and safe
					$str = array_map( 'sanitize_text_field', $str );

					return (string) implode( ',', $str );
				}

				// For a single value checkbox
				return $str;
			case 'radio':
				// Convert array of options into a comma-separated string
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
				return sanitize_text_field( (string) $str );
		}
	}
}
