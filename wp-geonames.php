<?php
/*
Plugin Name: WP GeoNames
Author: Jacques Malgrange, Bhujagendra Ishaya
Text Domain: wpGeonames
Domain Path: /lang
Description: Allows you to insert all or part of the global GeoNames database in your WordPress base.
Version: 2.0.2
Author URI: https://www.boiteasite.fr
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ********* MANUAL - ADD A LOCAL FILE IN WP-CONTENT/UPLOADS *********
// $geoManual = '/tmp/US.txt'; // '/tmp/ES.txt';
// *******************************************************************


// initialize
require_once 'vendor/autoload.php';

WPGeonames\Core::Factory( __FILE__ );

?>
