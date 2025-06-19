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
}
