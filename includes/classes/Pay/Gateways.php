<?php

class Pay_Gateways
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_VERIFY = 'VERIFY';

    public static function _getGateways($arrDefault)
    {
        $paymentOptions = array(
            'Pay_Gateway_Alipay',
            'Pay_Gateway_Amazonpay',
            'Pay_Gateway_Amex',
            'Pay_Gateway_Applepay',
            'Pay_Gateway_Afterpay',
            'Pay_Gateway_Billink',
            'Pay_Gateway_Cartasi',
            'Pay_Gateway_Capayable',
            'Pay_Gateway_CapayableGespreid',
            'Pay_Gateway_Cartebleue',
            'Pay_Gateway_Clickandbuy',
            'Pay_Gateway_CreditClick',
            'Pay_Gateway_Cashly',
            'Pay_Gateway_Dankort',
            'Pay_Gateway_DeCadeaukaart',
            'Pay_Gateway_Eps',
            'Pay_Gateway_Fashioncheque',
            'Pay_Gateway_Fashiongiftcard',
            'Pay_Gateway_Focum',
            'Pay_Gateway_Gezondheidsbon',
            'Pay_Gateway_Giropay',
            'Pay_Gateway_Givacard',
            'Pay_Gateway_Ideal',
            'Pay_Gateway_Incasso',
            'Pay_Gateway_Instore',
            'Pay_Gateway_Klarna',
            'Pay_Gateway_Klarnakp',
            'Pay_Gateway_Maestro',
            'Pay_Gateway_Minitixsms',
            'Pay_Gateway_Mistercash',
            'Pay_Gateway_Multibanco',
            'Pay_Gateway_Mybank',
            'Pay_Gateway_OkPayments',
            'Pay_Gateway_Overboeking',
            'Pay_Gateway_P24',
            'Pay_Gateway_Payconiq',
            'Pay_Gateway_Paypal',
            'Pay_Gateway_Paysafecard',
            'Pay_Gateway_Phone',
            'Pay_Gateway_Podiumcadeaukaart',
            'Pay_Gateway_Postepay',
            'Pay_Gateway_Sofortbanking',
            'Pay_Gateway_Spraypay',
            'Pay_Gateway_Tikkie',
            'Pay_Gateway_Visamastercard',
            'Pay_Gateway_Vvvgiftcard',
            'Pay_Gateway_Webshopgiftcard',
            'Pay_Gateway_Wijncadeau',
            'Pay_Gateway_Wechatpay',
            'Pay_Gateway_Yourgift',
            'Pay_Gateway_Yehhpay',
        );

        $paymentOptionsAvailable = array();
        foreach ($paymentOptions as $paymentOption) {
            $optionId = call_user_func(array($paymentOption, 'getOptionId'));
            $available = Pay_Helper_Data::isOptionAvailable($optionId);
            if ($available) {
                $paymentOptionsAvailable[] = $paymentOption;
            }
        }
        if (empty($paymentOptionsAvailable)) {
            $paymentOptionsAvailable = $paymentOptions;
        }

        $arrDefault = array_merge($arrDefault, $paymentOptionsAvailable);

        return $arrDefault;
    }

    public static function _addGlobalSettings($settings)
    {
        $loadedPaymentMethods = "";
        try {
            Pay_Helper_Data::loadPaymentMethods();

            $arrOptions = Pay_Helper_Data::getOptions();
            $loadedPaymentMethods .= '<br /><br />' . __('The following payment methods can be enabled', PAYNL_WOOCOMMERCE_TEXTDOMAIN);

            $loadedPaymentMethods .= '<ul>';
            foreach ($arrOptions as $option) {
                $loadedPaymentMethods .= '<li style="float: left; width:300px; display:block;"><img height="50px" src="' . $option['image'] . '" alt="' . $option['name'] . '" title="' . $option['name'] . '" /> ' . $option['name'] . '</li>';
            }
            $loadedPaymentMethods .= '</ul>';
            $loadedPaymentMethods .= '<div class="clear"></div>';
        } catch (Exception $e) {
            $current_apitoken = get_option('paynl_apitoken');
            $current_serviceid = get_option('paynl_serviceid');
            $current_tokencode = get_option('paynl_tokencode');

            $error = $e->getMessage();
            if(strlen($current_apitoken . $current_serviceid . $current_tokencode) == 0){
                if(count($_POST)>0){
                    $error = __('API token and Service id are required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
                } else{
                    $error = '';
                }
            } else if(strlen($current_apitoken . $current_serviceid) == 0){
                $error = __('API token and Service id are required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            } else if(strlen($current_apitoken) == 0){
                $error = __('API token is required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            } else if(strlen($current_serviceid) == 0){
                $error = __('Service id is required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            }

            if($error == 'HTTP/1.0 401 Unauthorized'){
                $error = __('API token is invalid.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            }
            if($error  == 'PAY-404 - Service not found'){
                $error = __('Service id is invalid.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            }

            if(strlen($error)>0){
                $loadedPaymentMethods = '<span style="color:#ff0000; font-weight:bold;">'.__('Error:', PAYNL_WOOCOMMERCE_TEXTDOMAIN).' ' . $error . '</span>';
            }
        }

        $updatedSettings = array();
        $addedSettings = array();
        $addedSettings[] = array(
            'title' => __('PAY. settings', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'title',
            'desc' => '<p>' . $loadedPaymentMethods . '</p><p style="margin-top: 25px;">' . __('The following options are required to use the PAY. Payment Gateway and are used by all PAY. Payment Methods', PAYNL_WOOCOMMERCE_TEXTDOMAIN) . '</p>',
            'id' => 'paynl_global_settings',
        );
        $addedSettings[] = array(
            'name' => __('Token Code', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'placeholder' => 'AT-####-####',
            'type' => 'text',
            'desc' => __('The AT-code belonging to your token, you can find this <a href="https://admin.pay.nl/company/tokens" target="api_token">here</a>', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_tokencode',
        );
        $addedSettings[] = array(
            'name' => __('Api token', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'text',
            'desc' => __('The api token used to communicate with the PAY. API, you can find your token <a href="https://admin.pay.nl/company/tokens" target="api_token">here</a>', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_apitoken',
        );
        $addedSettings[] = array(
            'name' => __('Service id', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'placeholder' => 'SL-####-####',
            'type' => 'text',
            'desc' => __('The serviceid to identify your website, you can find your serviceid <a href="https://admin.pay.nl/programs/programs" target="serviceid">here</a>', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_serviceid',
            'desc_tip' => __('The serviceid should be in the following format: SL-xxxx-xxxx', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
        );
        $addedSettings[] = array(
            'name' => __('Test mode', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => __('Check this box if you want to enable test mode', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_test_mode',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('SSL Verify Peer', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => __('Uncheck this box if you have SSL certificate errors that you don\'t know how to fix', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_verify_peer',
            'default' => 'yes',
        );
        $addedSettings[] = array(
            'name' => __('Send order data', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => __('Check this box if you want to send the order data to PAY., this is required if you want use \'Pay after delivery\' paymentmethods ', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_send_order_data',
            'default' => 'yes',
        );
        $addedSettings[] = array(
            'name' => __('Show VAT number', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => __('Check this box if you want to show VAT number in checkout', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_show_vat_number',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Show COC number', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => __('Check this box if you want to show COC number in checkout', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_show_coc_number',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Use high risk methods', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => __("Check this box if you are using high risk payment methods", PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_high_risk',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Payment screen language', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'select',
            'options' => Pay_Helper_Data::getAvailableLanguages(),
            'desc' => __('This is the language in which the payment screen will be shown', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_language',
            'default' => 'nl',
        );
        $addedSettings[] = array(
            'name' => __('Show payment method logos', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'select',
            'options' => Pay_Helper_Data::getLogoSizes(),
            'desc' => __('This is the size in which the payment method logos will be shown', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'id' => 'paynl_logo_size',
            'default' => 'Auto',
        );
        $addedSettings[] = array(
            'name' => __('Alternative Exchange URL', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'text',
            'placeholder' => 'https://www.yourdomain.nl/exchange_handler',
            'desc' => '<br>Use your own exchange-handler. Requests will be send as GET. <br> '.
                      'Example: https://www.yourdomain.nl/exchange_handler?action=#action#&order_id=#order_id#'.
                      '<Br>For more info see: <a href="https://docs.pay.nl/developers#exchange-parameters">docs.pay.nl</a>',
            'id' => 'paynl_exchange_url',
        );
        $addedSettings[] = array(
            'type' => 'sectionend',
            'id' => 'paynl_global_settings',
        );
        foreach ($settings as $setting) {
            if (isset($setting['id']) && $setting['id'] == 'payment_gateways_options' && $setting['type'] != 'sectionend') {
                $updatedSettings = array_merge($updatedSettings, $addedSettings);
            }
            $updatedSettings[] = $setting;
        }

        return $updatedSettings;
    }

    /**
     * This function registers the Pay Payment Gateways
     */
    public static function register()
    {
        add_filter('woocommerce_payment_gateways', array(__CLASS__, '_getGateways'));
    }


    public static function _addPayStyleSheet()
    {
      wp_register_style( 'custom_wp_admin_css', plugin_dir_url( __FILE__ ) . '/css/pay.css', false, '1.0.0' );
      wp_enqueue_style( 'custom_wp_admin_css' );
    }

  /**
     *
     *
     * This function adds the Pay Global Settings to the WooCommerce payment method settings
     */
    public static function addSettings()
    {
      add_action('admin_enqueue_scripts', array(__CLASS__, '_addPayStyleSheet'));
      add_filter('woocommerce_payment_gateways_settings', array(__CLASS__, '_addGlobalSettings'));
    }

    /**
     * Register the API's to catch the return and exchange
     */
    public static function registerApi()
    {
        add_action('woocommerce_api_wc_pay_gateway_return', array(__CLASS__, '_onReturn'));
        add_action('woocommerce_api_wc_pay_gateway_exchange', array(__CLASS__, '_onExchange'));
    }

    public static function _onReturn()
    {
        $status = self::getStatusFromStatusId($_GET['orderStatusId']);
        try {
            $url = Pay_Helper_Transaction::processTransaction($_GET['orderId'], $status);
        } catch (Pay_Exception_Notice $e) {
            // just ignore the notices
        }

        wp_redirect($url);
    }

    public static function getStatusFromStatusId($statusId)
    {
        if (
            $statusId == 100 ||
            $statusId == 95
        ) {
            $status = self::STATUS_SUCCESS;
        } elseif ($statusId == 85) {
            $status = self::STATUS_VERIFY;
        } elseif ($statusId < 0) {
            $status = self::STATUS_CANCELED;
        } else {
            $status = self::STATUS_PENDING;
        }
        return $status;
    }


    public static function _onExchange()
    {
        $message = "";
        ob_start();
        try {
            $pending = false;
            if ($_REQUEST['action'] == 'new_ppt') {
                $status = self::STATUS_SUCCESS;
            } elseif ($_REQUEST['action'] == 'pending') {
                $status = self::STATUS_PENDING;
                $pending = true;
                $message = 'TRUE|Ignoring pending';
            } elseif ($_REQUEST['action'] == 'cancel') {
                $status = self::STATUS_CANCELED;
            } elseif ($_REQUEST['action'] == 'verify') {
                $status = self::STATUS_VERIFY;
            } else {
                die('TRUE| Unknown action ' . $_REQUEST['action'] . ' not doing anything');
            }
            if (!$pending) {
                Pay_Helper_Transaction::processTransaction($_REQUEST['order_id'], $status);

                $message = 'TRUE|Status updated to ' . $status;
            }
        } catch (Pay_Exception_Notice $e) {
            $message = 'TRUE|Notice: ' . $e->getMessage();
        } catch (Pay_Exception $e) {
            $message = 'False|Error: ' . $e->getMessage();
        }
        $suppressed = ob_get_clean();

        if (!empty($suppressed)) {
            $message .= ' |Suppressed Output: ' . $suppressed;
        }
        die($message);
    }

    public static function registerCheckoutFlash()
    {
        if (isset($_REQUEST['paynl_status']) && $_REQUEST['paynl_status'] == self::STATUS_CANCELED) {
            add_action('woocommerce_before_checkout_form', array(__CLASS__, 'displayFlashCanceled'), 20);
        }
    }

    public static function displayFlashCanceled()
    {
        wc_print_notice(__('The payment has been canceled, please try again', PAYNL_WOOCOMMERCE_TEXTDOMAIN), 'error');
    }
}
