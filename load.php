<?php defined('ABSPATH') || exit; // load.php

// init admin settings
require_once (EMFI_PLUGIN_DIR . 'includes/functions.php');
require_once (EMFI_PLUGIN_DIR . 'includes/class-emfi-config.php');
require_once (EMFI_PLUGIN_DIR . 'admin/settings.php');

// Run integration only if the selected plugin is enabled and available
add_action('plugins_loaded', function () {
	if (!class_exists('EmfiConfig')) {
		error_log('EtchMail: Emfi_Config not loaded.');
		return;
	}

	$enabled = EmfiConfig::get('enabled_form');

	if (!$enabled) {
		error_log('EtchMail: No form integration selected.');
		return;
	}

	$plugin_ready = match ($enabled) {
		'cf7'     => defined('WPCF7_VERSION'),
		// 'gravity' => class_exists('GFForms'),
		// 'ninja'   => class_exists('Ninja_Forms'),
		default   => false,
	};

	if (!$plugin_ready) {
		error_log("EtchMail: Selected form plugin [$enabled] not installed or active.");
		return;
	}

	// Load the correct integration file
	$integration_file = plugin_dir_path(__FILE__) . "integrations/{$enabled}/main.php";

	if (file_exists($integration_file)) {
		require_once $integration_file;
	} else {
		error_log("EtchMail: Integration file for [$enabled] not found.");
	}
});