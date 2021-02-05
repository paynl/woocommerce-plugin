<?php

/**
 * Plugin Name: PAY. Payment Methods for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-paynl-payment-methods/
 * Description: PAY. Payment Methods for WooCommerce
 * Version: 3.5.5
 * Author: PAY.
 * Author URI: https://www.pay.nl
 * Requires at least: 3.5.1
 * Tested up to: 5.6
 * WC tested up to: 4.8.0
 * WC requires at least: 3.0
 *
 * Text Domain: woocommerce-paynl-payment-methods
 * Domain Path: /i18n/languages
 */
require_once dirname( __FILE__ ) . '/includes/classes/Autoload.php';
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

# Load plugin functionality
require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

define('PPMFWC_WOOCOMMERCE_TEXTDOMAIN', 'woocommerce-paynl-payment-methods');
define('PPMFWC_PLUGIN_URL', plugins_url('/', __FILE__));
define('PPMFWC_PLUGIN_PATH', plugin_dir_path(__FILE__));

# Load textdomain
load_plugin_textdomain(PPMFWC_WOOCOMMERCE_TEXTDOMAIN, false, 'woocommerce-paynl-payment-methods/i18n/languages');

# Check if Curl is available
if (!in_array('curl', get_loaded_extensions())) {
    add_action( 'admin_notices', 'ppmfwc_error_curl_not_installed' );
}

# Register autoloader
PPMFWC_Autoload::ppmfwc_register();

# Register installer
register_activation_hook(__FILE__, array('PPMFWC_Setup', 'ppmfwc_install'));

if (is_plugin_active_for_network('woocommerce-paynl-payment-methods/woocommerce-payment-paynl.php')) {
  add_action('wp_initialize_site', array('PPMFWC_Setup', 'ppmfwc_newBlog'), 11);
  add_filter('wpmu_drop_tables', array('PPMFWC_Setup', 'ppmfwc_delBlog'));
}

if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php')) {

	# Register PAY gateway in WooCcommerce
	PPMFWC_Gateways::ppmfwc_register();

	# Test if PAY. can be reached
  PPMFWC_Setup::ppmfwc_testConnection();

  # Register checkoutFlash
	PPMFWC_Gateways::ppmfwc_registerCheckoutFlash();

	# Add global settings
	PPMFWC_Gateways::ppmfwc_addSettings();

	# Register function calls to WooCommerce API
	PPMFWC_Gateways::ppmfwc_registerApi();

	# Add settings link on the plugin-page
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ppmfwc_plugin_add_settings_link');

  if(get_option('paynl_show_vat_number') == "yes") {
    add_action('woocommerce_before_order_notes', 'ppmfwc_vatField');
    add_action('woocommerce_checkout_update_order_meta', 'ppmfwc_checkout_vat_number_update_order_meta');
    add_action('woocommerce_admin_order_data_after_billing_address', 'ppmfwc_vat_number_display_admin_order_meta', 10, 1);
  }

  if(get_option('paynl_show_coc_number') == "yes") {
    add_action('woocommerce_before_order_notes', 'ppmfwc_cocField');
    add_action('woocommerce_checkout_update_order_meta', 'ppmfwc_checkout_coc_number_update_order_meta');
    add_action('woocommerce_admin_order_data_after_billing_address', 'ppmfwc_coc_number_display_admin_order_meta', 10, 1);
  }

  if(get_option('paynl_standard_style') == "yes"){
      add_action('wp_enqueue_scripts', 'ppmfwc_payStyle');
  }

} else {
	# WooCommerce seems to be inactive, show eror message
	add_action( 'admin_notices', 'ppmfwc_error_woocommerce_not_active' );
}

/**
 * Show WooCommerce error message
 */
function ppmfwc_error_woocommerce_not_active()
{
    echo '<div class="error"><p>' . esc_html(__('The PAY. Payment Methods for WooCommerce plugin requires WooCommerce to be active', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '</p></div>';
}

/**
 * Show curl error message
 */
function ppmfwc_error_curl_not_installed()
{
    echo '<div class="error"><p>' . esc_html(__('Curl is not installed. In order to use the PAY. payment methods, you must install install CURL. Ask your system administrator to install php_curl.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '</p></div>';
}

/**
 * @param $links
 * @return mixed
 */
function ppmfwc_plugin_add_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('/admin.php?page=wc-settings&tab=checkout#paynl_apitoken') . '">' . esc_html(__('Settings')) . '</a>';
    array_push($links, $settings_link);
    return $links;
}

/**
 * @param $checkout
 */
function ppmfwc_vatField($checkout)
{
  woocommerce_form_field('vat_number', array(
    'type' => 'text',
    'class' => array('vat-number-field form-row-wide'),
    'label' => esc_html(__('VAT Number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
    'placeholder' => esc_html(__('Enter your VAT number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
  ), $checkout->get_value('vat_number'));
}

function ppmfwc_payStyle(){
    if(is_checkout() == true){
        wp_enqueue_style('ppmfwc_checkout_style', PPMFWC_PLUGIN_URL . 'assets/css/pay.css');
        wp_enqueue_style('ppmfwc_checkout_style');
    }
}

/**
 * @param $checkout
 */
function ppmfwc_cocField($checkout)
{
  woocommerce_form_field('coc_number', array(
    'type' => 'text',
    'class' => array('coc-number-field form-row-wide'),
    'label' => esc_html(__('COC Number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
    'placeholder' => esc_html(__('Enter your COC number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
  ), $checkout->get_value('coc_number'));
}

/**
 * Save VAT-field in the order
 * @param $order_id
 */
function ppmfwc_checkout_vat_number_update_order_meta($order_id)
{
    if (!empty($_POST['vat_number'])) {
        update_post_meta($order_id, '_vat_number', sanitize_text_field($_POST['vat_number']));
    }
}

/**
 * Show VAT in the WooCommerce admin
 * @param $order
 */
function ppmfwc_vat_number_display_admin_order_meta($order)
{
    echo '<p><strong>' . esc_html(__('VAT Number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ':</strong> ' . esc_html(get_post_meta($order->get_id(), '_vat_number', true)) . '</p>';
}

/**
 * Save COC-field in the order
 * @param $order_id
 */
function ppmfwc_checkout_coc_number_update_order_meta($order_id)
{
    if (!empty($_POST['coc_number'])) {
        update_post_meta($order_id, '_coc_number', sanitize_text_field($_POST['coc_number']));
    }
}

/**
 * Show COC in the WooCommerce admin
 * @param $order
 */
function ppmfwc_coc_number_display_admin_order_meta($order)
{
    echo '<p><strong>' . esc_html(__('COC Number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ':</strong> ' . esc_html(get_post_meta($order->get_id(), '_coc_number', true)) . '</p>';
}