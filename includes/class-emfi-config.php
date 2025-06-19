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
			'text'     => 'text',
			'email'    => 'email',
			'url'      => 'url',
			'checkbox' => 'bool',
		];

		foreach ( $data as $field ) {

			$type = $field['type'] ?? 'text';
			if ( ! isset( $type2filter[ $type ] ) ) {
				continue;
			}

			$tag   = self::user_input( (string) $field['tag'] );
			$value = self::user_input( (string) $field['value'], $type2filter[ $type ] );

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
		$endpoint = self::get( 'api_url' ) . "/lists/{$list_uid}/subscribers";
		$resp     = emfi_api_v2_request( 'POST', $endpoint, $body );

		if ( ! is_array( $resp ) || ( $resp['status'] ?? '' ) !== 'success' ) {
			error_log( '[Etchmail] API error: ' . wp_json_encode( $resp ) );
		}
	}

	/* ---------- Simple sanitiser ---------- */
	public static function user_input( string $str, ?string $type = 'text' ) : string {
		switch ( $type ) {
			case 'email': return sanitize_email( $str );
			case 'url':   return esc_url_raw( $str );
			case 'bool':  return $str === 'on' || $str === '1' ? '1' : '0';
			default:      return sanitize_text_field( $str );
		}
	}
}
