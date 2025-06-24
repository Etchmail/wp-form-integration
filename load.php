<?php defined('ABSPATH') || exit; // load.php

// init admin settings
require_once (EMFI_PLUGIN_DIR . 'includes/functions.php');
require_once (EMFI_PLUGIN_DIR . 'includes/class-emfi-config.php');
require_once (EMFI_PLUGIN_DIR . 'admin/settings.php');

// Run integration only if the selected plugin is enabled and available
add_action('plugins_loaded', function () {
        if ( ! class_exists( 'EmfiConfig' ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'Etchmail: Emfi_Config not loaded.' );
                }
                return;
        }

	$enabled = EmfiConfig::get('enabled_form');

        if ( ! $enabled ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( 'Etchmail: No form integration selected.' );
                }
                return;
        }

	// check if the dependency is enabled.
	$plugin_ready = match ($enabled) {
		'cf7'     => defined('WPCF7_VERSION'),
		'formidable' => class_exists( 'FrmAppHelper' ),
		'fluent' => defined( 'FLUENTFORM' ),
		// 'ninja'   => class_exists('Ninja_Forms'),
		default   => false,
	};

	if ($enabled == 'none'){
		return; // disables the loading
	}

        if ( ! $plugin_ready ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "Etchmail: {$enabled} Integration could not be loaded." );
                }
                return;
        }


	// Load the correct integration file
	$integration_file = plugin_dir_path(__FILE__) . "integrations/{$enabled}/main.php";

        if ( file_exists( $integration_file ) ) {
                require_once $integration_file;
        } else {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        error_log( "Etchmail: Integration file for [$enabled] not found." );
                }
        }
});