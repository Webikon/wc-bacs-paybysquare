<?php

/*
Plugin Name: PAY by square pre WooCommerce
Description: Pridáva QR kód na sumarizačnú stránku platby prevodom
Version: 1.4.1
Author: Webikon (Matej Kravjar)
Author URI: https://webikon.sk
License: GPLv2+
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc-bacs-paybysquare
Domain Path: /languages
WC requires at least: 3.0
WC tested up to: 5.2.2
*/

namespace Webikon\Woocommerce_Plugin\WC_BACS_Paybysquare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/class-wc-bacs-paybysquare.php';
Plugin::run( __FILE__ );
