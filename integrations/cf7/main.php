<?php defined('ABSPATH') || exit; // integrations/cf7/main.php

class EMFI_CF7 {

	/**
	 * Field definitions for CF7 integration.
	 */
	private static $fields = [
		'enabled' => [
			'label'   => 'Enabled',
			'type'    => 'checkbox',
			'default' => 'false',
		],
		'list_uid' => [
			'label'   => 'List UID',
			'type'    => 'text',
			'default' => '',
		],
		'list_name' => [
			'label'   => 'List Name',
			'type'    => 'text',
			'default' => '',
		],
		'mapped_fields' => [
			'label'   => 'Mapped Fields',
			'type'    => 'mapped-field',
			'default' => '',
		],
	];

	public function __construct() {
		add_action('wpcf7_admin_init', [$this, 'register_settings']);
		add_action('wpcf7_admin_init', [$this, 'add_admin_panel'], 15);
		add_action('wp_ajax_emfi_get_lists', [$this, 'ajax_get_lists']); // ✅ FIXED
	}

	/**
	 * Register CF7-specific settings.
	 */
	public function register_settings() {
		foreach (self::$fields as $key => $field) {
			register_setting('EMFI_CF7', "emfi_cf7_{$key}");
		}
	}

	/**
	 * Add Etchmail tab to the CF7 form editor.
	 */
	public function add_admin_panel() {
		add_filter('wpcf7_editor_panels', function ($panels) {
			$panels['etchmail-panel'] = [
				'title'    => 'Etchmail Integration',
				'callback' => function () {
					include EMFI_PLUGIN_DIR . 'integrations/cf7/assets/view.php';
				}
			];
			return $panels;
		});
	}

	/**
	 * AJAX: Fetch mailing lists from Etchmail API.
	 */
	public function ajax_get_lists() {
		check_ajax_referer('etchmail_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
			return;
		}

		if (!class_exists('EmfiConfig') || !method_exists('EmfiConfig', 'getLists')) {
			wp_send_json_error('Etchmail config not available.');
			return;
		}

		$lists = EmfiConfig::getLists(); // returns assoc array

		if (is_array($lists)) {
			// Convert associative array to array of objects for JS
			$formatted = [];
			foreach ($lists as $uid => $name) {
				$formatted[] = [
					'list_uid' => $uid,
					'name'     => $name,
				];
			}
			wp_send_json_success($formatted);
		}
	}
}

new EMFI_CF7();
