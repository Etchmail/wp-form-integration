<?php defined( 'ABSPATH' ) || exit;


class ETCHFOIN_AdminSettings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'init_settings' ] );
		add_action( 'wp_ajax_test_etchmail_connection', [ $this, 'ajax_test_connection' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function add_admin_menu() {
		add_options_page(
			'Etchmail Form Integration',
			'Etchmail Integration',
			'manage_options',
			'etchmail-form-integration',
			[ $this, 'render_settings_page' ]
		);
	}

	public function init_settings() {
		if ( class_exists( 'ETCHFOINConfig' ) ) {
			ETCHFOINConfig::register(); // This must be called early in admin load
		}
	}

	public function render_settings_page() {
		include ETCHFOIN_PLUGIN_DIR . 'admin/assets/etchfoin-admin.php';
	}

	public function ajax_test_connection() {
		check_ajax_referer( 'etchmail_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'etchfoin' ) );
			return;
		}

		$config = ETCHFOINConfig::all();

		// check if an api url & key is set
		if ( empty( $config['api_url'] ) || empty( $config['api_key'] ) ) {
			wp_send_json_error( __( 'Missing API URL or Private Key.', 'etchfoin' ) );
			return;
		}

		$base_url = esc_url_raw( $config['api_url'] );
		$endpoint = rtrim( $base_url, '/' ) . '/lists';

		$response = etchfoin_api_v2_request( 'GET', $endpoint, [], $config );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Request error: ' . $response->get_error_message() );
			return;
		}

		if ( $response && isset( $response['status'] ) && 'success' === $response['status'] ) {
			wp_send_json_success( __( 'Connection successful!', 'etchfoin' ) );
		} else {
			wp_send_json_error( __( 'Connection failed. Please check your API settings.', 'etchfoin' ) );
		}
	}

	public function enqueue_assets( $hook ) {
		// WordPress passes the current screen’s hook suffix, e.g. 'settings_page_etchmail-fi'
		if ( 'settings_page_etchmail-form-integration' !== $hook ) {
			return; // Load nothing elsewhere in wp-admin
		}

		wp_register_script(
			'etchfoin-admin',
			plugins_url( 'admin/assets/etchfoin-admin.js', ETCHFOIN_PLUGIN ),
			[ 'jquery' ],
			ETCHFOIN_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'etchfoin-admin',
			'etchfoinData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'etchmail_nonce' ),
			]
		);
		wp_enqueue_script( 'etchfoin-admin' );
	}

}

new ETCHFOIN_AdminSettings();


