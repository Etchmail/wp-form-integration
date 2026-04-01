<?php
/**
 * Etchmail Form Integration — Uninstall
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all options stored by the plugin.
 *
 * @package EtchmailFormIntegration
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/*  1. Static plugin options                                          */
/* ------------------------------------------------------------------ */
$static_options = array(
	'etchfoin_api_url',
	'etchfoin_api_key',
	'etchfoin_enabled_form',
	'etchfoin_enable_standalone',
	'etchfoin_standalone_selected_list',
);

foreach ( $static_options as $option ) {
	delete_option( $option );
}

/* ------------------------------------------------------------------ */
/*  2. Dynamic CF7 per-form options  (etchfoin_cf7_{id}_{key})        */
/* ------------------------------------------------------------------ */
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup on uninstall only.
$cf7_options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'etchfoin_cf7_' ) . '%'
	)
);

foreach ( $cf7_options as $option ) {
	delete_option( $option );
}

/* ------------------------------------------------------------------ */
/*  3. Dynamic Standalone per-list options                            */
/*     (etchfoin_standalone_{uid}_fields / _styles)                   */
/* ------------------------------------------------------------------ */

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup on uninstall only.
$standalone_options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'etchfoin_standalone_' ) . '%'
	)
);

foreach ( $standalone_options as $option ) {
	delete_option( $option );
}
