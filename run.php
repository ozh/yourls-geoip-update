<?php
/**
 * You can include this file in a custom file to run the update script.
 * This is useful if you want to run the update script manually or from a cron job.
 *
 * Example :
 *      <?php
 *      $key = 'your-license-key';
 *      include_once __DIR__ . '/run.php';
 *      $results = ozh_yourls_geoip2_update_client_run($key);
 *      // do something with $results
 *      ?>
 */

function ozh_yourls_geoip2_update_client_run($key) {
    require 'vendor/autoload.php';

    // configuration
    $client = new \tronovav\GeoIP2Update\Client(array(
        'license_key' => $key,
        'dir' => __DIR__,
        'editions' => array('GeoLite2-Country'),
    ));

    // run update
    $client->run();

    // return update status and errors
    return array(
        'updated' => $client->updated(),
        'errors' => $client->errors(),
    );
}
