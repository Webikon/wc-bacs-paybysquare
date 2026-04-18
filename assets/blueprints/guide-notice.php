<?php
/**
 * Plugin Name: PAY by square — Live Preview Guide
 * Description: Shows a welcome guide on the PAY by square settings page in the WordPress Playground Live Preview.
 * Version: 1.0.0
 * Author: Webikon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_notices',
	function () {
		$page    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		if ( 'wc-settings' !== $page || 'integration' !== $tab || 'paybysquare' !== $section ) {
			return;
		}

		$register_url = 'https://app.bysquare.com/?utm_source=wordpress-playground&utm_medium=live-preview&utm_campaign=wc-bacs-paybysquare';
		$bacs_url     = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bacs' );
		?>
		<div class="notice notice-info" style="padding: 16px; border-left-color: #2271b1;">
			<h2 style="margin-top: 0;">👋 Welcome to the PAY by square Live Preview</h2>
			<p><strong>This plugin generates QR codes for bank transfer payments</strong> — customers scan the QR with their banking app to pre-fill all payment details (IBAN, amount, variable symbol, beneficiary).</p>

			<h3>How to set up (3 steps)</h3>
			<ol>
				<li><strong>Register at app.bysquare.com</strong> — <a href="<?php echo esc_url( $register_url ); ?>" target="_blank" rel="noopener">Create an account</a> to get API credentials (username + password).</li>
				<li><strong>Set up BACS bank account</strong> — Enter your IBAN and BIC in <a href="<?php echo esc_url( $bacs_url ); ?>">Direct bank transfer settings</a>. Both fields are required.</li>
				<li><strong>Configure this integration</strong> — On this page, fill in:
					<ul style="list-style: disc; margin-left: 24px;">
						<li><em>Beneficiary name</em> — your company / recipient name shown in the QR code</li>
						<li><em>Username</em> and <em>Password</em> — your app.bysquare.com credentials</li>
					</ul>
				</li>
			</ol>

			<h3>What the plugin does after setup</h3>
			<ul style="list-style: disc; margin-left: 24px;">
				<li>📄 Shows a QR code on the WooCommerce <strong>thank-you page</strong> after order placement</li>
				<li>📧 Embeds the QR code in the <strong>order email</strong> sent to the customer</li>
				<li>🇸🇰 Supports <strong>PAY by square</strong> (Slovak standard)</li>
				<li>🇨🇿 Supports <strong>QR platba</strong> (Czech standard)</li>
				<li>🌐 Automatic selection based on order currency (EUR → Slovak, CZK → Czech)</li>
			</ul>

			<p style="margin-bottom: 0;"><strong>⚠ Note about this preview:</strong> QR codes require real API credentials from app.bysquare.com and cannot be generated in this sandbox. To see working QR codes, install the plugin on your own WooCommerce site after registering.</p>

			<p><a href="<?php echo esc_url( $register_url ); ?>" target="_blank" rel="noopener" class="button button-primary">Register at app.bysquare.com →</a></p>
		</div>
		<?php
	}
);
