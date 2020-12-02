<?php

class PPMFWC_Gateways
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_VERIFY = 'VERIFY';
    const STATUS_REFUND = 'REFUND';

    const ACTION_NEWPPT = 'new_ppt';
    const ACTION_PENDING = 'pending';
    const ACTION_CANCEL = 'cancel';
    const ACTION_VERIFY = 'verify';
    const ACTION_REFUND = 'refund:received';

    public static function ppmfwc_getGateways($arrDefault)
    {
        $paymentOptions = array(
            'PPMFWC_Gateway_Alipay',
            'PPMFWC_Gateway_Amazonpay',
            'PPMFWC_Gateway_Amex',
            'PPMFWC_Gateway_Applepay',
            'PPMFWC_Gateway_Afterpay',
            'PPMFWC_Gateway_Billink',
            'PPMFWC_Gateway_Cartasi',
            'PPMFWC_Gateway_Capayable',
            'PPMFWC_Gateway_CapayableGespreid',
            'PPMFWC_Gateway_Cartebleue',
            'PPMFWC_Gateway_Clickandbuy',
            'PPMFWC_Gateway_CreditClick',
            'PPMFWC_Gateway_Cashly',
            'PPMFWC_Gateway_Dankort',
            'PPMFWC_Gateway_DeCadeaukaart',
            'PPMFWC_Gateway_Eps',
            'PPMFWC_Gateway_Fashioncheque',
            'PPMFWC_Gateway_Fashiongiftcard',
            'PPMFWC_Gateway_Focum',
            'PPMFWC_Gateway_Gezondheidsbon',
            'PPMFWC_Gateway_Giropay',
            'PPMFWC_Gateway_Givacard',
            'PPMFWC_Gateway_Ideal',
            'PPMFWC_Gateway_Incasso',
            'PPMFWC_Gateway_Instore',
            'PPMFWC_Gateway_Klarna',
            'PPMFWC_Gateway_Klarnakp',
            'PPMFWC_Gateway_Maestro',
            'PPMFWC_Gateway_Minitixsms',
            'PPMFWC_Gateway_Mistercash',
            'PPMFWC_Gateway_Multibanco',
            'PPMFWC_Gateway_Mybank',
            'PPMFWC_Gateway_OkPayments',
            'PPMFWC_Gateway_Overboeking',
            'PPMFWC_Gateway_P24',
            'PPMFWC_Gateway_Payconiq',
            'PPMFWC_Gateway_Paypal',
            'PPMFWC_Gateway_Paysafecard',
            'PPMFWC_Gateway_Phone',
            'PPMFWC_Gateway_Podiumcadeaukaart',
            'PPMFWC_Gateway_Postepay',
            'PPMFWC_Gateway_Sofortbanking',
            'PPMFWC_Gateway_Spraypay',
            'PPMFWC_Gateway_Tikkie',
            'PPMFWC_Gateway_Visamastercard',
            'PPMFWC_Gateway_Vvvgiftcard',
            'PPMFWC_Gateway_Webshopgiftcard',
            'PPMFWC_Gateway_Wijncadeau',
            'PPMFWC_Gateway_Wechatpay',
            'PPMFWC_Gateway_Yourgift',
            'PPMFWC_Gateway_Yehhpay',
        );

        $paymentOptionsAvailable = array();
        foreach ($paymentOptions as $paymentOption) {
            $optionId = call_user_func(array($paymentOption, 'getOptionId'));
            $available = PPMFWC_Helper_Data::isOptionAvailable($optionId);
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

    /**
     * @param $settings
     * @return array|void
     */
    public static function ppmfwc_addGlobalSettings($settings)
    {
        $loadedPaymentMethods = "";
        try {
            PPMFWC_Helper_Data::loadPaymentMethods();

            $arrOptions = PPMFWC_Helper_Data::getOptions();
            $loadedPaymentMethods .= '<br /><br />' . esc_html(__('The following payment methods can be enabled', PAYNL_WOOCOMMERCE_TEXTDOMAIN));

            $loadedPaymentMethods .= '<ul>';
            foreach ($arrOptions as $option) {
                $loadedPaymentMethods .= '<li style="float: left; width:300px; display:block;"><img height="50px" src="' . esc_attr($option['image']) . '" alt="' . esc_attr($option['name'])
                  . '" title="' . esc_attr($option['name']) . '" /> ' . esc_attr($option['name']) . '</li>';
            }
            $loadedPaymentMethods .= '</ul>';
            $loadedPaymentMethods .= '<div class="clear"></div>';
        } catch (Exception $e) {
            $current_apitoken = get_option('paynl_apitoken');
            $current_serviceid = get_option('paynl_serviceid');
            $current_tokencode = get_option('paynl_tokencode');

            $error = $e->getMessage();
            if (strlen($current_apitoken . $current_serviceid . $current_tokencode) == 0) {
                if (count($_POST) > 0) {
                    $error = __('API token and Service id are required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
                } else {
                    $error = '';
                }
            } else if (strlen($current_apitoken . $current_serviceid) == 0) {
                $error = __('API token and Service id are required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            } else if (strlen($current_apitoken) == 0) {
                $error = __('API token is required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            } else if (strlen($current_serviceid) == 0) {
                $error = __('Service id is required.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            }

            if ($error == 'HTTP/1.0 401 Unauthorized') {
                $error = __('API token is invalid.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            }
            if ($error == 'PAY-404 - Service not found') {
                $error = __('Service id is invalid.', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            }

            if (strlen($error) > 0) {
                $loadedPaymentMethods = '<span style="color:#ff0000; font-weight:bold;">' . esc_html(__('Error:', PAYNL_WOOCOMMERCE_TEXTDOMAIN)) . ' ' . esc_html($error) . '</span>';
            }
        }

        $updatedSettings = array();
        $addedSettings = array();
        $addedSettings[] = array(
            'title' => __('PAY. settings', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'title',
            'desc' => '<p>' . $loadedPaymentMethods . '</p><p style="margin-top: 25px;">' . esc_html(__('The following options are required to use the PAY. Payment Gateway and are used by all PAY. Payment Methods', PAYNL_WOOCOMMERCE_TEXTDOMAIN)). '</p>',
            'id' => 'paynl_global_settings',
        );
        $addedSettings[] = array(
            'name' => __('Token Code', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'placeholder' => 'AT-####-####',
            'type' => 'text',
            'desc' => esc_html(__('The AT-code belonging to your token, you can find this ', PAYNL_WOOCOMMERCE_TEXTDOMAIN)) . '<a href="https://admin.pay.nl/company/tokens" target="api_token">here</a>',
            'id' => 'paynl_tokencode',
        );
        $addedSettings[] = array(
            'name' => __('Api token', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'text',
            'desc' => esc_html(__('The api token used to communicate with the PAY. API, you can find your token ', PAYNL_WOOCOMMERCE_TEXTDOMAIN)).'<a href="https://admin.pay.nl/company/tokens" target="api_token">here</a>',
            'id' => 'paynl_apitoken',
        );
        $addedSettings[] = array(
            'name' => __('Service id', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'placeholder' => 'SL-####-####',
            'type' => 'text',
            'desc' => esc_html(__('The serviceid to identify your website, you can find your serviceid here ', PAYNL_WOOCOMMERCE_TEXTDOMAIN)). '<a href="https://admin.pay.nl/programs/programs" target="serviceid">here</a>',
            'id' => 'paynl_serviceid',
            'desc_tip' => __('The serviceid should be in the following format: SL-xxxx-xxxx', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
        );
        $addedSettings[] = array(
            'name' => __('Test mode', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to enable test mode', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_test_mode',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('SSL Verify Peer', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__('Uncheck this box if you have SSL certificate errors that you don\'t know how to fix', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_verify_peer',
            'default' => 'yes',
        );
        $addedSettings[] = array(
            'name' => __('Send order data', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to send the order data to PAY., this is required if you want use \'Pay after delivery\' paymentmethods ', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_send_order_data',
            'default' => 'yes',
        );
        $addedSettings[] = array(
            'name' => __('Show VAT number', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to show VAT number in checkout', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_show_vat_number',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Show COC number', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to show COC number in checkout', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_show_coc_number',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Use high risk methods', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__("Check this box if you are using high risk payment methods", PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_high_risk',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Payment screen language', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'select',
            'options' => PPMFWC_Helper_Data::ppmfwc_getAvailableLanguages(),
            'desc' => esc_html(__('This is the language in which the payment screen will be shown', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_language',
            'default' => 'nl',
        );
        $addedSettings[] = array(
            'name' => __('Show payment method logos', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'select',
            'options' => PPMFWC_Helper_Data::ppmfwc_getLogoSizes(),
            'desc' => esc_html(__('This is the size in which the payment method logos will be shown', PAYNL_WOOCOMMERCE_TEXTDOMAIN)),
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
    public static function ppmfwc_register()
    {
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'ppmfwc_getGateways'));
    }


    public static function ppmfwc_addPayStyleSheet()
    {
        wp_register_style('paynl_wp_admin_css', PAYNL_PLUGIN_URL . 'assets/css/pay.css', false, '1.0.0');
        wp_enqueue_style('paynl_wp_admin_css');
    }

    /**
     * This function adds the Pay Global Settings to the WooCommerce payment method settings
     */
    public static function ppmfwc_addSettings()
    {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'ppmfwc_addPayStyleSheet'));
        add_filter('woocommerce_payment_gateways_settings', array(__CLASS__, 'ppmfwc_addGlobalSettings'));
    }

    /**
     * Register the API's to catch the return and exchange
     */
    public static function ppmfwc_registerApi()
    {
        add_action('woocommerce_api_wc_pay_gateway_return', array(__CLASS__, 'ppmfwc_onReturn'));
        add_action('woocommerce_api_wc_pay_gateway_exchange', array(__CLASS__, 'ppmfwc_onExchange'));
    }

    /**
     * After a (successfull, failed, cancelled etc.) PAY payment the user wil end up here
     */
    public static function ppmfwc_onReturn()
    {
        $orderStatusId = isset($_GET['orderStatusId']) ? sanitize_text_field($_GET['orderStatusId']) : false;
        $orderId = isset($_GET['orderId']) ? sanitize_text_field($_GET['orderId']) : false;
        $url = wc_get_checkout_url();

        $status = self::ppmfwc_getStatusFromStatusId($orderStatusId);
        try {
            # Retrieve URL to continue (and update status if necessary)
            if (!empty($orderId)) {
                $url = PPMFWC_Helper_Transaction::processTransaction($orderId, $status);
            }

        } catch (PPMFWC_Exception_Notice $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not retrieve url to continue. Error: ' . $e->getMessage());
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not retrieve url to continue. Error: ' . $e->getMessage(), 'error');
        }

        wp_redirect($url);
    }

    /**
     * Retrieve a textual-status by statusId
     *
     * @param $statusId
     * @return string
     */
    public static function ppmfwc_getStatusFromStatusId($statusId)
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

    /**
     * Converts PAY. actions into the correct status
     *
     * @return mixed
     */
    public static function ppmfwc_getPayActions()
    {
        $arrPayActions[self::ACTION_NEWPPT] = self::STATUS_SUCCESS;
        $arrPayActions[self::ACTION_PENDING] = self::STATUS_PENDING;
        $arrPayActions[self::ACTION_CANCEL] = self::STATUS_CANCELED;
        $arrPayActions[self::ACTION_VERIFY] = self::STATUS_VERIFY;
        $arrPayActions[self::ACTION_REFUND] = self::STATUS_REFUND;
        return $arrPayActions;
    }

    /**
     * Handles the PAY. Exchange requests
     *
     */
    public static function ppmfwc_onExchange()
    {
        $action = isset($_GET['action']) ? strtolower(sanitize_text_field($_GET['action'])) : null;
        $order_id = isset($_REQUEST['order_id']) ? sanitize_text_field($_REQUEST['order_id']) : null;
        $arrActions = self::ppmfwc_getPayActions();
        $message = 'TRUE|Ignoring ' . $action;

        ob_start();
        try {
            if(in_array($action, array_keys($arrActions))) {
                $status = $arrActions[$action];
            } else {
                throw new PPMFWC_Exception_Notice('Unknown action: ' . $action);
            }

            if (!in_array($action, array(SELF::ACTION_PENDING, SELF::ACTION_REFUND))) {
                # Try to update the orderstatus.
                PPMFWC_Helper_Transaction::processTransaction($order_id, $status);
                $message = 'TRUE|Status updated to ' . $status;
            }

        } catch (PPMFWC_Exception_Notice $e) {
            $message = 'TRUE|Notice: ' . $e->getMessage();
        } catch (PPMFWC_Exception $e) {
            $message = 'False|Error 1: ' . $e->getMessage();
        } catch (Exception $e) {
            $message = 'False|Error 2: ' . $e->getMessage();
        }
        $suppressed = ob_get_clean();

        if (!empty($suppressed)) {
            $message .= ' |Suppressed Output: ' . $suppressed;
        }
        die($message);
    }

    public static function ppmfwc_registerCheckoutFlash()
    {
        $paynlstatus = isset($_REQUEST['paynl_status']) ? sanitize_text_field($_REQUEST['paynl_status']) : null;
        if ($paynlstatus == self::STATUS_CANCELED) {
            add_action('woocommerce_before_checkout_form', array(__CLASS__, 'ppmfwc_displayFlashCanceled'), 20);
        }
    }

    public static function ppmfwc_displayFlashCanceled()
    {
        wc_print_notice(__('The payment has been canceled, please try again', PAYNL_WOOCOMMERCE_TEXTDOMAIN), 'error');
    }
}