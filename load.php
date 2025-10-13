<?php defined( 'ABSPATH' ) || exit; // load.php

// init
require_once( ETCHFOIN_PLUGIN_DIR . 'includes/etchfoin-functions.php' );
require_once( ETCHFOIN_PLUGIN_DIR . 'includes/class-etchfoin-config.php' );
require_once( ETCHFOIN_PLUGIN_DIR . 'admin/etchfoin-admin-init.php' );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'ETCHFOINConfig' ) ) {
		etchfoin_logging( 'Etchmail: Emfi_Config not loaded.', 'error' );
	}

	$enabled = ETCHFOINConfig::get( 'enabled_form' );

	if ( ! $enabled ) {
		etchfoin_logging( 'Etchmail: No form integration selected.', 'error' );
	}

	// check if the dependency is enabled.
	$plugin_ready = match ( $enabled ) {
		'cf7' => defined( 'WPCF7_VERSION' ),
		// 'formidable' => class_exists( 'FrmAppHelper' ),
		// 'fluent' => defined( 'FLUENTFORM' ),
		// 'ninja'   => class_exists('Ninja_Forms'),
		default => false,
	};

	if ( $enabled == 'none' ) {
		return; // disables the loading
	}
	$integration_file = plugin_dir_path( __FILE__ ) . "integrations/{$enabled}/main.php";

	if ( file_exists( $integration_file ) ) {
		require_once $integration_file;
	} else {
		etchfoin_logging( "Etchmail: Integration file for [$enabled] not found.", 'error' );
	}
});

function my_load_scripts($hook) {

	// create my own version codes
	error_log('hellow');

}
add_action('', 'my_load_scripts');