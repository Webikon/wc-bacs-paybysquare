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
 * Main plugin class that contains all logic.
 */
class Plugin {
	const QRPLATBA_INVALID = ';[^0-9A-Za-z $%+./:-];';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * Remember order for mail hooks.
	 *
	 * @var \WC_Order|null
	 */
	protected $order;

	/**
	 * Direct bank transfer data.
	 *
	 * @var \WC_Gateway_BACS|false|null
	 */
	protected $bacs;

	/**
	 * Plugin logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Protected constructor.
	 */
	protected function __construct() {
		$this->logger = new Logger();
	}

	/**
	 * Maybe create and get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new self();
		}
		return static::$instance;
	}

	/**
	 * Get direct bank transfer data.
	 *
	 * @return \WC_Gateway_BACS|false
	 */
	public function get_bacs() {
		if ( null === $this->bacs ) {
			$available = \WC()->payment_gateways()->payment_gateways();
			if ( empty( $available['bacs'] ) ) {
				$this->logger->error( 'BACS payment gateway is not available' );
				$this->bacs = false;
			} else {
				$this->bacs = $available['bacs'];
			}
		}
		return $this->bacs;
	}

	/**
	 * Plugin setup.
	 *
	 * @param string $file main plugin file.
	 * @return void
	 */
	public static function run( $file ) {
		$plugin = static::get_instance();
		add_action( 'init', [ $plugin, 'initialize' ] );
		$plugin_basename = plugin_basename( $file );
		add_filter( "plugin_action_links_{$plugin_basename}", [ $plugin, 'add_settings_link' ] );
		add_filter( "network_admin_plugin_action_links_{$plugin_basename}", [ $plugin, 'add_settings_link' ] );
		add_filter( 'woocommerce_settings_api_form_fields_bacs', [ $plugin, 'filter_form_fields' ], 1000 );
		add_action( 'woocommerce_settings_checkout', [ $plugin, 'add_settings_note' ], 1000, 0 );
		add_action( 'woocommerce_thankyou_bacs', [ $plugin, 'thankyou_page_qrcode' ] );
		add_action( 'woocommerce_email_order_meta', [ $plugin, 'onhold_email_qrcode_info' ], -1000, 3 );
		add_filter( 'woocommerce_gateway_title', [ $plugin, 'filter_gateway_title' ], 1000, 2 );
	}

	/**
	 * Plugin initialization.
	 *
	 * @return void
	 */
	public function initialize() {
		load_plugin_textdomain( 'wc-bacs-paybysquare', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register settings link to WordPress plugin list.
	 *
	 * @param array<string, string> $links existing plugin links.
	 * @return array<string, string>
	 */
	public function add_settings_link( $links ) {
		$admin_url = admin_url(
			add_query_arg(
				[
					'page'    => 'wc-settings',
					'tab'     => 'checkout',
					'section' => 'bacs',
				],
				'admin.php'
			)
		) . '#woocommerce_bacs_paybysquare';
		return array_merge(
			[ 'settings' => '<a href="' . esc_attr( $admin_url ) . '">' . esc_html__( 'Settings', 'wc-bacs-paybysquare' ) . '</a>' ],
			$links
		);
	}

	/**
	 * Register PAY by square settings on direct bank transfer.
	 *
	 * @param array<string, array<string, mixed>> $fields existing direct bank transfer fields.
	 * @return array<string, array<string, mixed>>
	 */
	public function filter_form_fields( $fields ) {
		$pbsq_text = 'app.bysquare.com';
		return $fields + [
			'paybysquare'             => [
				'title'   => __( 'PAY by square Settings', 'wc-bacs-paybysquare' ),
				'type'    => 'title',
				'default' => '',
			],
			'paybysquare_beneficiary' => [
				'title'             => __( 'Beneficiary name', 'wc-bacs-paybysquare' ),
				'type'              => 'text',
				'description'       => __( 'Name of person or organization receiving money', 'wc-bacs-paybysquare' ),
				'default'           => '',
				'desc_tip'          => true,
				'sanitize_callback' => function ( $value ) {
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
			'paybysquare_username'    => [
				'title'       => __( 'Username', 'wc-bacs-paybysquare' ),
				'type'        => 'text',
				/* translators: %s: service name */
				'description' => sprintf( __( 'Your Username for %s service', 'wc-bacs-paybysquare' ), $pbsq_text ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'paybysquare_password'    => [
				'title'       => __( 'Password', 'wc-bacs-paybysquare' ),
				'type'        => 'password',
				/* translators: %s: service name */
				'description' => sprintf( __( 'Your Password for %s service', 'wc-bacs-paybysquare' ), $pbsq_text ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'paybysquare_information' => [
				'title'       => __( 'Checkout information', 'wc-bacs-paybysquare' ),
				'type'        => 'text',
				'description' => __( 'Text appended to your BACS title, advertising QR code availability', 'wc-bacs-paybysquare' ),
				'default'     => __( '(payment QR code)', 'wc-bacs-paybysquare' ),
				'desc_tip'    => true,
			],
			'paybysquare_display'     => [
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
	 * Inform user that monthly limit has been reached.
	 *
	 * @return void
	 */
	public function add_settings_note() {
		global $current_section;

		if ( 'bacs' === $current_section ) {
			$pbsq_link    = '<a href="https://app.bysquare.com" target="_blank">app.bysquare.com</a>';
			$allowed_html = [
				'a' => [
					'href'   => true,
					'target' => true,
				],
			];
			echo '<p id="woocommerce_bacs_paybysquare_note">';
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
	}

	/**
	 * Render QR code on thank you page.
	 *
	 * @param int $order_id the order ID to render QR code for.
	 * @return void
	 */
	public function thankyou_page_qrcode( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof \WC_Order ) {
			$info = $this->fetch_qrcode_png_info( $order );
			if ( $info ) {
				$this->output_qr_code_image( $info[1] );
			}
		}
	}

	/**
	 * Render QR code image into email.
	 *
	 * @param \WC_Order $order the order to render QR code for.
	 * @param boolean   $sent_to_admin send email to admin as well.
	 * @param boolean   $plain_text send email in plaintext.
	 * @return void
	 */
	public function onhold_email_qrcode_info( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! $sent_to_admin && ! $plain_text ) {
			if ( 'bacs' === $order->get_payment_method() && 'on-hold' === $order->get_status() ) {
				$info = $this->fetch_qrcode_png_info( $order );
				if ( $info ) {
					$this->order = $order;
					add_action( 'phpmailer_init', [ $this, 'onhold_email_attachments' ] );
					$this->output_qr_code_image( 'cid:' . $info[2] );
				}
			}
		}
	}

	/**
	 * Embed QR code image data into email.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer the mailer instance.
	 * @return void
	 */
	public function onhold_email_attachments( $phpmailer ) {
		$order = $this->order;
		if ( $order instanceof \WC_Order && 'bacs' === $order->get_payment_method() && 'on-hold' === $order->get_status() ) {
			$info = $this->fetch_qrcode_png_info( $order );
			if ( $info ) {
				if ( ! $phpmailer->addEmbeddedImage( $info[0], $info[2] ) ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$this->logger->warning( 'Adding embedded image into sent email failed: ' . $phpmailer->ErrorInfo );
				}
			}
		}
	}

	/**
	 * Hook into direct bank transfer title.
	 *
	 * @param string $title existing gateway title.
	 * @param string $gateway_id the gateway ID.
	 * @return string
	 */
	public function filter_gateway_title( $title, $gateway_id ) {
		$bacs = $this->get_bacs();
		if ( 'bacs' === $gateway_id && $bacs && $bacs->get_option( 'paybysquare_information' ) ) {
			$title .= rtrim( ' ' . ltrim( $bacs->get_option( 'paybysquare_information' ) ) );
		}
		return $title;
	}

	/**
	 * The QR code HTML to render on thankyou page and email.
	 *
	 * @param string $src the QR code image source.
	 * @return void
	 */
	protected function output_qr_code_image( $src ) {
		if ( $src ) {
			echo '<div style="margin: 1em 0 1em">'
				. '<p>' . esc_html__( 'For convenient payment, scan this QR code with your banking app:', 'wc-bacs-paybysquare' ) . '</p>'
				. '<img src="' . esc_attr( $src ) . '" alt="[PAY by square]" style="width: 16em; height: auto" />'
				. '</div>';
		}
	}

	/**
	 * Handle request to PAY by square API.
	 *
	 * @param \WC_Order $order the order to generate QR code for.
	 * @return array{0: string, 1: string, 2: string}|array{}
	 */
	protected function fetch_qrcode_png_info( $order ) {
		$bacs = $this->get_bacs();
		if ( ! $bacs ) {
			return [];
		}
		$display = $bacs->get_option( 'paybysquare_display' );
		$slovak  = 'slovak' === $display || 'auto' === $display && 'EUR' === $order->get_currency();
		$czech   = 'czech' === $display || 'auto' === $display && 'CZK' === $order->get_currency();
		if ( ! $slovak && ! $czech ) {
			return [];
		}
		$bank_accounts = [];
		foreach ( $bacs->account_details as $bank_account ) {
			$iban = static::sanitize( $bank_account['iban'] );
			$bic  = static::sanitize( $bank_account['bic'] );
			if ( $iban && $bic ) {
				$bank_accounts[] = [
					'iban' => $iban,
					'bic'  => $bic,
				];
			}
		}
		if ( ! $bank_accounts ) {
			$this->logger->warning( 'BACS payment gateway has no IBAN+BIC specified in account details.' );
			return [];
		}
		// in auto mode, prefer bank accounts based on currency (SK* for EUR, CZ* for CZK).
		if ( 'auto' === $display && count( $bank_accounts ) > 1 ) {
			if ( 'EUR' === $order->get_currency() ) {
				$iban_prefix = 'SK';
			} elseif ( 'CZK' === $order->get_currency() ) {
				$iban_prefix = 'CZ';
			}
			if ( ! empty( $iban_prefix ) ) {
				$bank_accounts = array_merge(
					array_filter(
						$bank_accounts,
						function ( $account ) use( $iban_prefix ) {
							return 0 === strncmp( $iban_prefix, $account['iban'], 2 );
						}
					),
					array_filter(
						$bank_accounts,
						function ( $account ) use( $iban_prefix ) {
							return 0 !== strncmp( $iban_prefix, $account['iban'], 2 );
						}
					)
				);
			}
		}

		$wp_upload = wp_upload_dir();
		if ( ! empty( $wp_upload['error'] ) ) {
			$this->logger->error( 'Searching for WordPress upload directory failed: ' . $wp_upload['error'] );
			return [];
		}
		$beneficiary_name = strtoupper( $bacs->get_option( 'paybysquare_beneficiary' ) );
		if ( $czech && preg_match( static::QRPLATBA_INVALID, $beneficiary_name ) ) {
			$this->logger->error( 'Invalid character detected in beneficiary name, cannot generage Czech QR code' );
			return [];
		}

		$qrdata = [
			'total'            => $order->get_total(),
			'currency'         => $order->get_currency(),
			'variable_symbol'  => substr( preg_replace( '/[^0-9]+/', '', $order->get_order_number() ) ?? '', 0, 10 ),
			'payment_note'     => 'PAY by square ' . $order->get_order_number(),
			'beneficiary_name' => $beneficiary_name,
			'bank_accounts'    => $bank_accounts,
		];
		$json   = wp_json_encode( $qrdata + [ 'display' => $display ] );
		if ( false === $json ) {
			$this->logger->error( 'Encoding of QR code properties into JSON has failed' );
			return [];
		}
		$hash = sha1( $json );
		$file = 'paybysquare/' . $hash . '.png';
		$path = $wp_upload['basedir'] . '/' . $file;
		$url  = $wp_upload['baseurl'] . '/' . $file;

		if ( file_exists( $path ) ) {
			return [ $path, $url, $hash ];
		}

		if ( ! wp_mkdir_p( dirname( $path ) ) ) {
			$this->logger->error( 'Unable to initialize directory storage for images: ' . dirname( $path ) );
			return [];
		}

		$xml = '<BySquareXmlDocuments xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
			. '<Username>' . esc_html( $bacs->get_option( 'paybysquare_username' ) ) . '</Username>'
			. '<Password>' . esc_html( $bacs->get_option( 'paybysquare_password' ) ) . '</Password>'
			. '<CountryOptions>'
			. '<Slovak>' . esc_html( $slovak ? 'true' : 'false' ) . '</Slovak>'
			. '<Czech>' . esc_html( $czech ? 'true' : 'false' ) . '</Czech>'
			. '</CountryOptions>'
			. '<Documents>'
			. '<Pay xsi:type="Pay" xmlns="http://www.bysquare.com/bysquare">'
			. '<Payments>'
			. '<Payment>'
			. '<PaymentOptions>paymentorder</PaymentOptions>'
			. '<Amount>' . esc_html( strval( $qrdata['total'] ) ) . '</Amount>'
			. '<CurrencyCode>' . esc_html( $qrdata['currency'] ) . '</CurrencyCode>'
			. '<VariableSymbol>' . esc_html( $qrdata['variable_symbol'] ) . '</VariableSymbol>'
			. '<PaymentNote>' . esc_html( $qrdata['payment_note'] ) . '</PaymentNote>'
			. '<BeneficiaryName>' . esc_html( $qrdata['beneficiary_name'] ) . '</BeneficiaryName>'
			. '<BankAccounts>';

		foreach ( $qrdata['bank_accounts'] as $bank_account ) {
			$xml .= '<BankAccount>'
				. '<IBAN>' . esc_html( $bank_account['iban'] ) . '</IBAN>'
				. '<BIC>' . esc_html( $bank_account['bic'] ) . '</BIC>'
				. '</BankAccount>';
		}

		$xml .= '</BankAccounts>'
			. '</Payment>'
			. '</Payments>'
			. '</Pay>'
			. '</Documents>'
			. '</BySquareXmlDocuments>';

		$result = wp_remote_post(
			'https://app.bysquare.com/api/generateQR',
			[
				'headers' => [
					'content-type' => 'text/xml',
				],
				'body'    => $xml,
			]
		);

		if ( is_wp_error( $result ) ) {
			$this->logger->error( 'Request failed with message "' . $result->get_error_message() . '".' );
			return [];
		}

		if ( empty( $result['response']['code'] ) ) {
			$this->logger->error( 'Request failed without a code.' );
			return [];
		}

		$code   = $result['response']['code'];
		$parsed = simplexml_load_string( $result['body'] );
		if ( false === $parsed ) {
			$this->logger->error( 'Response is not valid XML (code = ' . $code . ').' );
			return [];
		}

		switch ( $code ) {
			case 200:
				if ( $slovak && ! isset( $parsed->PayBySquare ) ) {
					$this->logger->error( 'Response is missing paybysquare code.' );
					return [];
				}
				if ( $czech && ! isset( $parsed->QrPlatbaCz ) ) {
					$this->logger->error( 'Paybysquare: Response is missing qrplatbacz code.' );
					return [];
				}

				$imageData = strval( $slovak ? $parsed->PayBySquare : $parsed->QrPlatbaCz );

				if ( false === file_put_contents( $path, base64_decode( $imageData ), LOCK_EX ) ) {
					$this->logger->error( 'Unable to write QR code into file: ' . $path );
					return [];
				}
				break;
			case 400:
				if ( ! isset( $parsed->ErrorCode ) ) {
					$this->logger->error( 'Request failed with code 400 without details.' );
					return [];
				}
				if ( 'E601' !== "$parsed->ErrorCode" ) {
					$message = empty( $parsed->Message ) ? '' : ' "' . $parsed->Message . '"';
					$detail  = empty( $parsed->Detail ) ? '' : ' (' . $parsed->Detail . ')';
					$this->logger->error( 'Request failed with code 400 with error ' . $parsed->ErrorCode . $message . $detail . '.' );
					return [];
				}
				update_option( 'woocommerce_bacs_paybysquare_limit_exceeded', gmdate( 'Ym' ) );
				$this->logger->error( 'Montly limit was reached (HTTP=400 ErrorCode=E601).' );
				return [];
			case 401:
				$this->logger->error( 'Username and Password pair does not exists or is disabled.' );
				return [];
			default:
				$this->logger->error( 'Request failed with code "' . $code . '".' );
				return [];
		}

		delete_option( 'woocommerce_bacs_paybysquare_limit_exceeded' );

		return [ $path, $url, $hash ];
	}

	/**
	 * Sanitize function for IBAN and BIC.
	 *
	 * @internal
	 * @param string $value unsanitized value.
	 * @return string
	 */
	protected static function sanitize( $value ) {
		// allow only alphanumeric characters (and uppercase lowercased ones).
		return preg_replace( '/[^0-9A-Z]+/', '', strtoupper( $value ) ) ?? '';
	}
}
