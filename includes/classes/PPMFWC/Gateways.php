<?php

class PPMFWC_Gateways
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_DENIED = 'DENIED';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_AUTHORIZE = 'AUTHORIZE';
    const STATUS_VERIFY = 'VERIFY';
    const STATUS_REFUND = 'REFUND';
    const STATUS_REFUND_PARTIALLY = 'PARTREF';

    const ACTION_NEWPPT = 'new_ppt';
    const ACTION_PENDING = 'pending';
    const ACTION_CANCEL = 'cancel';
    const ACTION_VERIFY = 'verify';
    const ACTION_REFUND = 'refund:received';

    private static $arrGateways = array(
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
      'PPMFWC_Gateway_Good4fun',
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
      'PPMFWC_Gateway_Trustly',
      'PPMFWC_Gateway_Visamastercard',
      'PPMFWC_Gateway_Vvvgiftcard',
      'PPMFWC_Gateway_Webshopgiftcard',
      'PPMFWC_Gateway_Wijncadeau',
      'PPMFWC_Gateway_Wechatpay',
      'PPMFWC_Gateway_Yourgift',
      'PPMFWC_Gateway_Yehhpay',
    );

    public static function ppmfwc_getGateways($arrDefault)
    {
        $paymentOptions = self::$arrGateways;

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
     * @return array
     */
    public static function ppmfwc_addGlobalSettings($settings)
    {
        $loadedPaymentMethods = "";
        try {
            PPMFWC_Helper_Data::loadPaymentMethods();

            $arrOptions = PPMFWC_Helper_Data::getOptions();
            $loadedPaymentMethods .= '<br /><br />' . esc_html(__('The following payment methods can be enabled', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));

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
                    $error = __('API token and Service id are required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
                } else {
                    $error = '';
                }
            } else if (strlen($current_apitoken . $current_serviceid) == 0) {
                $error = __('API token and Service id are required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
            } else if (strlen($current_apitoken) == 0) {
                $error = __('API token is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
            } else if (strlen($current_serviceid) == 0) {
                $error = __('Service id is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
            }

            if ($error == 'HTTP/1.0 401 Unauthorized') {
                $error = __('API token is invalid.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
            }
            if ($error == 'PAY-404 - Service not found') {
                $error = __('Service id is invalid.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN);
            }

            if (strlen($error) > 0) {
                $loadedPaymentMethods = '<span style="color:#ff0000; font-weight:bold;">' . esc_html(__('Error:', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ' ' . esc_html($error) . '</span>';
            }
        }

        $updatedSettings = array();
        $addedSettings = array();
        $addedSettings[] = array(
            'title' => esc_html(__('PAY. settings', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'title',
            'desc' => '<p>' . $loadedPaymentMethods . '</p><p style="margin-top: 25px;">' . esc_html(__('The following options are required to use the PAY. Payment Gateway and are used by all PAY. Payment Methods', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)). '</p>',
            'id' => 'paynl_global_settings',
        );
        $addedSettings[] = array(
            'name' => esc_html(__('Token Code', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'placeholder' => 'AT-####-####',
            'type' => 'text',
            'desc' => esc_html(__('The AT-code belonging to your token, you can find this ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '<a href="https://admin.pay.nl/company/tokens" target="api_token">here</a>',
            'id' => 'paynl_tokencode',
        );
        $addedSettings[] = array(
            'name' => esc_html( __('Api token', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'text',
            'desc' => esc_html(__('The api token used to communicate with the PAY. API, you can find your token ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)).'<a href="https://admin.pay.nl/company/tokens" target="api_token">here</a>',
            'id' => 'paynl_apitoken',
        );
        $addedSettings[] = array(
            'name' => esc_html(__('Service id', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'placeholder' => 'SL-####-####',
            'type' => 'text',
            'desc' => esc_html(__('The serviceid to identify your website, you can find your serviceid here ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)). '<a href="https://admin.pay.nl/programs/programs" target="serviceid">here</a>',
            'id' => 'paynl_serviceid',
            'desc_tip' => __('The serviceid should be in the following format: SL-xxxx-xxxx', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
        );
        $addedSettings[] = array(
            'name' => esc_html(__('Test mode', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to enable test mode', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_test_mode',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => esc_html(__('SSL Verify Peer', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'checkbox',
            'desc' => esc_html(__('Uncheck this box if you have SSL certificate errors that you don\'t know how to fix', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_verify_peer',
            'default' => 'yes',
        );
        $addedSettings[] = array(
            'name' => esc_html(__('Send order data', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to send the order data to PAY., this is required if you want use \'Pay after delivery\' paymentmethods ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_send_order_data',
            'default' => 'yes',
        );
        $addedSettings[] = array(
            'name' => esc_html(__('Show VAT number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to show VAT number in checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_show_vat_number',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => esc_html(__('Show COC number', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to show COC number in checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_show_coc_number',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Use high risk methods', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__("Check this box if you are using high risk payment methods", PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_high_risk',
            'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Payment screen language', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'select',
            'options' => PPMFWC_Helper_Data::ppmfwc_getAvailableLanguages(),
            'desc' => esc_html(__('This is the language in which the payment screen will be shown', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_language',
            'default' => 'nl',
        );
        $addedSettings[] = array(
            'name' => __('Show payment method logos', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'select',
            'options' => PPMFWC_Helper_Data::ppmfwc_getLogoSizes(),
            'desc' => esc_html(__('This is the size in which the payment method logos will be shown', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_logo_size',
            'default' => 'Auto',
        );
        $addedSettings[] = array(
            'name' => __('Standard PAY. style', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            'type' => 'checkbox',
            'desc' => esc_html(__('Check this box if you want to use the standard PAY. style in the checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            'id' => 'paynl_standard_style',
            'default' => 'no',
          );
       $addedSettings[] = array(
          'name' => __('Extended Logging', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
          'type' => 'checkbox',
          'desc' => esc_html(__("Log payment information. Logfiles can be found at: WooCommerce > Status > Logs", PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
          'id' => 'paynl_paylogger',
          'default' => 'yes',
        );
       $addedSettings[] = array(
          'name' => __('Refund processing', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
          'type' => 'checkbox',
          'desc' => esc_html(__("Process refunds initiated from PAY admin", PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
          'id' => 'paynl_externalrefund',
          'default' => 'no',
        );
        $addedSettings[] = array(
            'name' => __('Alternative Exchange URL', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
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
        wp_register_style('paynl_wp_admin_css', PPMFWC_PLUGIN_URL . 'assets/css/pay.css', false, '1.0.0');
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

        PPMFWC_Helper_Data::ppmfwc_payLogger('FINISH, back from PAY payment', $orderId, array('orderStatusId' => $orderStatusId, 'status' => $status));

        try {
            # Retrieve URL to continue (and update status if necessary)
            if (!empty($orderId))
            {
                $newStatus = PPMFWC_Helper_Transaction::processTransaction($orderId, $status);
                try {
                    $transactionLocalDB = PPMFWC_Helper_Transaction::getTransaction($orderId);
                    if (!$transactionLocalDB || empty($transactionLocalDB['order_id'])) {
                        throw new PPMFWC_Exception_Notice('Could not find local transaction for order ' . $orderId);
                    }
                    $order = new WC_Order($transactionLocalDB['order_id']);

                    $url = self::getOrderReturnUrl($order, $newStatus);
                } catch (Exception $e) {
                    PPMFWC_Helper_Data::ppmfwc_payLogger('Exception: ' . $e->getMessage(), $orderId);
                }
            }
        } catch (PPMFWC_Exception_Notice $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not retrieve url to continue: ' . $e->getMessage(), $orderId);
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not retrieve url to continue: ' . $e->getMessage(), $orderId, array(), 'error');
        }

        wp_redirect($url);
    }


    public static function getOrderReturnUrl(WC_Order $order, $newStatus)
    {
        if ($newStatus == PPMFWC_Gateways::STATUS_CANCELED)
        {
            $url = add_query_arg('paynl_status', PPMFWC_Gateways::STATUS_CANCELED, wc_get_checkout_url());
        } elseif ($newStatus == PPMFWC_Gateways::STATUS_DENIED)
        {
            wc_add_notice(esc_html(__('Payment denied. Please try again or use another payment method.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), 'error');
            $url = add_query_arg('paynl_status', PPMFWC_Gateways::STATUS_DENIED, wc_get_checkout_url());
        } elseif ($newStatus == PPMFWC_Gateways::STATUS_PENDING)
        {
            $url = add_query_arg('paynl_status', PPMFWC_Gateways::STATUS_PENDING, $order->get_checkout_order_received_url());
        } else
        {

            $return_url = $order->get_checkout_order_received_url();
            if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
                $return_url = str_replace('http:', 'https:', $return_url);
            }

            $url = apply_filters('woocommerce_get_return_url', $return_url, $order);
        }

        return $url;
    }


    /**
     * Retrieve a textual-status by statusId
     *
     * @param $statusId
     * @return string
     */
    public static function ppmfwc_getStatusFromStatusId($statusId)
    {
        $status = null;
        if ($statusId == 100) {
            $status = self::STATUS_SUCCESS;
        } elseif ($statusId == 95) {
            $status = self::STATUS_AUTHORIZE;
        } elseif ($statusId == 85) {
            $status = self::STATUS_VERIFY;
        } elseif ($statusId == -81) {
            $status = SELF::STATUS_REFUND;
        } elseif ($statusId == -82) {
            $status = SELF::STATUS_REFUND_PARTIALLY;
        } elseif ($statusId == -63) {
            $status = SELF::STATUS_DENIED;
        } elseif ($statusId < 0) {
            $status = self::STATUS_CANCELED;
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
     * Return Gateway object based on payment_profile_id
     *
     * @param $payment_profile_id
     * @return mixed|null
     */
    public static function ppmfwc_getGateWayById($payment_profile_id)
    {
        foreach (self::$arrGateways as $strGateway) {
            $optionId = call_user_func(array($strGateway, 'getOptionId'));
            if (!empty($optionId) && $payment_profile_id == $optionId) {
                return new $strGateway();
            }
        }
        return null;
    }

    /**
     * Handles the PAY. Exchange requests
     *
     */
    public static function ppmfwc_onExchange()
    {
        $action = isset($_REQUEST['action']) ? strtolower(sanitize_text_field($_REQUEST['action'])) : null;
        $order_id = isset($_REQUEST['order_id']) ? sanitize_text_field($_REQUEST['order_id']) : null;
        $wc_order_id = isset($_REQUEST['extra1']) ? sanitize_text_field($_REQUEST['extra1']) : null;
        $methodId = isset($_REQUEST['payment_profile_id']) ? sanitize_text_field($_REQUEST['payment_profile_id']) : null;

        $arrActions = self::ppmfwc_getPayActions();
        $message = 'TRUE|Ignoring ' . $action;

        ob_start();
        try {
            if(in_array($action, array_keys($arrActions))) {
                $status = $arrActions[$action];
            } else {
                throw new PPMFWC_Exception_Notice('Ignoring: ' . $action);
            }

            if (!in_array($action, array(SELF::ACTION_PENDING))) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('Exchange incoming', $order_id, array('action' => $action, 'wc_order_id' => $wc_order_id, 'methodid' => $methodId));

                # Try to update the orderstatus.
                $newStatus = PPMFWC_Helper_Transaction::processTransaction($order_id, $status, $methodId);
                $message = 'TRUE|Status updated to ' . $newStatus;
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
        if ($paynlstatus == self::STATUS_PENDING) {
            add_action('woocommerce_before_thankyou', array(__CLASS__, 'ppmfwc_displayFlashPending'), 20);
        }
    }

    public static function ppmfwc_displayFlashCanceled()
    {
        wc_print_notice(__('The payment has been canceled, please try again', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), 'error');
    }

    public static function ppmfwc_displayFlashPending()
    {
        wc_print_notice(__('The payment is pending or not completed', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), 'notice');
    }

}
