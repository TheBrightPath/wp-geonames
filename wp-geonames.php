<?php
/*
Plugin Name: WP GeoNames
Author: Jacques Malgrange, Bhujagendra Ishaya
Text Domain: wpGeonames
Domain Path: /lang
Description: Allows you to insert all or part of the global GeoNames database in your WordPress base.
Version: 4.1.1
Author URI: https://www.boiteasite.fr
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
{
    http_response_code( 404 );
    exit;
}

// ignore admin heartbeats
if ( ! defined( 'TBP_IS_ADMIN_HEARTBEAT' ) )
{
    define(
        'TBP_IS_ADMIN_HEARTBEAT',
        (
            'heartbeat' === ( $_REQUEST['action'] ?? false )
            && '/wp-admin/admin-ajax.php' === $_SERVER['REQUEST_URI']
        )
    );
}

if ( TBP_IS_ADMIN_HEARTBEAT )
{
    return;
}

// ********* MANUAL - ADD A LOCAL FILE IN WP-CONTENT/UPLOADS *********
// $geoManual = '/tmp/US.txt'; // '/tmp/ES.txt';
// *******************************************************************

// initialize
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

WPGeonames\Core::Factory( __FILE__ );

?>
