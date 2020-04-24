<?php

abstract class Pay_Gateway_Abstract extends WC_Payment_Gateway
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ON_HOLD = 'on-hold';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED = 'failed';

    public function __construct()
    {
        $this->id   = $this->getId();
        $this->icon = $this->getIcon();

        $this->has_fields         = true;
        $this->method_title       = 'PAY. - ' . $this->getName();
        $this->method_description = sprintf(__('Activate this module to accept %s transactions',
            PAYNL_WOOCOMMERCE_TEXTDOMAIN), $this->getName());

        $this->supports = array('products', 'refunds');

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
    }

    public static function getId()
    {
        throw new Exception('Please implement the getId method');
    }

    public function getIcon()
    {
        $size = get_option('paynl_logo_size');

        if ($size) {
            return 'https://static.pay.nl/payment_profiles/' . $size . '/' . $this->getOptionId() . '.png';
        } else {
            return '';
        }

    }

    public static function getOptionId()
    {
        throw new Exception('Please implement the getOptionId method');
    }

    public static function getName()
    {
        throw new Exception('Please implement the getName method');
    }

    public function getVersion()
    {
        return '3.4.4';
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $optionId = $this->getOptionId();
        if (Pay_Helper_Data::isOptionAvailable($optionId)) {
            $this->form_fields = array(
                'enabled'      => array(
                    'title'   => __('Enable/Disable', 'woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => sprintf(__('Enable PAY. %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN), $this->getName()),
                    'default' => 'no',
                ),
                'title'        => array(
                    'title'       => __('Title', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default'     => $this->getName(),
                    'desc_tip'    => true,
                ),
                'description'  => array(
                    'title'   => __('Customer Message', 'woocommerce'),
                    'type'    => 'textarea',
                    'default' => sprintf(__('Pay with %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN), $this->getName()),
                ),
                'instructions' => array(
                    'title'       => __('Instructions', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page.', 'woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'min_amount'   => array(
                    'title'       => __('Minimum amount', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    'type'        => 'price',
                    'description' => __('Minimum amount valid for this payment method, leave blank for no limit',
                        PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'max_amount'   => array(
                    'title'       => __('Maximum amount', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    'type'        => 'price',
                    'description' => __('Maximum amount valid for this payment method, leave blank for no limit',
                        PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );

            if ($this->slowConfirmation()) {
                $this->form_fields['initial_order_status'] = array(
                    'title'       => __('Initial order status', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    'type'        => 'select',
                    'options'     => array(
                        self::STATUS_ON_HOLD => wc_get_order_status_name(self::STATUS_ON_HOLD) . ' (' . __('default',
                                'woocommerce') . ')',
                        self::STATUS_PENDING => wc_get_order_status_name(self::STATUS_PENDING),
                    ),
                    'default'     => self::STATUS_ON_HOLD,
                    /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                    'description' => sprintf(
                        __('Some payment methods take longer than a few hours to complete. The initial order state is then set to \'%s\'. This ensures the order is not cancelled when the setting %s is used.',
                            PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                        wc_get_order_status_name(self::STATUS_ON_HOLD),
                        '<a href="' . admin_url('admin.php?page=wc-settings&tab=products&section=inventory') . '" target="_blank">' . __('Hold Stock (minutes)',
                            'woocommerce') . '</a>'
                    ),
                );
            }

        } else {
            $this->form_fields = array(
                'message' => array(
                    'title'       => __('Disabled', 'woocommerce'),
                    'type'        => 'hidden',
                    'description' => __('This payment method is not available, please enable this in the PAY. admin.',
                        PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    'label'       => sprintf(__('Enable PAY. %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN), $this->getName()),

                )
            );
        }
    }

    /**
     * @return bool Payment methods that are confirmed slowly (like banktransfer) should return true here
     */
    public static function slowConfirmation()
    {
        return false;
    }

    public function init_settings()
    {
        add_action('woocommerce_thankyou_' . $this->getId(), array($this, 'thankyou_page'));

        parent::init_settings();
    }

    public static function getTokenCode()
    {
        return get_option('paynl_tokencode');
    }

    protected static function is_high_risk()
    {
        $highrisk = get_option('paynl_high_risk');

        return $highrisk == 'yes';
    }

    public function is_available()
    {
        if ( ! parent::is_available()) {
            return false;
        }

        $min_amount = $this->get_option('min_amount');
        $max_amount = $this->get_option('max_amount');
        $orderTotal = $this->get_order_total();

        if ( ! empty($min_amount) && $orderTotal < (float)$min_amount) {
            return false;
        }
        if ( ! empty($max_amount) && $orderTotal > (float)$max_amount) {
            return false;
        }

        return true;
    }

    public function payment_fields()
    {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
    }

    public function process_payment($order_id)
    {
        /** @var $wpdb wpdb The database */
        $order = new WC_Order($order_id);

        $result = $this->startTransaction($order);

        $order->add_order_note(sprintf(__('PAY.: Transaction started: %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
            $result->getTransactionId()));

        if ($this->slowConfirmation()) {
            $initial_status = $this->get_option('initial_order_status');

            $order->update_status($initial_status,
                sprintf(__('Initial status set to %s ', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                    wc_get_order_status_name($initial_status)));
            if ($initial_status == self::STATUS_ON_HOLD) {
                // Reduce stock levels
                if (WooCommerce::instance()->version < 3) {
                    $order->reduce_order_stock();
                } else {
                    wc_reduce_stock_levels($order_id);
                }
            }
        }

        Pay_Helper_Transaction::newTransaction($result->getTransactionId(), $this->getOptionId(), $order->get_total(),
            $order_id, '');

        // Return thankyou redirect
        return array(
            'result'   => 'success',
            'redirect' => $result->getRedirectUrl()
        );
    }

    /**
     * @param WC_Order $order
     *
     * @return \Paynl\Result\Transaction\Start
     */
    protected function startTransaction(WC_Order $order)
    {
        $this->loginSDK();

        $returnUrl = add_query_arg(array(
            'wc-api'         => 'Wc_Pay_Gateway_Return'
        ), home_url('/'));

        $exchangeUrl = add_query_arg('wc-api', 'Wc_Pay_Gateway_Exchange', home_url('/'));

      # Wanneer er hekjes worden meegegeven aan de exchange, wordt dit gezien als een custom url.
      # Zo niet, dan wordt de custom exchange die wij hier meegeven wel gebruikt, alleen nog steeds via post en de sessionvar in de header.
      # Dus met hekjes, en exchange method op TXT, zorgt voor een GET-exchange
      # dus bij de instellingen moeten we benadrukken dat het om een GET gaat
      # en de hekjes geven we hier mee.

      $strAlternativeExchangeUrl = self::getAlternativeExchangeUrl();
      if (!empty(trim($strAlternativeExchangeUrl))) {
        $exchangeUrl = $strAlternativeExchangeUrl;
      }

      if (WooCommerce::instance()->version < 3 && $order->customer_ip_address) {
            $ipAddress = $order->customer_ip_address;
        } elseif ($order->get_customer_ip_address()) {
            $ipAddress = $order->get_customer_ip_address();
        } else {
            $ipAddress = Paynl\Helper::getIp();
        }

        if (WooCommerce::instance()->version < 3) {
            $currency = $order->order_currency;

            // This is not an error, this is for supporting older woocommerce versions.
            $order_id = $order->id;

            $shipping_first_name = $order->shipping_first_name;
            $shipping_last_name  = $order->shipping_last_name;
            $shipping_address_1  = $order->shipping_address_1;
            $shipping_address_2  = $order->shipping_address_2;
            $shipping_postcode   = $order->shipping_postcode;
            $shipping_city       = $order->shipping_city;
            $shipping_country    = $order->shipping_country;
            $shipping_state      = $order->shipping_state;

            $billing_email      = $order->billing_email;
            $billing_phone      = $order->billing_phone;
            $billing_first_name = $order->billing_first_name;
            $billing_last_name  = $order->billing_last_name;
            $billing_address_1  = $order->billing_address_1;
            $billing_address_2  = $order->billing_address_2;
            $billing_postcode   = $order->billing_postcode;
            $billing_city       = $order->billing_city;
            $billing_country    = $order->billing_country;
            $billing_state      = $order->billing_state;
        } else {
            $currency = $order->get_currency();

            $order_id = $order->get_id();

            $shipping_first_name = $order->get_shipping_first_name();
            $shipping_last_name  = $order->get_shipping_last_name();
            $shipping_address_1  = $order->get_shipping_address_1();
            $shipping_address_2  = $order->get_shipping_address_2();
            $shipping_postcode   = $order->get_shipping_postcode();
            $shipping_city       = $order->get_shipping_city();
            $shipping_country    = $order->get_shipping_country();
            $shipping_state      = $order->get_shipping_state();

            $billing_email      = $order->get_billing_email();
            $billing_phone      = $order->get_billing_phone();
            $billing_first_name = $order->get_billing_first_name();
            $billing_last_name  = $order->get_billing_last_name();
            $billing_address_1  = $order->get_billing_address_1();
            $billing_address_2  = $order->get_billing_address_2();
            $billing_postcode   = $order->get_billing_postcode();
            $billing_city       = $order->get_billing_city();
            $billing_country    = $order->get_billing_country();
            $billing_state      = $order->get_shipping_state();
        }
        if ($shipping_country && $shipping_state) {
            $shipping_state = "{$shipping_country}-{$shipping_state}";
        }
        if ($billing_country && $billing_state) {
            $billing_state = "{$billing_country}-{$billing_state}";
        }


        $startData = array(
            'amount'        => $order->get_total(),
            'returnUrl'     => $returnUrl,
            'exchangeUrl'   => $exchangeUrl,
            'orderNumber'   => $order->get_order_number(),
            'paymentMethod' => $this->getOptionId(),
            'currency'      => $currency,
            'description'   => $order->get_order_number(),
            'extra1'        => apply_filters('paynl-extra1', $order->get_order_number(), $order),
            'extra2'        => apply_filters('paynl-extra2', $billing_email, $order),
            'extra3'        => apply_filters('paynl-extra3', $order_id, $order),
            'ipaddress'     => $ipAddress,
            'object'        => 'woocommerce ' . $this->getVersion(),
        );

        if (get_option('paynl_send_order_data') == 'yes') {
            $enduser = array(
                'initials'     => $shipping_first_name,
                'lastName'     => substr($shipping_last_name,0,32),
                'phoneNumber'  => $billing_phone,
                'emailAddress' => $billing_email,
            );
            if (isset($_POST['birthdate']) && !empty($_POST['birthdate'])) {
                $enduser['birthDate'] = $_POST['birthdate'];
            }
            if (isset($_POST['birthdate_billink']) && !empty($_POST['birthdate_billink']) && $this->getOptionId() == 1672) {
                $enduser['birthDate'] = $_POST['birthdate_billink'];
            }
            if (isset($_POST['birthdate_capayble']) && !empty($_POST['birthdate_capayble']) && $this->getOptionId() == 1744) {
                $enduser['birthDate'] = $_POST['birthdate_capayble'];
            }
            if (isset($_POST['birthdate_capayble_gespreid']) && !empty($_POST['birthdate_capayble_gespreid']) && $this->getOptionId() == 1813) {
                $enduser['birthDate'] = $_POST['birthdate_capayble'];
            }
            if (isset($_POST['birthdate_klarna']) && !empty($_POST['birthdate_klarna']) && $this->getOptionId() == 1717) {
                $enduser['birthDate'] = $_POST['birthdate_klarna'];
            }
            if (isset($_POST['birthdate_yeahpay']) && !empty($_POST['birthdate_yeahpay']) && $this->getOptionId() == 1877) {
                $enduser['birthDate'] = $_POST['birthdate_yeahpay'];
            }            

            $enduser['company'] = array(
              'name' => $order->get_billing_company(),
              'countryCode' => $billing_country
            );

            if (!empty($_POST['vat_number'])) {
              $enduser['company']['vatNumber'] = sanitize_text_field($_POST['vat_number']);
            }

            if (!empty($_POST['coc_number'])) {
              $enduser['company']['cocNumber'] = sanitize_text_field($_POST['coc_number']);
            }

            $startData['enduser'] = $enduser;

            // order gegevens ophalen
            $shippingAddress  = $shipping_address_1 . ' ' . $shipping_address_2;
            $aShippingAddress = \Paynl\Helper::splitAddress($shippingAddress);

            $address              = array(
                'streetName'  => $aShippingAddress[0],
                'houseNumber' => $aShippingAddress[1],
                'zipCode'     => $shipping_postcode,
                'city'        => $shipping_city,
                'country'     => $shipping_country,
//	            'state' => $shipping_state
            );
            $startData['address'] = $address;

            $billingAddress  = $billing_address_1 . ' ' . $billing_address_2;
            $aBillingAddress = \Paynl\Helper::splitAddress($billingAddress);

            $invoiceAddress              = array(
                'initials'    => $billing_first_name,
                'lastName'    => substr($billing_last_name,0,32),
                'streetName'  => $aBillingAddress[0],
                'houseNumber' => $aBillingAddress[1],
                'zipCode'     => $billing_postcode,
                'city'        => $billing_city,
                'country'     => $billing_country,
//	            'state' => $billing_state
            );
            $startData['invoiceAddress'] = $invoiceAddress;
            $startData['products']       = $this->getProductLines($order);
        }

        if (isset($_POST['option_sub_id']) && ! empty($_POST['option_sub_id'])) {
            $startData['bank'] = $_POST['option_sub_id'];
        }
        if (get_option('paynl_test_mode') == 'yes') {
            $startData['testmode'] = true;
        }
        $language = get_option('paynl_language');

        if ($language == 'browser') {
            $language = Pay_Helper_Data::getBrowserLanguage();
        }

        $startData['language'] = $language;

        $result = \Paynl\Transaction::start($startData);

        if (WooCommerce::instance()->version > 3) {

            //this method was introduced in woocommerce 3.0
            $order->update_meta_data('transactionId', $result->getTransactionId());
            $order->save();
        } else {
            update_post_meta($order->id, 'transactionId', $result->getTransactionId());
        }

        return $result;
    }

  /**
   * @return mixed|string|void
   */
  public static function getAlternativeExchangeUrl()
  {
    $strAltUrl = get_option('paynl_exchange_url');

    if(!empty($strAltUrl)) {
      return $strAltUrl;
    }

    return '';
  }

    public static function loginSDK()
    {
        \Paynl\Config::setApiToken(self::getApiToken());
        \Paynl\Config::setServiceId(self::getServiceId());

        $tokenCode = self::getTokenCode();
        if(!empty($tokenCode)){
            \Paynl\Config::setTokenCode($tokenCode);
        }

        $verifyPeer = (get_option('paynl_verify_peer') == 'yes');
        \Paynl\Config::setVerifyPeer($verifyPeer);
    }

    public static function getApiToken()
    {
        return get_option('paynl_apitoken');
    }

    public static function getServiceId()
    {
        return get_option('paynl_serviceid');
    }

    private function getProductLines(WC_Order $order)
    {
        $items = $order->get_items();

        $aProducts = array();

        foreach ($items as $item) {
            $pricePerPiece = ($item['line_subtotal'] + $item['line_subtotal_tax']) / $item['qty'];
            $taxPerPiece   = $item['line_subtotal_tax'] / $item['qty'];
            $product       = array(
                'id'    => $item['product_id'],
                'name'  => $item['name'],
                'price' => $pricePerPiece,
                'qty'   => $item['qty'],
                'type'  => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE,
                'tax'   => $taxPerPiece
            );
            $aProducts[]   = $product;
        }
        if (WooCommerce::instance()->version < 3) {
            $shipping_total = $order->get_total_shipping();
        } else {
            $shipping_total = $order->get_shipping_total();
        }
        // verzendkosten meesturen
        $shipping = floatval($shipping_total) + floatval($order->get_shipping_tax());
        if ($shipping != 0) {
            $aProducts[] = array(
                'id'    => 'shipping',
                'name'  => __('Shipping', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                'price' => $shipping,
                'tax'   => $order->get_shipping_tax(),
                'qty'   => 1,
                'type'  => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            );
        }

        //korting meesturen
        $discount = $order->get_total_discount(false);
        if ($discount != 0) {
            $discountExcl = $order->get_total_discount(true);
            $discountTax  = $discount - $discountExcl;

            $aProducts[] = array(
                'id'    => 'discount',
                'name'  => __('Discount', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                'price' => $discount * -1,
                'qty'   => 1,
                'type'  => \Paynl\Transaction::PRODUCT_TYPE_DISCOUNT,
                'tax'   => $discountTax * -1
            );
        }

        //fees meesturen
        //Extra kosten meesturen
        $fees = $order->get_fees();
        if ( ! empty($fees)) {
            foreach ($fees as $fee) {
                $aProducts[] = array(
                    'id'    => $fee['type'],
                    'name'  => $fee['name'],
                    'price' => $fee['line_total'],
                    'qty'   => 1,
                    'type'  => \Paynl\Transaction::PRODUCT_TYPE_HANDLING
                );
            }
        }

        return $aProducts;
    }

    /**
     * Process a refund if supported
     *
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     *
     * @return  bool|wp_error True or false based on success, or a WP_Error object
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        if ($amount <= 0) {
            return new WP_Error('1', "Refund amount must be greater than €0.00");
        }

        $order = wc_get_order($order_id);

        $transactionId = Pay_Helper_Transaction::getPaidTransactionIdForOrderId($order_id);

        if ( ! $order || ! $transactionId) {
            return false;
        }

        try {
            $this->loginSDK();

            $result = \Paynl\Transaction::refund($transactionId, $amount, $reason);

            $order->add_order_note(sprintf(__('Refunded %s - Refund ID: %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN), $amount,
                $result->getRefundId()));

            return true;
        } catch (Exception $e) {
            return new WP_Error(1, $e->getMessage());
        }
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        if ($this->get_option('instructions')) {
            echo wpautop(wptexturize($this->get_option('instructions')));
        }
    }

}
