<?php defined('ABSPATH') || exit; // integrations/cf7/main.php

class EMFI_CF7 {

	/**
	 * Field definitions for CF7 integration.
	 */
	public $form = null;

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
		add_action('wpcf7_admin_init', [$this, 'cf7_register_editor_panel'], 15);
		add_action('wp_ajax_emfi_get_lists', [$this, 'ajax_get_lists']);
		add_action('wp_ajax_emfi_save_cf7_settings', [$this, 'ajax_save_settings']);
	}

	/**
	 * Add Etchmail tab to the CF7 form editor.
	 */



	public function cf7_register_editor_panel() {
		add_filter('wpcf7_editor_panels', function ($panels) {

			$panels['etchmail-panel'] = [
				'title'    => 'Etchmail Integration',
				'callback' => array($this, 'cf7_render_editor_panel'),
			];

			return $panels;
		});
	}

	public function cf7_render_editor_panel($form) {
		$this->form = $form; //



		foreach (self::$fields as $key => $field) {
			register_setting('EMFI_CF7', "emfi_cf7_{$this->form->id}_{$key}");
		}

		// todo search for tags and add them to the mapped options
		$tags = $this->form->scan_form_tags();
		print_r($tags);

		// Now include the view and pass the settings if needed
		include EMFI_PLUGIN_DIR . 'integrations/cf7/assets/view.php';
	}

	public function ajax_save_settings() {
		check_ajax_referer('etchmail_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}

		$form_id  = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
		$enabled  = isset($_POST['enabled']) ? sanitize_text_field($_POST['enabled']) : '0';
		$list_uid = isset($_POST['list_uid']) ? sanitize_text_field($_POST['list_uid']) : '';

		if (!$form_id) {
			wp_send_json_error('Missing form ID');
		}

		update_option("emfi_cf7_{$form_id}_enabled", $enabled);
		update_option("emfi_cf7_{$form_id}_list_uid", $list_uid);

		wp_send_json_success();
	}

	public function getOption($key, $form_id,$default = null) {
		$value = get_option("emfi_cf7_{$form_id}_{$key}");
		return $value !== false ? $value : $default;
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
