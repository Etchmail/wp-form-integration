<?php defined('ABSPATH') || exit; // includes/class-emfi-config.php
class EmfiConfig {
	const OPTION_GROUP = 'EMFI';
	const OPTION_PAGE  = 'EMFI';

	private static $fields = [
		'api_url' => [
			'label' => 'API URL',
			'type'  => 'text',
			'default' => '',
		],
		'api_key' => [
			'label' => 'Private API Key',
			'type'  => 'text',
			'default' => '',
		],
		'enabled_form' => [
			'label' => 'Select Form Integration',
			'type'  => 'select',
			'options' => [
				'none'     => 'Disabled',
				'cf7'     => 'Contact Form 7',
//				'gravity' => 'Gravity Forms',
//				'ninja'   => 'Ninja Forms',
			],
			'default' => 'none',
		],
	];

	// Register settings & fields
	public static function register() {
		foreach (self::$fields as $key => $field) {
			register_setting(self::OPTION_GROUP, "emfi_$key");

			add_settings_field(
				"emfi_$key",
				$field['label'],
				function() use ($key, $field) {
					$name = "emfi_$key";
					$value = get_option($name, $field['default']);

					if ($field['type'] === 'select') {
						echo "<select name='$name'>";
						foreach ($field['options'] as $optionValue => $label) {
							$selected = selected($value, $optionValue, false);
							echo "<option value='$optionValue' $selected>$label</option>";
						}
						echo "</select>";
					} else {
						echo "<input type='text' class='regular-text' name='$name' value='" . esc_attr($value) . "' />";
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
	public static function get($key) {
		if (!isset(self::$fields[$key])) return null;
		return get_option("emfi_$key", self::$fields[$key]['default']);
	}

	// Get all config as array
	public static function all() {
		$out = [];
		foreach (self::$fields as $key => $meta) {
			$out[$key] = self::get($key);
		}
		return $out;
	}

	public static function getLists() {

			$endpoint = self::get('api_url') . '/lists';

			$response = emfi_api_v2_request('GET', $endpoint);

			if (!$response || !isset($response['data']['records'])) {
				return null;
			}

			$lists = array();
			foreach ($response['data']['records'] as $list) {
				$lists[$list['general']['list_uid']] = $list['general']['name'];
			}

			return $lists;

	}

	public static function getFields($list_uid = null) {

		if ($list_uid == null){
			return null;
		}

		$endpoint = self::get('api_url') . "/lists/{$list_uid}/fields";

		$response = emfi_api_v2_request('GET', $endpoint);

		if (!$response || !isset($response['data']['records'])) {
			return null;
		}

		$fields = array();

		foreach ($response['data']['records'] as $field) {
			if ($field['visibility'] == "visible"){
				$fields[] = [
					'label' => $field['label'],
					'tag' => $field['tag'],
					'default_value' => $field['default_value'],
					'required' => $field['required'],
					'type' => $field['type']['identifier'],
				];
			}
		}

		return $fields;

	}

	public static function submitToList( string $list_uid, array $data ) : void {

		/* 1. Gather the mapped fields ------------------------------------ */
		$body   = [];     // final multipart payload
		$email  = '';     // promoted address (first email field wins)

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

			// Misc
			'bool'     => 'bool',
		];

		foreach ( $data as $field ) {

			$type = $field['type'] ?? 'text';
			if ( ! isset( $type2filter[ $type ] ) ) {
				continue;
			}

			$tag   = self::user_input($field['tag'] );
			$value = self::user_input($field['value'], $type2filter[ $type ] );

			if ( $type === 'email' && $email === '' ) {
				$email = $value;          // only first email field
			}

			$body[ $tag ] = $value;       // FNAME, LNAME, custom tags …
		}

		if ( $email === '' ) {
			error_log( '[Etchmail] skipped – no email address found.' );
			return;
		}

		$body['EMAIL']               = $email;          // Etchmail’s required field
		$body['details[source]']     = 'web';     // flat “details[…]” key
		$body['details[ip_address]'] = $_SERVER['REMOTE_ADDR'] ?? '';

		/* 2. Hit the endpoint ------------------------------------------- */

		error_log('[Etchmail] Mapped Data: ' . print_r($data, true));
		error_log('[Etchmail] Request Body: ' . print_r($body, true));

		$endpoint = self::get( 'api_url' ) . "/lists/{$list_uid}/subscribers";
		$resp     = emfi_api_v2_request( 'POST', $endpoint, $body );

		if ( ! is_array( $resp ) || ( $resp['status'] ?? '' ) !== 'success' ) {
			// Suppress logging if it's the known duplicate subscriber warning
			if ( isset( $resp['error'] ) && $resp['error'] === 'The subscriber already exists in this list.' ) {
				return;
			}

			error_log( '[Etchmail] API error: ' . wp_json_encode( $resp ) );
		}
	}


	public static function user_input($str, ?string $type = 'text') : string {
		switch ($type) {
			case 'email':
				return sanitize_email((string) $str);

			case 'url':
				return esc_url_raw((string) $str);

			case 'tel':
				// Strip non-numeric, allow leading +
				return preg_replace('/[^\d\+]/', '', (string) $str);

			case 'number':
				return is_numeric($str) ? (string) $str : '';

			case 'date':
				// Match YYYY-MM-DD format only
				return preg_match('/^\d{4}-\d{2}-\d{2}$/', $str) ? $str : '';

			case 'checkbox':
				// If it's an array (e.g. from checkboxes), implode values with comma
				if (is_array($str)) {
					// Ensure each value is a string and safe
					$str = array_map('sanitize_text_field', $str);
					return (string)implode(',', $str);
				}
				// For a single value checkbox
				return $str;
			case 'radio':
				// Convert array of options into a comma-separated string
				if (is_array($str)) {
					$str = array_map('sanitize_text_field', $str);
					return implode(',', $str);
				}
				return sanitize_text_field((string) $str);

			case 'textarea':
				return sanitize_textarea_field((string) $str);

			case 'bool':
				return ($str === 'on' || $str === '1' || $str === true) ? '1' : '0';

			case 'text':
			default:
				return sanitize_text_field((string) $str);
		}
	}
}
