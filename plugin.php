<?php
/*
Plugin Name: GeoIP Update plugin
Plugin URI: https://github.com/ozh/yourls-geoip-update
Description: Update the GeoIP database
Version: 1.2
Author: Ozh
Author URI: http://ozh.org/
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'ozh_yourls_geoupd_add_page' );
function ozh_yourls_geoupd_add_page() {
    yourls_register_plugin_page( 'geoipupdate', 'GeoIP DB update', 'ozh_yourls_geoupd_do_page' );
}

// Use this local GeoIP database if present instead of the one shipped with YOURLS
yourls_add_action( 'plugins_loaded', 'ozh_yourls_geoupd_maybe_use_local_db' );
function ozh_yourls_geoupd_maybe_use_local_db() {
    $local_db = __DIR__ .'/GeoLite2-Country/GeoLite2-Country.mmdb';
    if( is_readable($local_db) ) {
        yourls_add_filter('geo_ip_path_to_db', function () use ($local_db) {
            return $local_db;
        });
    }
}

// Display admin page
function ozh_yourls_geoupd_do_page() {

    echo <<<HTML
		<h2>GeoIP update</h2>
		<p>This plugin updates the database shipped with YOURLS.</p>
        <p>It is recommended to run this once a month or so. This requires a <a href="https://www.maxmind.com/en/account/login">Maxmind account</a> and a free license key.</p>
HTML;

    // Check if a form was submitted
    if( isset($_POST['geoip_api_key']) ) {
        // Check nonce
        yourls_verify_nonce( 'geoip_update' );

        // Process form
        ozh_yourls_geoupd_update_db();
    }

    // Get value from database
    $geoip_api_id  = yourls_get_option( 'geoip_api_id' );
    $geoip_api_key = yourls_get_option( 'geoip_api_key' );

    // Create nonce
    $nonce = yourls_create_nonce( 'geoip_update' );

    echo <<<HTML
		<form method="post">
		<input type="hidden" name="nonce" value="$nonce" />
		<p><label for="geoip_api_id">Maxmind account ID</label> <input type="text" class="text" id="geoip_api_id" name="geoip_api_id" value="$geoip_api_id" /></p>
		<p><label for="geoip_api_key">Maxmind license key</label> <input type="text" class="text" id="geoip_api_key" name="geoip_api_key" value="$geoip_api_key" /></p>
		<p><input type="submit" class="button" value="Update DB" /></p>
		</form>
HTML;

    ozh_yourls_geoupd_display_db_last_modified();

    if($last_check = yourls_get_option('geoip_last_check')) {
        echo '<p>Last check: '.yourls_date_i18n(yourls_get_datetime_format('Y-m-d H:i:s'), yourls_get_timestamp( $last_check )).'</p>';
    }}

// Update database
function ozh_yourls_geoupd_update_db() {
    $id  = $_POST['geoip_api_id'];
    $key = $_POST['geoip_api_key'];

    if ($key && $id) {
        // Validate geoip_api_key and update
        $id  = ozh_yourls_sanitize_api_key($id);
        $key = ozh_yourls_sanitize_api_key($key);
        yourls_update_option('geoip_api_id', $id);
        yourls_update_option('geoip_api_key', $key);

        // Run the updater
        require_once( __DIR__ . '/run.php' );
        $results = ozh_yourls_geoip2_update_client_run($id, $key);

        // check if the update was successful
        if( $results['updated'] ) {
            echo '<div class="success"><p>Database updated successfully.</p>';
            foreach( $results['updated'] as $update ) {
                echo '<p>' . $update . '</p>';
            }
            echo '</div>';
            yourls_update_option('geoip_last_check', time() );
        } else {
            echo '<div class="error"><p>Database not updated.</p>';
            foreach( $results['errors'] as $error ) {
                echo '<p>' . $error . '</p>';
            }
            echo '</div>';
        }

    }
}

// Sanitize the api key
function ozh_yourls_sanitize_api_key( $key ) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
}

// Echo last modified date of the database on Maxmind server
function ozh_yourls_geoupd_display_db_last_modified() {
    // check if file last-modified.txt exists
    if ( file_exists(__DIR__ . '/GeoLite2-Country/last-modified.txt') ) {
        $last_update = substr(file_get_contents(__DIR__ . '/GeoLite2-Country/last-modified.txt'), 0, 10);
        echo "<p>GeoIP DB date: " .yourls_date_i18n( yourls_get_datetime_format('Y-m-d H:i:s'), yourls_get_timestamp( $last_update )) . "</p>";
    }
}

