<?php defined('ABSPATH') || exit; // wp-etchmail-forms.php
/**
 * Plugin Name: Etchmail Form Integration
 * Plugin URI:  https://github.com/Etchmail/wp-form-integration
 * Description: Etchmail signup form integrations
 * Version:     2.0.0
 * Author:      Tiaan Kellerman
 * Author URI:  https://github.com/Etchmail/wp-form-integration
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: etchfoin
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 8.0
 */



define( 'ETCHFOIN_PLUGIN', __FILE__ );

define( 'ETCHFOIN_PLUGIN_BASENAME', plugin_basename( ETCHFOIN_PLUGIN ) );

const ETCHFOIN_PLUGIN_VERSION = '2.0.0';

define( 'ETCHFOIN_PLUGIN_NAME', trim( dirname( ETCHFOIN_PLUGIN_BASENAME ), '/' ) );

define( 'ETCHFOIN_PLUGIN_DIR', untrailingslashit( dirname( ETCHFOIN_PLUGIN ) ) . DIRECTORY_SEPARATOR );

// Debug mode – set to false for production
if ( ! defined( 'ETCHFOIN_DEBUG' ) ) {
	define( 'ETCHFOIN_DEBUG', false );
}

require_once ETCHFOIN_PLUGIN_DIR . '/load.php';
