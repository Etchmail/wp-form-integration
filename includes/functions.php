<?php defined('ABSPATH') || exit; // include/function.php


function emfi_api_v2_request( string $method, string $endpoint, array $body = [], ?array $config = null ) {

	$config = $config ?: EmfiConfig::all();

	if ( empty( $config['api_url'] ) || empty( $config['api_key'] ) ) {
		error_log( 'Etchmail API: Config missing or incomplete' );
		return false;
	}

	/* NOTE: no Content-Type header – WP will add the multipart boundary */
	$args = [
		'method'  => $method,
		'headers' => [ 'X-API-KEY' => $config['api_key'] ],
		'timeout' => 30,
	];

	if ( $method === 'POST' && ! empty( $body ) ) {
		$args['body'] = $body;                 // array ⇒ multipart/form-data
	}

	$resp = wp_remote_request( $endpoint, $args );

	if ( is_wp_error( $resp ) ) {
		error_log( 'Etchmail API error: ' . $resp->get_error_message() );
		return false;
	}
	return json_decode( wp_remote_retrieve_body( $resp ), true );
}