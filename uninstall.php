<?php
/**
 * GeoIP2 Update uninstall script
 *
 * This file is executed when the plugin is uninstalled on YOURLS 1.8.3 or later.
 */

// No direct call.
if( !defined( 'YOURLS_UNINSTALL_PLUGIN' ) ) die();

// The uninstallation process itself

// Delete plugin's custom option
yourls_delete_option( 'geoip_api_key' );
yourls_delete_option( 'geoip_last_check' );

// That's it.
