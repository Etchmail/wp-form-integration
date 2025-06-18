<?php defined('ABSPATH') || exit; // admin/settings.php


class Emfi_Admin_Settings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'init_settings' ] );
		add_action( 'wp_ajax_test_etchmail_connection', [ $this, 'ajax_test_connection' ] );
	}

	public function add_admin_menu() {
		add_options_page(
			'Etchmail Form Integration',
			'Etchmail Integration',
			'manage_options',
			'etchmail-fi',
			[ $this, 'render_settings_page' ]
		);
	}

	public function init_settings() {
		if (class_exists('EmfiConfig')) {
			EmfiConfig::register(); // This must be called early in admin load
		}
	}

	public function render_settings_page() {
		include EMFI_PLUGIN_DIR . 'admin/assets/view.php';
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$config = EmfiConfig::all();

		if ( empty( $config['api_url'] ) || empty( $config['api_key'] ) ) {
			wp_send_json_error( 'Missing API URL or Private Key.' );
			return;
		}

		$endpoint = rtrim( $config['api_url'], '/' ) . '/lists';

		$response = emfi_api_v2_request( 'GET', $endpoint, [], $config );

		// Log raw response for debugging
		error_log('Etchmail Test Connection Response: ' . print_r($response, true));

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Request error: ' . $response->get_error_message() );
			return;
		}

		if ( $response && isset( $response['status'] ) && $response['status'] === 'success' ) {
			wp_send_json_success( 'Connection successful!' );
		} else {
			// Log full failure response
			error_log('Etchmail API failed: ' . json_encode($response));
			wp_send_json_error( 'Connection failed. Please check your API settings.' );
		}
	}
}

new Emfi_Admin_Settings();


