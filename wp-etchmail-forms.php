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


define( 'EMFI_PLUGIN', __FILE__ );

define( 'EMFI_PLUGIN_BASENAME', plugin_basename( EMFI_PLUGIN ) );

define( 'EMFI_PLUGIN_NAME', trim( dirname( EMFI_PLUGIN_BASENAME ), '/' ) );

define( 'EMFI_PLUGIN_DIR', untrailingslashit( dirname( EMFI_PLUGIN ) ) . DIRECTORY_SEPARATOR );

require_once EMFI_PLUGIN_DIR . '/load.php';
