<?php

namespace Webikon\Woocommerce_Plugin\WC_BACS_Paybysquare;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	protected static $instance;
	protected $order;
	protected $bacs;

	protected function __construct() {
	}

	public static function get_instance() {
		if ( ! static::$instance ) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	public function get_bacs() {
		if ( null === $this->bacs ) {
			$available = \WC()->payment_gateways->payment_gateways();
			if ( empty( $available['bacs'] ) ) {
				trigger_error( 'Paybysquare: BACS payment gateway not available.', E_USER_NOTICE );
				$this->bacs = false;
			}
			else {
				$this->bacs = $available['bacs'];
			}
		}
		return $this->bacs;
	}

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

	public function initialize() {
		load_plugin_textdomain( 'wc-bacs-paybysquare', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function add_settings_link( $links ) {
		$admin_url = admin_url( add_query_arg( [
			'page' => 'wc-settings',
			'tab' => 'checkout',
			'section' => 'bacs',
		], 'admin.php' ) ) . '#woocommerce_bacs_paybysquare';
		return array_merge(
			[ 'settings' => '<a href="'. esc_attr( $admin_url ) . '">' . esc_html__( 'Settings', 'wc-bacs-paybysquare' ) . '</a>', ],
			$links
		);
	}

	public function filter_form_fields( $fields ) {
		$pbsq_text = 'app.bysquare.com';
		return $fields + [
			'paybysquare' => [
				'title' => __( 'PAY by square Settings', 'wc-bacs-paybysquare' ),
				'type' => 'title',
				'default' => '',
			],
			'paybysquare_beneficiary' => [
				'title' => __( 'Beneficiary name', 'wc-bacs-paybysquare' ),
				'type' => 'text',
				'description' => __( 'Name of person or organization receiving money', 'wc-bacs-paybysquare' ),
				'default' => '',
				'desc_tip' => true,
			],
			'paybysquare_username' => [
				'title' => __( 'Username', 'wc-bacs-paybysquare' ),
				'type' => 'text',
				/* translators: %s: service name */
				'description' => sprintf( __( 'Your Username for %s service', 'wc-bacs-paybysquare' ), $pbsq_text ),
				'default' => '',
				'desc_tip' => true,
			],
			'paybysquare_password' => [
				'title' => __( 'Password', 'wc-bacs-paybysquare' ),
				'type' => 'password',
				/* translators: %s: service name */
				'description' => sprintf( __( 'Your Password for %s service', 'wc-bacs-paybysquare' ), $pbsq_text ),
				'default' => '',
				'desc_tip' => true,
			],
			'paybysquare_information' => [
				'title' => __( 'Checkout information', 'wc-bacs-paybysquare' ),
				'type' => 'text',
				'description' => __( 'Text appended to your BACS title, advertising QR code availability', 'wc-bacs-paybysquare' ),
				'default' => __( '(payment QR code)', 'wc-bacs-paybysquare' ),
				'desc_tip' => true,
			],
			'paybysquare_display' => [
				'title' => __( 'Display QR code', 'wc-bacs-paybysquare' ),
				'description' => __( 'Setting controlling which type of QR should be displayed', 'wc-bacs-paybysquare' ),
				'type' => 'select',
				'options' => [
					'slovak' => __( 'PAY by square (Slovak)', 'wc-bacs-paybysquare' ),
					'czech' => __( 'QR platba (Czech)', 'wc-bacs-paybysquare' ),
					'auto' => __( 'Automatic (based on currency)', 'wc-bacs-paybysquare' ),
				],
				'default' => 'auto',
				'desc_tip' => true,
			],
		];
	}

	public function add_settings_note() {
		global $current_section;

		if ( 'bacs' === $current_section ) {
			$pbsq_link = '<a href="https://app.bysquare.com" target="_blank">app.bysquare.com</a>';
			echo '<p id="woocommerce_bacs_paybysquare_note">';
			$limit_exceeded = get_option( 'woocommerce_bacs_paybysquare_limit_exceeded' );
			if ( $limit_exceeded && gmdate( 'Ym' ) === $limit_exceeded ) {
				echo '<span style="font-weight: bold; color: #c00">' . __( 'Your limit of generated QR codes was depleted', 'wc-bacs-paybysquare' ) . '</span><br>';
				/* translators: %s: service link */
				printf( __( 'To generate more this month, you need to upgrade your program at %s', 'wc-bacs-paybysquare' ), $pbsq_link );
			}
			else {
				/* translators: %s: service link */
				printf( __( 'To learn more about the service, please visit %s', 'wc-bacs-paybysquare' ), $pbsq_link );
			}
			echo '</p>';
		}
	}

	public function thankyou_page_qrcode( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$info = $this->fetch_qrcode_png_info( $order );
			if ( $info ) {
				$this->output_qr_code_image( $info[1] );
			}
		}
	}

	public function onhold_email_qrcode_info( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( $order && ! $sent_to_admin && ! $plain_text ) {
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

	public function onhold_email_attachments( $phpmailer ) {
		$order = $this->order;
		if ( $order instanceof \WC_Order && 'bacs' === $order->get_payment_method() && 'on-hold' === $order->get_status() ) {
			$info = $this->fetch_qrcode_png_info( $order );
			if ( $info ) {
				$phpmailer->addEmbeddedImage( $info[0], $info[2] );
			}
		}
	}

	public function filter_gateway_title( $title, $gateway_id ) {
		$bacs = $this->get_bacs();
		if ( 'bacs' === $gateway_id && $bacs && $bacs->get_option( 'paybysquare_information' ) ) {
			$title .= rtrim( ' ' . ltrim( $bacs->get_option( 'paybysquare_information' ) ) );
		}
		return $title;
	}

	protected function output_qr_code_image( $src ) {
		if ( $src ) {
			echo '<div style="margin: 1em 0 1em">'
				. '<p>' . __( 'For convenient payment, scan this QR code with your banking app:', 'wc-bacs-paybysquare' ) . '</p>'
				. '<img src="' . esc_attr( $src ) . '" alt="[PAY by square]" style="width: 16em; height: auto" />'
				. '</div>';
		}
	}

	protected function fetch_qrcode_png_info( \WC_Order $order ) {
		$bacs = $this->get_bacs();
		if ( ! $bacs ) {
			return [];
		}
		$display = $bacs->get_option( 'paybysquare_display' );
		$slovak = 'slovak' === $display || 'auto' === $display && 'EUR' === $order->get_currency();
		$czech = 'czech' === $display || 'auto' === $display && 'CZK' === $order->get_currency();
		if ( ! $slovak && ! $czech ) {
			return [];
		}
		$bank_accounts = [];
		foreach ( $bacs->account_details as $bank_account ) {
			$iban = static::sanitize( $bank_account['iban'] );
			$bic = static::sanitize( $bank_account['bic'] );
			if ( $iban && $bic ) {
				$bank_accounts[] = [ 'iban' => $iban, 'bic' => $bic ];
			}
		}
		if ( ! $bank_accounts ) {
			trigger_error( 'Paybysquare: BACS payment gateway has no IBAN+BIC specified in account details.', E_USER_NOTICE );
			return [];
		}
		$wp_upload = wp_upload_dir();
		if ( ! empty( $wp_upload['error'] ) ) {
			trigger_error( 'Paybysquare: Searching for WordPress upload directory failed: ' . $wp_upload['error'], E_USER_NOTICE );
			return [];
		}
		
		$qrdata = [
			'total' => $order->get_total(),
			'currency' => $order->get_currency(),
			'variable_symbol' => substr( preg_replace( '/[^0-9]+/', '', $order->get_order_number() ), 0, 10 ),
			'payment_note' => 'PAY by square ' . $order->get_order_number(),
			'beneficiary_name' => $bacs->get_option( 'paybysquare_beneficiary' ),
			'bank_accounts' => $bank_accounts,
		];
		$hash = sha1( json_encode( $qrdata + [ 'display' => $display ] ) );
		$file = 'paybysquare/' . $hash . '.png';
		$path = $wp_upload['basedir'] . '/' . $file;
		$url = $wp_upload['baseurl'] . '/' . $file;

		if ( file_exists( $path ) ) {
			return [ $path, $url, $hash ];
		}

		if ( ! wp_mkdir_p( dirname( $path ) ) ) {
			trigger_error( 'Paybysquare: Unable to initialize directory storage for images: ' . dirname( $path ) , E_USER_NOTICE );
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
			. '<Amount>' . esc_html( $qrdata['total'] ) . '</Amount>'
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

		$result = wp_remote_post( 'https://app.bysquare.com/api/generateQR', [
			'headers' => [
				'content-type' => 'text/xml',
			],
			'body' => $xml,
		] );

		if ( is_wp_error( $result ) ) {
			trigger_error( 'Paybysquare: Request failed with message "' . $result->get_error_message() . '".' , E_USER_NOTICE );
			return [];
		}

		if ( empty( $result['response']['code'] ) ) {
			trigger_error( 'Paybysquare: Request failed without a code.' , E_USER_NOTICE );
			return [];
		}

		$code = $result['response']['code'];
		$parsed = simplexml_load_string( $result['body'] );
		if ( false === $parsed ) {
			trigger_error( 'Paybysquare: Response is not valid XML (code = ' . $code . ').' , E_USER_NOTICE );
			return [];
		}

		switch ( $code ) {
		case 200:
			if ( $slovak && ! isset( $parsed->PayBySquare ) ) {
				trigger_error( 'Paybysquare: Response is missing paybysquare code.' , E_USER_NOTICE );
				return [];
			}
			if ( $czech && ! isset( $parsed->QrPlatbaCz ) ) {
				trigger_error( 'Paybysquare: Response is missing qrplatbacz code.' , E_USER_NOTICE );
				return [];
			}

			$imageData = $slovak ? "$parsed->PayBySquare" : "$parsed->QrPlatbaCz";

			if ( false === file_put_contents( $path, base64_decode( $imageData ), LOCK_EX ) ) {
				trigger_error( 'Paybysquare: Unable to write QR code into file: ' . $path, E_USER_NOTICE );
				return [];
			}
			break;
		case 400:
			if ( ! isset( $parsed->ErrorCode ) ) {
				trigger_error( 'Paybysquare: Request failed with code 400 without details.' , E_USER_NOTICE );
				return [];
			}
			if ( 'E601' !== "$parsed->ErrorCode" ) {
				$message = empty( $parsed->Message ) ? '' :  ' "' . $parsed->Message . '"';
				$detail = empty( $parsed->Detail ) ? '' : ' (' . $parsed->Detail . ')';
				trigger_error( 'Paybysquare: Request failed with code 400 with error ' . $parsed->ErrorCode . $message . $detail . '.' , E_USER_NOTICE );
				return [];
			}
			update_option( 'woocommerce_bacs_paybysquare_limit_exceeded', gmdate( 'Ym' ) );
			trigger_error( 'Paybysquare: Montly limit was reached (HTTP=400 ErrorCode=E601).' , E_USER_NOTICE );
			return [];
		case 401:
			trigger_error( 'Paybysquare: Username and Password pair does not exists or is disabled.' , E_USER_NOTICE );
			return [];
		default:
			trigger_error( 'Paybysquare: Request failed with code "' . $code . '".' , E_USER_NOTICE );
			return [];
		}

		delete_option( 'woocommerce_bacs_paybysquare_limit_exceeded' );

		return [ $path, $url, $hash ];
	}

	protected static function sanitize( $value ) {
		// allow only alphanumeric characters (and uppercase lowercased ones)
		return preg_replace( '/[^0-9A-Z]+/', '', strtoupper( $value ) );
	}
}
