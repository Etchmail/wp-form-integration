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

	// Check if the dependency for the selected integration is active.
	$plugin_ready = match ( $enabled ) {
		'contactform7' => defined( 'WPCF7_VERSION' ),
		default        => false,
	};

	if ( 'none' !== $enabled ) {
		// Only allow known integration slugs to prevent directory traversal.
		$allowed = array( 'cf7', 'contactform7' );
		if ( in_array( $enabled, $allowed, true ) ) {
			$integration_file = plugin_dir_path( __FILE__ ) . 'integrations/' . $enabled . '/main.php';

			if ( file_exists( $integration_file ) ) {
				require_once $integration_file;
			} else {
				etchfoin_logging( 'Etchmail: Integration file for [' . $enabled . '] not found.', 'error' );
			}
		}
	}

	// Load standalone shortcode form (independent of the form-plugin integration)
	$standalone_enabled = ETCHFOINConfig::get( 'enable_standalone' );
	if ( 'yes' === $standalone_enabled ) {
		$standalone_file = plugin_dir_path( __FILE__ ) . 'integrations/standalone/main.php';
		if ( file_exists( $standalone_file ) ) {
			require_once $standalone_file;
		} else {
			etchfoin_logging( 'Etchmail: Standalone integration file not found.', 'error' );
		}
	}
});