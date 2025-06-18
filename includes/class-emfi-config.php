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
			'EtchMail Settings',
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
}
