<?php defined('ABSPATH') || exit; // wp-etchmail-forms.php
/**
 * Plugin Name: Etchmail Form Integration
 * Plugin URI: https://github.com/Etchmail/wp-form-integration
 * Description: Etchmail signup form integrations
 * Version: 1.1.0
 * Author: Tiaan Kellerman
 * License: GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */



define( 'ETCHFOIN_PLUGIN', __FILE__ );

define( 'ETCHFOIN_PLUGIN_BASENAME', plugin_basename( ETCHFOIN_PLUGIN ) );

const ETCHFOIN_PLUGIN_VERSION = '1.1.0';

define( 'ETCHFOIN_PLUGIN_NAME', trim( dirname( ETCHFOIN_PLUGIN_BASENAME ), '/' ) );

define( 'ETCHFOIN_PLUGIN_DIR', untrailingslashit( dirname( ETCHFOIN_PLUGIN ) ) . DIRECTORY_SEPARATOR );

require_once ETCHFOIN_PLUGIN_DIR . '/load.php';
