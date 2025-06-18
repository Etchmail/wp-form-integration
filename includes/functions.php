<?php defined('ABSPATH') || exit; // include/function.php


function emfi_api_v2_request($method, $endpoint, $data = array(), $config = null) {
	if (!$config) {
		if (!class_exists('EmfiConfig')) {
			error_log('EtchMail API: EmfiConfig class not found');
			return false;
		}
		$config = EmfiConfig::all();
	}

	if (empty($config['api_url']) || empty($config['api_key'])) {
		error_log('EtchMail API: Config missing or incomplete');
		return false;
	}

	$headers = array(
		'Content-Type' => 'application/json',
		'X-API-KEY'     => $config['api_key'],
	);

	$args = array(
		'method'  => $method,
		'headers' => $headers,
		'timeout' => 30,
	);

	if ($method === 'POST' && !empty($data)) {
		$args['body'] = json_encode($data);
	}

	$response = wp_remote_request($endpoint, $args);

	if (is_wp_error($response)) {
		error_log('EtchMail API error: ' . $response->get_error_message());
		return false;
	}

	$body = wp_remote_retrieve_body($response);
	$decoded = json_decode($body, true);

	// Optional: log response for debugging
	// error_log('EtchMail API Response: ' . print_r($decoded, true));

	return $decoded;
}