<?php
/**
 * You can include this file in a custom file to run the update script.
 * This is useful if you want to run the update script manually or from a cron job.
 *
 * Example :
 *      <?php
 *      $id = 'your-account-id';      
 *      $key = 'your-license-key';
 *      include_once __DIR__ . '/run.php';
 *      $results = ozh_yourls_geoip2_update_client_run($id, $key);
 *      // do something with $results
 *      var_dump($results);
 *      ?>
 */

function ozh_yourls_geoip2_update_client_run($id, $key) {
    require 'vendor/autoload.php';

    // configuration
    $client = new \danielsreichenbach\GeoIP2Update\Client(array(
        'account_id' => $id,
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
