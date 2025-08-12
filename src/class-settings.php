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

/**
 * PAY by square settings.
 */
class Settings extends \WC_Integration {
	/**
	 * Reference to service used in settings.
	 *
	 * @var string
	 */
	const SERVICE_REFERENCE = 'app.bysquare.com';

	/**
	 * Regular expression for invalid character for QR platba beneficiary.
	 *
	 * @var string
	 */
	const QRPLATBA_INVALID = ';[^0-9A-Za-z $%+./:-];';

	/**
	 * Setup integration object.
	 */
	public function __construct() {
		$this->id                 = Plugin::INTEGRATION_ID;
		$this->method_title       = __( 'PAY by square', 'wc-bacs-paybysquare' );
		$this->method_description = __( 'Displays QR codes for convenient direct bank transfer payments', 'wc-bacs-paybysquare' );

		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_integration_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Define setting fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'beneficiary' => [
				'title'             => __( 'Beneficiary name', 'wc-bacs-paybysquare' ),
				'type'              => 'text',
				'description'       => __( 'Name of person or organization receiving money', 'wc-bacs-paybysquare' ),
				'default'           => '',
				'desc_tip'          => true,
				'sanitize_callback' => function ( string $value ) {
					if ( preg_match( static::QRPLATBA_INVALID, $value ) ) {
						add_action(
							'admin_notices',
							function () {
								echo '<div class="notice notice-warning is-dismissible"><p><b>'
								. sprintf(
									/* translators: %s: field name */
									esc_html__( 'Field "%s" does contain character, that is invalid for Czech QR code.', 'wc-bacs-paybysquare' ),
									esc_html__( 'Beneficiary name', 'wc-bacs-paybysquare' )
								)
								. '</b></p><p>'
								. esc_html__( 'If you are not using Czech QR code, you may safely ignore this warning.', 'wc-bacs-paybysquare' )
								. '</p><p>'
								. sprintf(
									/* translators: 1: valid digits, 2: valid letters, 3: valid symbols */
									esc_html__( 'Valid characters are digits %1$s, letters %2$s, a space, and symbols %3$s', 'wc-bacs-paybysquare' ),
									'0..9',
									'A..Z a..z',
									'$ % + - . / :'
								)
								. '</p></div>';
							}
						);
					}
					return $value;
				},
			],
			'username'    => [
				'title'       => __( 'Username', 'wc-bacs-paybysquare' ),
				'type'        => 'text',
				/* translators: %s: service name */
				'description' => sprintf( __( 'Your Username for %s service', 'wc-bacs-paybysquare' ), static::SERVICE_REFERENCE ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'password'    => [
				'title'       => __( 'Password', 'wc-bacs-paybysquare' ),
				'type'        => 'password',
				/* translators: %s: service name */
				'description' => sprintf( __( 'Your Password for %s service', 'wc-bacs-paybysquare' ), static::SERVICE_REFERENCE ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'information' => [
				'title'       => __( 'Checkout information', 'wc-bacs-paybysquare' ),
				'type'        => 'text',
				'description' => __( 'Text appended to your BACS title, advertising QR code availability', 'wc-bacs-paybysquare' ),
				'default'     => __( '(payment QR code)', 'wc-bacs-paybysquare' ),
				'desc_tip'    => true,
			],
			'display'     => [
				'title'       => __( 'Display QR code', 'wc-bacs-paybysquare' ),
				'description' => __( 'Setting controlling which type of QR should be displayed', 'wc-bacs-paybysquare' ),
				'type'        => 'select',
				'options'     => [
					'slovak' => __( 'PAY by square (Slovak)', 'wc-bacs-paybysquare' ),
					'czech'  => __( 'QR platba (Czech)', 'wc-bacs-paybysquare' ),
					'auto'   => __( 'Automatic (based on currency)', 'wc-bacs-paybysquare' ),
				],
				'default'     => 'auto',
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * Customized admin options rendering.
	 *
	 * @return void
	 */
	public function admin_options() {
		parent::admin_options();
		// Display the note on new settings page.
		$pbsq_link    = '<a href="https://app.bysquare.com" target="_blank">app.bysquare.com</a>';
		$allowed_html = [
			'a' => [
				'href'   => true,
				'target' => true,
			],
		];
		echo '<p id="woocommerce_bacs_paybysquare_note woocommerce_integration_paybysquare_note">';
		$limit_exceeded = get_option( 'woocommerce_bacs_paybysquare_limit_exceeded' );
		if ( $limit_exceeded && gmdate( 'Ym' ) === $limit_exceeded ) {
			echo '<span style="font-weight: bold; color: #c00">' . esc_html__( 'Your limit of generated QR codes was depleted', 'wc-bacs-paybysquare' ) . '</span><br>';
			/* translators: %s: service link */
			printf( esc_html__( 'To generate more this month, you need to upgrade your program at %s', 'wc-bacs-paybysquare' ), wp_kses( $pbsq_link, $allowed_html ) );
		} else {
			/* translators: %s: service link */
			printf( esc_html__( 'To learn more about the service, please visit %s', 'wc-bacs-paybysquare' ), wp_kses( $pbsq_link, $allowed_html ) );
		}
		echo '</p>';
	}

	/**
	 * Customized setting initialization.
	 *
	 * @return void
	 */
	public function init_settings() {
		parent::init_settings();

		// Attempt to load settings from old place (bacs gateway settings).
		$saved = get_option( $this->get_option_key(), null );
		if ( ! is_array( $saved ) ) {
			$bacs = get_option( 'woocommerce_bacs_settings' );
			if ( is_array( $bacs ) ) {
				$migrated = [];
				foreach ( $this->form_fields as $key => $data ) {
					/**
					 * The settings field definition.
					 *
					 * @var array<string, mixed>
					 */
					$field            = $data;
					$migrated[ $key ] = $bacs[ 'paybysquare_' . $key ] ?? $field['default'];
				}
				update_option( $this->get_option_key(), $migrated );
				$this->settings = $migrated;
			}
		}
	}
}
