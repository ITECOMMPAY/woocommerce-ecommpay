<?php

defined( 'ABSPATH' ) || exit;


use common\EcpCore;
use common\exceptions\EcpGatewaySignatureException;
use common\gateways\EcpGateway;
use common\log\EcpGatewayLog;
use common\modules\EcpModulePaymentPage;
use common\modules\EcpSigner;
use common\settings\EcpSettings;
use common\settings\EcpSettingsGeneral;

/**
 * Make the object available for later use
 *
 * @return EcpCore
 */
function ecommpay(): EcpCore {
	return EcpCore::get_instance();
}

/**
 * Returns current version for frontend.
 *
 * @return string
 */
function ecp_version(): string {
	return 'wc_ecp-' . EcpCore::WC_ECP_VERSION;
}

if ( ! function_exists( 'wp_version' ) ) {
	function wp_version() {
		include( ABSPATH . WPINC . '/version.php' );

		/** @noinspection PhpUndefinedVariableInspection */
		return $wp_version;
	}
}

if ( ! function_exists( 'wc_version' ) ) {
	function wc_version(): string {
		return WC()->version;
	}
}

/**
 * Get the plugin url.
 * @return string
 */
function ecp_plugin_url(): string {
	return untrailingslashit( plugins_url( '/', ECP_PLUGIN_PATH ) );
}

/**
 * Get the plugin path.
 * @return string
 */
function ecp_plugin_path(): string {
	return untrailingslashit( plugin_dir_path( ECP_PLUGIN_PATH ) );
}

function ecp_assets_path( $file_name ): string {
	return ecp_plugin_path() . '/assets/' . trim( $file_name, '/' );
}

function ecp_assets_url( $file_name ): string {
	return ecp_plugin_url() . '/assets/' . trim( $file_name, '/' );
}

function ecp_js_path( $file_name ): string {
	return ecp_assets_path( 'js/' . trim( $file_name, '/' ) );
}

function ecp_css_path( $file_name ): string {
	return ecp_assets_path( 'css/' . trim( $file_name, '/' ) );
}

function ecp_css_url( $file_name ): string {
	return esc_url( ecp_assets_url( 'css/' . trim( $file_name, '/' ) ) );
}

function ecp_js_url( $file_name ): string {
	return esc_url( ecp_assets_url( 'js/' . trim( $file_name, '/' ) ) );
}

function ecp_img_url( $file_name ): string {
	return esc_url( ecp_assets_url( 'img/' . trim( $file_name, '/' ) ) );
}

/**
 * Returns the link to the gateway settings page.
 *
 * @param string $sub
 *
 * @return string
 */
function ecp_settings_page_url( string $sub = EcpSettingsGeneral::ID ): string {
	if ( ! in_array( $sub, EcpSettings::SETTINGS_TABS ) ) {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . esc_attr( $sub ) );
	}

	foreach ( ecp_payment_methods() as $id => $method ) {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id . '&sub=' . esc_attr( $sub ) );
	}

	return admin_url( 'admin.php?page=wc-settings&tab=checkout' );
}

/**
 * @return string
 */
function ecp_doc_link(): string {
	return 'https://developers.ecommpay.com/en/en_CMS__wordpress.html';
}

/**
 * Returns a link to the manual contains description by error code.
 *
 * @param string $code Error code
 *
 * @return string
 *
 */
function ecp_error_code_link( string $code ): string {
	return 'https://developers.ecommpay.com/en/en_Gate__Unified_Codes.html?hl= ' . $code;
}

/**
 * Returns a link to the log files in the WP backend.
 */
function ecp_admin_link(): string {
	wc_get_logger();
	$source = EcpGatewayLog::ECOMMPAY_DOMAIN;
	$handler   = new WC_Log_Handler_File();
	$log_files = $handler->get_log_files();
	$log_file  = '';
	foreach ( $log_files as $file_name => $file_path ) {
		if ( str_contains( $file_name, $source ) ) {
			$log_file = $file_name;
			break;
		}
	}

	if ( empty( $log_file ) ) {
		return '';
	}

	return add_query_arg( [
		'page'     => 'wc-status',
		'tab'      => 'logs',
		'log_file' => $log_file
	], admin_url( 'admin.php' ) );
}

/**
 * Fetches and shows a view
 *
 * @param string $path
 * @param array $args
 */
function ecp_get_view( string $path, array $args = [] ) {
	if ( is_array( $args ) && ! empty ( $args ) ) {
		extract( $args );
	}

	$file = __DIR__ . '/../views/' . trim( $path );

	if ( file_exists( $file ) ) {
		include $file;
	}
}

const ECOMMPAY_LOCALE_DOMAIN = 'woo-ecommpay';

/**
 * @return void
 */
function ecp_load_i18n(): void {
	load_plugin_textdomain(
		ECOMMPAY_LOCALE_DOMAIN,
		false,
		dirname( plugin_basename( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'languages'
	);
}

/**
 * Translates text.
 *
 * @param string $text Text to translate.
 * @param string $context Context information for the translators.
 *
 * @return string Translated text.
 */
function ecpL( string $text, string $context ): string {
	return _x( $text, $context, ECOMMPAY_LOCALE_DOMAIN );
}

function ecpTr( string $text ): string {
	return __( $text, ECOMMPAY_LOCALE_DOMAIN );
}


/**
 * Checks if a setting options is enabled by checking on yes/no data.
 *
 * @param string $key
 * @param string $payment_method
 *
 * @return bool
 */
function ecp_is_enabled( string $key, string $payment_method = EcpSettingsGeneral::ID ): bool {
	return ecommpay()
		       ->get_pm_option(
			       $payment_method,
			       $key,
			       EcpSettings::VALUE_DISABLED
		       ) === EcpSettings::VALUE_ENABLED;
}

/**
 * @return EcpGateway[]
 * @since 3.0.0
 */
function ecp_payment_methods(): array {
	return ecommpay()->get_payment_methods();
}

/**
 * @return string[]
 * @since 3.0.0
 */
function ecp_payment_classnames(): array {
	return ecommpay()->get_payment_classnames();
}

/**
 * @return bool
 * @since 3.0.0
 */
function ecp_has_available_methods(): bool {
	foreach ( ecp_payment_methods() as $id => $method ) {
		if ( $method->enabled ) {
			return true;
		}
	}

	return false;
}

/**
 * Inserts a new key/value after the key in the array.
 *
 * @param string $needle The array key to insert the element after
 * @param array $haystack An array to insert the element into
 * @param string $new_key The key to insert
 * @param mixed $new_value A value to insert
 *
 * @return array The new array if the $needle key exists, otherwise an unmodified $haystack
 */
function ecp_array_insert_after( string $needle, array $haystack, string $new_key, $new_value ): array {

	if ( array_key_exists( $needle, $haystack ) ) {

		$new_array = [];

		foreach ( $haystack as $key => $value ) {

			$new_array[ $key ] = $value;

			if ( $key === $needle ) {
				$new_array[ $new_key ] = $new_value;
			}
		}

		return $new_array;
	}

	return $haystack;
}

/**
 * @param string $payment_type
 *
 * @return string
 */
function get_ecp_payment_method_icon( string $payment_type ): string {
	$logos = [
		'card'            => 'card.svg',
		'alipay'          => 'alipay.svg',
		'apple_pay'       => 'apple_pay_core.svg',
		'apple_pay_core'  => 'apple_pay_core.svg',
		'bigcash'         => 'bigcash.svg',
		'crypto'          => 'crypto.svg',
		'google-pay'      => 'google_pay.png',
		'google-pay-host' => 'google_pay.svg',
		'humm'			  => 'humm.svg',
		'jeton-wallet'    => 'jetonWallet.svg',
		'mobile'          => 'mobile.svg',
		'monetix-wallet'  => 'monetix-wallet.svg',
		'neteller'        => 'neteller.svg',
		'paypal-wallet'   => 'paypal-wallet.svg',
		'profee'          => 'profee.svg',
		'rapid'           => 'rapid.svg',
		'skrill'          => 'skrill.svg',
		'unionpay'        => 'unionpay.svg',
		'webmoney'        => 'webmoney.svg',
	];

	if ( array_key_exists( trim( $payment_type ), $logos ) ) {
		return ecp_img_url( $logos[ $payment_type ] );
	}

	return ecp_img_url( 'ecommpay.svg' );
}

/**
 * Returns ECOMMPAY Logger
 * @return EcpGatewayLog
 */
function ecp_get_log(): EcpGatewayLog {
	return EcpGatewayLog::get_instance();
}

function ecp_debug( ...$args ) {
	ecp_get_log()->debug( ...func_get_args() );
}

function ecp_info( ...$args ) {
	ecp_get_log()->info( ...func_get_args() );
}

function ecp_warning() {
	ecp_get_log()->warning( ...func_get_args() );
}

function ecp_warn( ...$args ) {
	ecp_warning( ...$args );
}

function ecp_error( ...$args ) {
	ecp_get_log()->error( ...func_get_args() );
}

/**
 * Returns ECOMMPAY Signer.
 *
 * @return EcpSigner
 */
function ecp_get_signer(): EcpSigner {
	return EcpSigner::get_instance();
}

/**
 * <h2>Returns the result of data signature verification.</h2>
 *
 * @param array $data <p>Data to verify.</p>
 *
 * @return bool <p><b>TRUE</b> if the signature is valid or <b>FALSE</b> otherwise.</p>
 * @throws EcpGatewaySignatureException <p>
 * When the key or value of one of the parameters contains the character
 * {@see EcpSigner::VALUE_SEPARATOR} symbol.
 * </p>
 * @since 2.0.0
 */
function ecp_check_signature( array $data ): bool {
	return ecp_get_signer()->check( $data );
}

/**
 * @return EcpModulePaymentPage
 */
function ecp_payment_page(): EcpModulePaymentPage {
	return EcpModulePaymentPage::get_instance();
}

function ecp_region_code( $country, $region ) {
	$regions = WC()->countries->get_states( $country );

	return array_search( $region, $regions );
}

/**
 * Display a description.
 *
 * @param array $attributes custom HTML attributes as key => value pairs.
 *
 * @return string
 * @since  2.2.2
 *
 */
function ecp_custom_attributes( array $attributes ): string {
	$result = '';

	foreach ( $attributes as $attribute => $attribute_value ) {
		$result .= sprintf( ' %s="%s"', esc_attr( $attribute ), esc_attr( $attribute_value ) );
	}

	return $result;
}
