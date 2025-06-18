<?php defined('ABSPATH') || exit; // integrations/cf7/main.php

class EMFI_CF7 {
	public function __construct() {
		add_action('wpcf7_admin_init', array($this, 'add_cf7_admin_init'), 15);
	}

	public function add_cf7_admin_init() {
		add_filter('wpcf7_editor_panels', array($this, 'add_cf7_panel'));
	}

	// Add EtchMail panel to CF7 form editor
	public function add_cf7_panel($panels) {
		$panels['etchmail-panel'] = array(
			'title' => 'EtchMail Integration',
			'callback' => array($this, 'cf7_panel_content')
		);
		return $panels;
	}
}

new EMFI_CF7();