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
	/**
	 * Identifier for integration that stores settings
	 *
	 * @var string
	 */
	const INTEGRATION_ID = 'paybysquare';

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
	 * PAY by square integration data.
	 *
	 * @var Settings|false|null
	 */
	protected $pbsq;

	/**
	 * Plugin logger.
	 *
	 * @var Logger
	 */
	protected $logger;

	/**
	 * Link to plugin settings page.
	 *
	 * @var string
	 */
	protected $settings_link;

	/**
	 * Protected constructor.
	 */
	protected function __construct() {
		$this->logger        = new Logger();
		$this->settings_link = admin_url(
			add_query_arg(
				[
					'page'    => 'wc-settings',
					'tab'     => 'integration',
					'section' => static::INTEGRATION_ID,
				],
				'admin.php'
			)
		);
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
			if ( isset( $available['bacs'] ) && $available['bacs'] instanceof \WC_Gateway_BACS ) {
				$this->bacs = $available['bacs'];
			} else {
				$this->logger->error( 'BACS payment gateway is not available' );
				$this->bacs = false;
			}
		}
		return $this->bacs;
	}

	/**
	 * Get PAY by square integration data.
	 *
	 * @return Settings|false
	 */
	public function get_pbsq() {
		if ( null === $this->pbsq ) {
			$pbsq = WC()->integrations->get_integration( static::INTEGRATION_ID );
			if ( $pbsq instanceof Settings ) {
				$this->pbsq = $pbsq;
			} else {
				$this->logger->error( 'PAY by square integration is not available' );
				$this->pbsq = false;
			}
		}
		return $this->pbsq;
	}

	/**
	 * Plugin setup.
	 *
	 * @param string $file main plugin file.
	 * @return void
	 */
	public static function run( $file ) {
		$plugin = static::get_instance();
		add_action( 'plugins_loaded', [ $plugin, 'preinit' ] );
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
	 * Before plugin initialization.
	 *
	 * @return void
	 */
	public function preinit() {
		$plugin = static::get_instance();
		add_filter( 'woocommerce_integrations', [ $plugin, 'register_integration' ] );
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
	 * Register WooCommerce integration.
	 *
	 * @param string[] $integrations existing integrations.
	 *
	 * @return string[]
	 */
	public function register_integration( $integrations ) {
		require __DIR__ . '/class-settings.php';
		$integrations[] = Settings::class;
		return $integrations;
	}

	/**
	 * Get setting value.
	 *
	 * @param string $option_key the option key.
	 *
	 * @return string
	 */
	public function get_option( $option_key ) {
		$pbsq = $this->get_pbsq();
		return $pbsq ? $pbsq->get_option( $option_key ) : '';
	}

	/**
	 * Register settings link to WordPress plugin list.
	 *
	 * @param array<string, string> $links existing plugin links.
	 * @return array<string, string>
	 */
	public function add_settings_link( $links ) {
		return array_merge(
			[ 'settings' => '<a href="' . esc_attr( $this->settings_link ) . '">' . esc_html__( 'Settings', 'wc-bacs-paybysquare' ) . '</a>' ],
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
		$pbsq = $this->get_pbsq();
		if ( $pbsq && ! is_array( get_option( $pbsq->get_option_key() ) ) ) {
			$additional = [
				'paybysquare' => [
					'title' => __( 'PAY by square Settings', 'wc-bacs-paybysquare' ),
					'type'  => 'title',
				],
			];
			foreach ( $pbsq->form_fields as $field_id => $field_data ) {
				/**
				 * The settings field definition.
				 *
				 * @var array<string, mixed>
				 */
				$field                                    = $field_data;
				$additional[ 'paybysquare_' . $field_id ] = $field;
			}
			$fields += $additional;
		}
		return $fields;
	}

	/**
	 * Inform user that monthly limit has been reached.
	 *
	 * @return void
	 */
	public function add_settings_note() {
		/**
		 * Current screen.
		 *
		 * @var \WP_Screen|null
		 */
		global $current_screen;
		global $current_tab;
		global $current_section;

		// Provide the note + link to the new settings on pages were the settings were before.
		if ( $current_screen && 'woocommerce_page_wc-settings' === $current_screen->id && 'checkout' === $current_tab && ( 'bacs' === $current_section || 'offline' === $current_section ) ) {
			echo '<p>' . sprintf(
				/* translators: %s: link to new settings page */
				esc_html__( 'The PAY by square settings were moved to %s' ),
				'<a href="' . esc_attr( $this->settings_link ) . '">' . esc_html__( 'Integration', 'woocommerce' ) . ' &gt; ' . esc_html__( 'PAY by square', 'wc-bacs-paybysquare' ) . '</a>'
			) . '</p>';
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
		$bacs        = $this->get_bacs();
		$information = trim( $this->get_option( 'information' ) );
		if ( 'bacs' === $gateway_id && $bacs && $information ) {
			$title .= ' ' . $information;
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
		$display = $this->get_option( 'display' );
		$slovak  = 'slovak' === $display || ( 'auto' === $display && 'EUR' === $order->get_currency() );
		$czech   = 'czech' === $display || ( 'auto' === $display && 'CZK' === $order->get_currency() );
		if ( ! $slovak && ! $czech ) {
			return [];
		}
		$bank_accounts = [];
		foreach ( $bacs->account_details as $account ) {
			/**
			 * Bank account properties.
			 *
			 * @var array{iban: string, bic: string}
			 */
			$bank_account = $account;
			$iban         = static::sanitize( $bank_account['iban'] );
			$bic          = static::sanitize( $bank_account['bic'] );
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
						function ( $account ) use ( $iban_prefix ) {
							return 0 === strncmp( $iban_prefix, $account['iban'], 2 );
						}
					),
					array_filter(
						$bank_accounts,
						function ( $account ) use ( $iban_prefix ) {
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
		$beneficiary_name = strtoupper( $this->get_option( 'beneficiary' ) );
		if ( $czech && preg_match( Settings::QRPLATBA_INVALID, $beneficiary_name ) ) {
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
			. '<Username>' . esc_html( $this->get_option( 'username' ) ) . '</Username>'
			. '<Password>' . esc_html( $this->get_option( 'password' ) ) . '</Password>'
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
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $slovak && ! isset( $parsed->PayBySquare ) ) {
					$this->logger->error( 'Response is missing paybysquare code.' );
					return [];
				}
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $czech && ! isset( $parsed->QrPlatbaCz ) ) {
					$this->logger->error( 'Paybysquare: Response is missing qrplatbacz code.' );
					return [];
				}

				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$base64_image_data = strval( $slovak ? $parsed->PayBySquare : $parsed->QrPlatbaCz );

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$raw_image_data = base64_decode( $base64_image_data );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				if ( false === file_put_contents( $path, $raw_image_data, LOCK_EX ) ) {
					$this->logger->error( 'Unable to write QR code into file: ' . $path );
					return [];
				}
				break;
			case 400:
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( ! isset( $parsed->ErrorCode ) ) {
					$this->logger->error( 'Request failed with code 400 without details.' );
					return [];
				}
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( 'E601' !== strval( $parsed->ErrorCode ) ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$message = empty( $parsed->Message ) ? '' : ' "' . $parsed->Message . '"';
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$detail = empty( $parsed->Detail ) ? '' : ' (' . $parsed->Detail . ')';
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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
