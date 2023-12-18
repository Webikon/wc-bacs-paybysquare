<?php
/**
 * This file is part of WordPress plugin: PAY by square for WooCommerce
 *
 * @package Webikon\Woocommerce_Plugin\WC_BACS_Paybysquare
 * @author Webikon (Matej Kravjar) <hello@webikon.sk>
 * @copyright 2017 Webikon & Matej Kravjar
 * @license GPLv2+
 *
 * Plugin Name: PAY by square for WooCommerce
 * Description: Adds a payment QR code on summary page of direct bank transfer
 * Version: 1.4.2
 * Author: Webikon (Matej Kravjar)
 * Author URI: https://webikon.sk
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-bacs-paybysquare
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 5.4.1
 */

namespace Webikon\Woocommerce_Plugin\WC_BACS_Paybysquare;

// protect against direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// declare compatibility with High-Performace Order Storage (HPOS).
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

require __DIR__ . '/src/class-logger.php';
require __DIR__ . '/src/class-plugin.php';
Plugin::run( __FILE__ );
