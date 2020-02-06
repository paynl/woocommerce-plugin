<?php

/**
 * Plugin Name: WooCommerce PAY. Payment Methods
 * Plugin URI: https://wordpress.org/plugins/woocommerce-paynl-payment-methods/
 * Description: PAY. payment methods for WooCommerce
 * Version: 3.4.1
 * Author: PAY.
 * Author URI: https://www.pay.nl
 * Requires at least: 3.5.1
 * Tested up to: 5.0
 *
 * Text Domain: woocommerce-paynl-payment-methods
 * Domain Path: /i18n/languages
 */
//Autoloader laden en registreren
require_once dirname( __FILE__ ) . '/includes/classes/Autoload.php';
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

//plugin functies inladen
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

define( 'PAYNL_WOOCOMMERCE_TEXTDOMAIN', 'woocommerce-paynl-payment-methods' );

define( 'PAYNL_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'PAYNL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

//textdomain inladen
load_plugin_textdomain( PAYNL_WOOCOMMERCE_TEXTDOMAIN, false, 'woocommerce-paynl-payment-methods/i18n/languages' );

function pay_error_woocommerce_not_active() {
	echo '<div class="error"><p>' . __( 'The PAY. payment methods plugin requires Woocommerce to be active', PAYNL_WOOCOMMERCE_TEXTDOMAIN ) . '</p></div>';
}

function pay_error_curl_not_installed() {
	echo '<div class="error"><p>' . __( 'Curl is not installed.<br />In order to use the PAY. payment methods, you must install install CURL.<br />Ask your system administrator to install php_curl', PAYNL_WOOCOMMERCE_TEXTDOMAIN ) . '</p></div>';
}

function paynl_plugin_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( '/admin.php?page=wc-settings&tab=checkout#paynl_apitoken' ) . '">' . __( 'Settings') . '</a>';
	array_push( $links, $settings_link );
	return $links;
}

// Curl is niet geinstalleerd. foutmelding weergeven
if ( ! function_exists( 'curl_version' ) ) {
	add_action( 'admin_notices', 'pay_error_curl_not_installed' );
}

//Autoloader registreren
Pay_Autoload::register();

//Installer registreren
register_activation_hook( __FILE__, array( 'Pay_Setup', 'install' ) );

if ( is_plugin_active_for_network( 'woocommerce-paynl-payment-methods/woocommerce-payment-paynl.php' ) ) {
	add_action( 'wpmu_new_blog', array( 'Pay_Setup', 'newBlog' ), 10, 6 );
}

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {

	//Gateways van pay aan woocommerce koppelen
	Pay_Gateways::register();

	// test if pay can be reached
	Pay_Setup::testConnection();

	Pay_Gateways::registerCheckoutFlash();

	//Globale settings van pay aan woocommerce koppelen
	Pay_Gateways::addSettings();

	//Return en exchange functies koppelen aan de woocommerce API
	Pay_Gateways::registerApi();

	// Add settings link on the plugin-page
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'paynl_plugin_add_settings_link' );
} else {
	// Woocommerce is niet actief. foutmelding weergeven
	add_action( 'admin_notices', 'pay_error_woocommerce_not_active' );
}

