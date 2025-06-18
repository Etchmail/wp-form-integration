<?php defined('ABSPATH') || exit; // wp-etchmail-forms.php
/**
 * Plugin Name: Etchmail Contact Form Integration
 * Plugin URI: https://github.com/Etchmail/wp-form-integration
 * Description: Etchmail signup form integrations
 * Version: 1.0.0
 * Author: Tiaan Kellerman
 * License: IDK
 */


define( 'EMFI_PLUGIN', __FILE__ );

define( 'EMFI_PLUGIN_BASENAME', plugin_basename( EMFI_PLUGIN ) );

define( 'EMFI_PLUGIN_NAME', trim( dirname( EMFI_PLUGIN_BASENAME ), '/' ) );

define( 'EMFI_PLUGIN_DIR', untrailingslashit( dirname( EMFI_PLUGIN ) ) . DIRECTORY_SEPARATOR );

require_once EMFI_PLUGIN_DIR . '/load.php';