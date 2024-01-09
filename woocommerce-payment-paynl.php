<?php

/**
 * Plugin Name: Pay. Payment Methods for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/woocommerce-paynl-payment-methods/
 * Description: Pay. Payment Methods for WooCommerce
 * Version: 3.18.1
 * Author: Pay.
 * Author URI: https://www.pay.nl
 * Requires at least: 3.5.1
 * WC requires at least: 3.0
 * Requires PHP: 7.0
 * Text Domain: woocommerce-paynl-payment-methods
 * Domain Path: /i18n/languages
 */

require_once dirname(__FILE__) . '/includes/classes/Autoload.php';
require_once dirname(__FILE__) . '/vendor/autoload.php';

# Load plugin functionality
require_once(ABSPATH . '/wp-admin/includes/plugin.php');

define('PPMFWC_WOOCOMMERCE_TEXTDOMAIN', 'woocommerce-paynl-payment-methods');
define('PPMFWC_PLUGIN_URL', plugins_url('/', __FILE__));
define('PPMFWC_PLUGIN_PATH', plugin_dir_path(__FILE__));

# Load textdomain
load_plugin_textdomain(PPMFWC_WOOCOMMERCE_TEXTDOMAIN, false, 'woocommerce-paynl-payment-methods/i18n/languages');

# Check if Curl is available
if (!in_array('curl', get_loaded_extensions())) {
    add_action('admin_notices', 'ppmfwc_error_curl_not_installed');
}

# Register autoloader
PPMFWC_Autoload::ppmfwc_register();

# Register installer
register_activation_hook(__FILE__, array('PPMFWC_Setup', 'ppmfwc_install'));
add_action('init', array('PPMFWC_Setup', 'ppmfwc_install_init'));

if (is_plugin_active_for_network('woocommerce-paynl-payment-methods/woocommerce-payment-paynl.php')) {
    add_action('wp_initialize_site', array('PPMFWC_Setup', 'ppmfwc_newBlog'), 11);
    add_filter('wpmu_drop_tables', array('PPMFWC_Setup', 'ppmfwc_delBlog'));
}

if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active_for_network('woocommerce/woocommerce.php')) {
    # Register PAY gateway in WooCommerce
    PPMFWC_Gateways::ppmfwc_register();

    # Test if Pay. can be reached
    PPMFWC_Setup::ppmfwc_testConnection();

    # Register checkoutFlash
    PPMFWC_Gateways::ppmfwc_registerCheckoutFlash();

    # Register function calls to WooCommerce API
    PPMFWC_Gateways::ppmfwc_registerApi();

    # Add PAY settings tab in WooCommerce
    PPMFWC_Gateways::ppmfwc_settingsTab();

    add_action('wp_enqueue_scripts', function () {
        $blocks_js_route = PPMFWC_PLUGIN_URL . 'assets/js/paynl-blocks.js';
        $gateways = WC()->payment_gateways()->payment_gateways();
        $payGateways = [];

        $texts['issuer'] = __('Issuer', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['selectissuer'] = __('Select an issuer', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['enterbirthdate'] = __('Date of birth', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['enterCocNumber'] = __('COC number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['requiredCocNumber'] = __('Please enter your COC number, this field is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['enterVatNumber'] = __('VAT number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['requiredVatNumber'] = __('Please enter your VAT number, this field is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
        $texts['dobRequired'] = __('Please enter your date of birth, this field is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);

        foreach ($gateways as $gateway_id => $gateway) {
            /** @var PPMFWC_Gateway_Abstract $gateway */
            if (substr($gateway_id, 0, 11) != 'pay_gateway') {
                continue;
            }
            if ($gateway->enabled != 'yes') {
                continue;
            }
            $payGateways[] = array(
              'paymentMethodId' => $gateway_id,
              'title' => $gateway->get_title(),
              'description' => $gateway->description,
              'image_path' => $gateway->getIcon(),
              'issuers' => $gateway->getIssuers(),
              'issuersSelectionType' => $gateway->getSelectionType(),
              'texts' => $texts,
              'showbirthdate' => $gateway->askBirthdate(),
              'birthdateRequired' => $gateway->birthdateRequired(),
              'showVatField' => $gateway->showVat(),
              'vatRequired' => $gateway->vatRequired(),
              'showCocField' => $gateway->showCoc(),
              'cocRequired' => $gateway->cocRequired()
            );
        }
        wp_enqueue_script('paynl-blocks-js', $blocks_js_route, array('wc-blocks-registry'), (string)time(), true);
        wp_localize_script('paynl-blocks-js', 'paynl_gateways', $payGateways);
        wp_register_style('paynl-blocks-style', PPMFWC_PLUGIN_URL . 'assets/css/paynl_blocks.css');
        wp_enqueue_style('paynl-blocks-style');
    });

    # Add settings link on the plugin-page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ppmfwc_plugin_add_settings_link');

    if (!empty(get_option('paynl_show_vat_number')) && get_option('paynl_show_vat_number') != "no") {
        add_action('woocommerce_before_order_notes', 'ppmfwc_vatField');
        add_action('woocommerce_checkout_update_order_meta', 'ppmfwc_checkout_vat_number_update_order_meta');
        add_action('woocommerce_admin_order_data_after_billing_address', 'ppmfwc_vat_number_display_admin_order_meta', 10, 1);
    }

    if (!empty(get_option('paynl_show_coc_number')) && get_option('paynl_show_coc_number') != "no") {
        add_action('woocommerce_before_order_notes', 'ppmfwc_cocField');
        add_action('woocommerce_checkout_update_order_meta', 'ppmfwc_checkout_coc_number_update_order_meta');
        add_action('woocommerce_admin_order_data_after_billing_address', 'ppmfwc_coc_number_display_admin_order_meta', 10, 1);
    }

    if (get_option('paynl_standard_style') == "yes" || empty(get_option('paynl_standard_style'))) {
        add_action('wp_enqueue_scripts', 'ppmfwc_payStyle');
    }

    add_action('wp_enqueue_scripts', 'ppmfwc_payScript');

    if (get_option('paynl_auto_capture') == "yes" || get_option('paynl_auto_void') == "yes") {
        add_action('woocommerce_order_status_changed', 'ppmfwc_auto_functions', 10, 3);
    }
} else {
    # WooCommerce seems to be inactive, show eror message
    add_action('admin_notices', 'ppmfwc_error_woocommerce_not_active');
}

/**
 * Show WooCommerce error message
 * @return void
 */
function ppmfwc_error_woocommerce_not_active()
{
    echo '<div class="error"><p>' . esc_html(__('The Pay. Payment Methods for WooCommerce plugin requires WooCommerce to be active', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '</p></div>';
}

/**
 * Show curl error message
 * @return void
 */
function ppmfwc_error_curl_not_installed()
{
    echo '<div class="error"><p>' . esc_html(__('Curl is not installed. In order to use the Pay. payment methods, you must install install CURL. Ask your system administrator to install php_curl.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '</p></div>'; // phpcs:ignore
}

/**
 * @param array $links
 * @return mixed
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */
function ppmfwc_plugin_add_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('/admin.php?page=wc-settings&tab=' . PPMFWC_Gateways::TAB_ID) . '">' . esc_html(__('Settings')) . '</a>';
    array_push($links, $settings_link);
    return $links;
}

/**
 * @param object $checkout
 * @return void
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */
function ppmfwc_vatField($checkout)
{
    woocommerce_form_field('vat_number', array(
    'type' => 'text',
    'class' => array('vat-number-field form-row-wide'),
    'label' => esc_html(__('VAT number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
    'placeholder' => esc_html(__('Enter your VAT number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
    'required' => (get_option('paynl_show_vat_number') == 'yes_required'),
    ), $checkout->get_value('vat_number'));
}

/**
 * @return void
 */
function ppmfwc_payStyle()
{
    if (is_checkout() == true) {
        wp_register_style('ppmfwc_checkout_style', PPMFWC_PLUGIN_URL . 'assets/css/paycheckout.css');
        wp_enqueue_style('ppmfwc_checkout_style');
    }
}

/**
 * @return void
 */
function ppmfwc_payScript()
{
    $scriptsAdded = array();
    if (is_checkout() == true) {
        $gateways = WC()->payment_gateways->payment_gateways();
        if ($gateways) {
            foreach ($gateways as $gateway) {
                if ($gateway->enabled == 'yes') {
                    if (!empty($gateway->settings['show_for_company']) && $gateway->settings['show_for_company'] != 'both' && !in_array('ppmfwc_checkout_script', $scriptsAdded)) {
                        //Register the Show for Company javascript
                        wp_register_script('ppmfwc_checkout_script', PPMFWC_PLUGIN_URL . 'assets/js/paycheckout.js', array('jquery'), '1.0', true);
                        wp_enqueue_script('ppmfwc_checkout_script');
                        $scriptsAdded[] = 'ppmfwc_checkout_script';
                    }
                    if (!empty($gateway->settings['applepay_detection']) && $gateway->settings['applepay_detection'] == 'yes' && !in_array('ppmfwc_applepay_script', $scriptsAdded)) {
                    //Register the Apple Pay Detection javascript
                        wp_register_script('ppmfwc_applepay_script', PPMFWC_PLUGIN_URL . 'assets/js/applepay.js', array('jquery'), '1.0', true);
                        wp_enqueue_script('ppmfwc_applepay_script');
                        $scriptsAdded[] = 'ppmfwc_applepay_script';
                    }
                }
            }
        }
    }
}

/**
 * @param object $checkout
 * @return void
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */
function ppmfwc_cocField($checkout)
{
    woocommerce_form_field('coc_number', array(
    'type' => 'text',
    'class' => array('coc-number-field form-row-wide'),
    'label' => esc_html(__('COC number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
    'placeholder' => esc_html(__('Enter your COC number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
    'required' => (get_option('paynl_show_coc_number') == 'yes_required'),
    ), $checkout->get_value('coc_number'));
}

/**
 * Save VAT-field in the order
 * @param string $order_id
 * @return void
 */
function ppmfwc_checkout_vat_number_update_order_meta($order_id)
{
    if (!empty($_POST['vat_number'])) {
        update_post_meta($order_id, '_vat_number', sanitize_text_field($_POST['vat_number']));
    }
}

/**
 * Show VAT in the WooCommerce admin
 * @param object $order
 * @return void
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */
function ppmfwc_vat_number_display_admin_order_meta($order)
{
    echo '<p><strong>' . esc_html(__('VAT number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ':</strong> ' . esc_html(get_post_meta($order->get_id(), '_vat_number', true)) . '</p>';
}

/**
 * Save COC-field in the order
 * @param string $order_id
 * @return void
 */
function ppmfwc_checkout_coc_number_update_order_meta(string $order_id)
{
    if (!empty($_POST['coc_number'])) {
        update_post_meta($order_id, '_coc_number', sanitize_text_field($_POST['coc_number']));
    }
}

/**
 * Show COC in the WooCommerce admin
 * @param object $order
 * @return void
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */
function ppmfwc_coc_number_display_admin_order_meta($order)
{
    echo '<p><strong>' . esc_html(__('COC number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ':</strong> ' . esc_html(get_post_meta($order->get_id(), '_coc_number', true)) . '</p>';
}


/**
 * @param string $order_id
 * @param string $old_status
 * @param string $new_status
 * @return void
 */
function ppmfwc_auto_functions($order_id, $old_status, $new_status)
{
    if (
        ($new_status == "completed" && get_option('paynl_auto_capture') == "yes") ||
        ($new_status == "cancelled" && get_option('paynl_auto_void') == "yes")
    ) {
        $order = new WC_Order($order_id);
        $transactionId = $order->get_transaction_id();
        $transactionLocalDB = PPMFWC_Helper_Transaction::getTransaction($transactionId);

        # Get transaction and make sure its status is Authorized
        if ($new_status == "completed" && get_option('paynl_auto_capture') == "yes" && !empty($transactionLocalDB['status']) && $transactionLocalDB['status'] == PPMFWC_Gateways::STATUS_AUTHORIZE) {
            try {
                PPMFWC_Gateway_Abstract::loginSDK();
                $bResult = \Paynl\Transaction::capture($transactionId);
                if ($bResult) {
                    $order->add_order_note(sprintf(esc_html(__('Pay.: Performed auto capture on transaction: %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $transactionId));
                } else {
                    throw new Exception('Could not capture');
                }
            } catch (Exception $e) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('Auto capture failed: ' . $e->getMessage(), $transactionId, array('wc-order-id' => $order_id));
            }
        } elseif ($new_status == "cancelled" && get_option('paynl_auto_void') == "yes" && !empty($transactionLocalDB['status']) && $transactionLocalDB['status'] == PPMFWC_Gateways::STATUS_AUTHORIZE) { // phpcs:ignore
            try {
                PPMFWC_Gateway_Abstract::loginSDK();
                $bResult = \Paynl\Transaction::void($transactionId);
                if ($bResult) {
                    $order->add_order_note(sprintf(esc_html(__('Pay.: Performed auto void on transaction: %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $transactionId));
                } else {
                    throw new Exception('Could not void');
                }
            } catch (Exception $e) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('Auto void failed: ' . $e->getMessage(), $transactionId, array('wc-order-id' => $order_id));
            }
        }
    }
}
