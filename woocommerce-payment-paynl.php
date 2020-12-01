<?php

/**
 * Plugin Name: PAY. Payment Methods for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-paynl-payment-methods/
 * Description: PAY. payment methods for WooCommerce
 * Version: 3.5.4
 * Author: PAY.
 * Author URI: https://www.pay.nl
 * Requires at least: 3.5.1
 * Tested up to: 5.6
 * WC tested up to: 4.7.1
 * WC requires at least: 2.5
 *
 * Text Domain: woocommerce-paynl-payment-methods
 * Domain Path: /i18n/languages
 */
require_once dirname( __FILE__ ) . '/includes/classes/Autoload.php';
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

# Load plugin functionality
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

define( 'PAYNL_WOOCOMMERCE_TEXTDOMAIN', 'woocommerce-paynl-payment-methods' );
define( 'PAYNL_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'PAYNL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

# Load textdomain
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

# Check if Curl is available
if (!in_array('curl', get_loaded_extensions())) {
    add_action( 'admin_notices', 'pay_error_curl_not_installed' );
}

# Register autoloader
Pay_Autoload::register();

# Register installer
register_activation_hook( __FILE__, array( 'PPMFWC_Pay_Setup', 'install' ) );

if (is_plugin_active_for_network('woocommerce-paynl-payment-methods/woocommerce-payment-paynl.php')) {
  add_action('wp_initialize_site', array('PPMFWC_Pay_Setup', 'newBlog'), 11);
  add_filter('wpmu_drop_tables', array('PPMFWC_Pay_Setup', 'delBlog'));
}

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {

	# Register PAY gateway in WooCcommerce
	PPMFWC_Gateways::register();

	# Test if PAY. can be reached
  PPMFWC_Pay_Setup::testConnection();

	PPMFWC_Gateways::paynl_registerCheckoutFlash();

	# Add global settings
	PPMFWC_Gateways::addSettings();

	# Register function calls to WooCommerce API
	PPMFWC_Gateways::registerApi();

	# Add settings link on the plugin-page
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'paynl_plugin_add_settings_link' );

  if(get_option('paynl_show_vat_number') == "yes") {
    add_action('woocommerce_before_order_notes', 'paynl_vatField');
    add_action('woocommerce_checkout_update_order_meta', 'paynl_checkout_vat_number_update_order_meta');
    add_action('woocommerce_admin_order_data_after_billing_address', 'paynl_vat_number_display_admin_order_meta', 10, 1);
  }

  if(get_option('paynl_show_coc_number') == "yes") {
    add_action('woocommerce_before_order_notes', 'paynl_cocField');
    add_action('woocommerce_checkout_update_order_meta', 'paynl_checkout_coc_number_update_order_meta');
    add_action('woocommerce_admin_order_data_after_billing_address', 'paynl_coc_number_display_admin_order_meta', 10, 1);
  }

} else {
	# WooCommerce seems to be inactive, show eror message
	add_action( 'admin_notices', 'pay_error_woocommerce_not_active' );
}


function paynl_vatField($checkout)
{
  woocommerce_form_field('vat_number', array(
    'type' => 'text',
    'class' => array('vat-number-field form-row-wide'),
    'label' => __('VAT Number'),
    'placeholder' => __('Enter your VAT number'),
  ), $checkout->get_value('vat_number'));
}

function paynl_cocField($checkout)
{
  woocommerce_form_field('coc_number', array(
    'type' => 'text',
    'class' => array('coc-number-field form-row-wide'),
    'label' => __('COC Number'),
    'placeholder' => __('Enter your COC number'),
  ), $checkout->get_value('coc_number'));
}


/**
 * Save VAT-field in the order
 * @param $order_id
 */
function paynl_checkout_vat_number_update_order_meta($order_id)
{
    if (!empty($_POST['vat_number'])) {
        update_post_meta($order_id, '_vat_number', sanitize_text_field($_POST['vat_number']));
    }
}


/**
 * Show content in the WooCommerce admin
 * @param $order
 */
function paynl_vat_number_display_admin_order_meta($order)
{
    echo '<p><strong>' . __('VAT Number', 'woocommerce') . ':</strong> ' . get_post_meta($order->id, '_vat_number', true) . '</p>';
}


/**
 * Save COC-field in the order
 * @param $order_id
 */
function paynl_checkout_coc_number_update_order_meta($order_id)
{
    if (!empty($_POST['coc_number'])) {
        update_post_meta($order_id, '_coc_number', sanitize_text_field($_POST['coc_number']));
    }
}

/**
 * Show content in the WooCommerce admin
 * @param $order
 */
function paynl_coc_number_display_admin_order_meta($order)
{
    echo '<p><strong>' . __('COC Number', 'woocommerce') . ':</strong> ' . get_post_meta($order->id, '_coc_number', true) . '</p>';
}