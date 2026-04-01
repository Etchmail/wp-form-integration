<?php defined( 'ABSPATH' ) || exit; // includes/function.php


function etchfoin_api_v2_request( string $method, string $endpoint, array $body = [], ?array $config = null ) {

	$config = $config ?: ETCHFOINConfig::all();

	if ( empty( $config['api_url'] ) || empty( $config['api_key'] ) ) {
		etchfoin_logging( 'Etchmail API: Config missing or incomplete', 'error' );
		return false;
	}

	/* NOTE: no Content-Type header – WP will add the multipart boundary */
	$args = [
		'method'  => $method,
		'headers' => [ 'X-API-KEY' => sanitize_text_field( $config['api_key'] ) ],
		'timeout' => 30,
	];

	if ( 'POST' === $method && ! empty( $body ) ) {
		$args['body'] = $body;                 // array ⇒ multipart/form-data
	}

	$endpoint = esc_url_raw( $endpoint );
	$resp     = wp_remote_request( $endpoint, $args );

	if ( is_wp_error( $resp ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			etchfoin_logging( 'Etchmail API error: ' . $resp->get_error_message(), 'error' );
		}
		return false;
	}

	return json_decode( wp_remote_retrieve_body( $resp ), true );
}

function etchfoin_logging( $message, $type = 'info' ): void {
	if ( ! defined( 'ETCHFOIN_DEBUG' ) || ! ETCHFOIN_DEBUG ) {
		return;
	}

	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only logging.
		error_log( sprintf( '[Etchmail] [%s] %s', $type, $message ) );
	}
}