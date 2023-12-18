<?php
/**
 * This file is part of WordPress plugin: PAY by square for WooCommerce
 *
 * @package Webikon\Woocommerce_Plugin\WC_BACS_Paybysquare
 * @author Webikon (Matej Kravjar) <hello@webikon.sk>
 * @copyright 2017 Webikon & Matej Kravjar
 * @license GPLv2+
 */

namespace Webikon\Woocommerce_Plugin\WC_BACS_Paybysquare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin logger.
 *
 * The class is derivation of Psr\Log\AbstractLogger, but to avoid
 * dependencies, the abstract class itself is not used.
 *
 * @license LICENSE-Psr-Log
 */
class Logger {
	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string  $level   Log level.
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function log( $level, $message, array $context = [] ) {
		$context['source'] = 'wc-bacs-paybysquare';
		wc_get_logger()->log( $level, $message, $context );
	}

	/**
	 * System is unusable.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function emergency( $message, array $context = [] ) {
		$this->log( 'emergency', $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function alert( $message, array $context = [] ) {
		$this->log( 'alert', $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function critical( $message, array $context = [] ) {
		$this->log( 'critical', $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function error( $message, array $context = [] ) {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function warning( $message, array $context = [] ) {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function notice( $message, array $context = [] ) {
		$this->log( 'notice', $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function info( $message, array $context = [] ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string  $message Log message.
	 * @param mixed[] $context Log context.
	 *
	 * @return void
	 */
	public function debug( $message, array $context = [] ) {
		$this->log( 'debug', $message, $context );
	}
}
