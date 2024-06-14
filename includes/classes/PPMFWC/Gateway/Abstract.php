<?php

/**
 * PPMFWC_Gateway_Abstract
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 */

abstract class PPMFWC_Gateway_Abstract extends WC_Payment_Gateway
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ON_HOLD = 'on-hold';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED = 'failed';

    const PAYMENT_METHOD_PINREFUND = 2351;

    /**
     * Payment Profile ID
     *
     * @var $optionId
     */
    public $optionId;

    /**
     * Abstract constructor.
     */
    public function __construct()
    {
        $this->id   = $this->getId();
        $this->icon = $this->getIcon();
        $this->optionId = $this->getOptionId();

        $this->has_fields         = true;
        $this->method_title       = esc_html('Pay. - ' . $this->getName());
        $this->method_description = esc_html(sprintf(__('Activate this module to accept %s transactions', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), $this->getName()));

        $this->supports = array('products', 'refunds');

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,'process_admin_options'));
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        $brandid = $this->get_option('brand_id');

        if (!empty($this->get_option('external_logo')) && wc_is_valid_url($this->get_option('external_logo'))) {
            return $this->get_option('external_logo');
        }
        if (!empty($brandid)) {
            return PPMFWC_PLUGIN_URL . 'assets/logos/' . $this->get_option('brand_id') . '.png';
        }
        return '';
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param boolean $update
     * @phpcs:disable Squiz.Commenting.FunctionComment.MissingReturn
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public function set_option_default($key, $value, $update = false)
    {
        if ((!$this->get_option($key)) || (strlen($this->get_option($key)) == 0) || ($update && $this->get_option($key) != $value)) {
            $this->update_option($key, $value);
        }
    }

    /**
     * Initialise Gateway Settings Form Fields.
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_form_fields()
    {
        $optionId = $this->getOptionId();

        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $this->form_fields = array(
                    'enabled' => array('title' => esc_html(__('Enable/Disable', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'    => 'checkbox',
                    'label'   => esc_html(sprintf(__('Enable Pay. %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), $this->getName())),
                    'default' => 'no',
                ),
                'title'        => array(
                    'title'       => esc_html(__('Title', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'        => 'text',
                    'description' => esc_html(__('The name of the payment method as shown in checkout.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'default'     => esc_html($this->getName()),
                    'desc_tip'    => true,
                ),
                'description'  => array(
                    'title'   => esc_html(__('Customer Message', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'    => 'textarea',
                    'default' => 'pay_init',
                ),
                'instructions' => array(
                    'title'       => esc_html(__('Instructions', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'        => 'textarea',
                    'description' => esc_html(__('Instructions that will be added to the thank you page.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'min_amount'   => array(
                    'title'       => esc_html(__('Minimum amount', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'        => 'price',
                    'description' => esc_html(__('Minimum order amount for this payment method, leave blank for no limit.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'default' => '',
                    'desc_tip'    => true,
                ),
                'max_amount'   => array(
                    'title'       => esc_html(__('Maximum amount', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'        => 'price',
                    'description' => esc_html(__('Maximum order amount for this payment method, leave blank for no limit.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );

            if ($this->slowConfirmation()) {
                $this->form_fields['initial_order_status'] = array(
                    'title'       => esc_html(__('Initial order status', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'        => 'select',
                    'options'     => array(
                        self::STATUS_ON_HOLD => wc_get_order_status_name(self::STATUS_ON_HOLD) . esc_html(' (' . __('default', PPMFWC_WOOCOMMERCE_TEXTDOMAIN) . ')'),
                        self::STATUS_PENDING => wc_get_order_status_name(self::STATUS_PENDING),
                    ),
                    'default'     => self::STATUS_ON_HOLD,
                    /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                    'description' => sprintf(
                        esc_html(
                            __(
                                'Some payment methods, like bank transfers, take longer to complete. By default we will set the initial order status to On Hold. This ensures the order is not cancelled when the setting %s is used.', // phpcs:ignore
                                PPMFWC_WOOCOMMERCE_TEXTDOMAIN
                            )
                        ),
                        wc_get_order_status_name(self::STATUS_ON_HOLD),
                        '<a href="' . admin_url('admin.php?page=wc-settings&tab=products&section=inventory') .
                        '" target="_blank">' . esc_html(__('Hold Stock (minutes)', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '</a>'
                    ),
                );
            }
            $showForCompanyDefault = 'both';
            if (in_array($optionId, array(1717, 2265))) {
                $showForCompanyDefault = 'private';
            }
            $this->form_fields['show_for_company'] = array(
                'title'       => esc_html(__('Customer type', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type'        => 'select',
                'options'     => array(
                    'both' => esc_html(__('Both', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'private' => esc_html(__('Private', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'business' => esc_html(__('Business', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                ),
                'default'     => $showForCompanyDefault,
                /* translators: Placeholder 1: Default order status, placeholder 2: Link to 'Hold Stock' setting */
                'description' => esc_html(__('Allow payment method to be used for business customers, private customers or both.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
            );

            $this->form_fields['country_limit'] = array(
                'title'       => esc_html(__('Country', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type'        => 'multiselect',
                'options'     => array_merge(array('all' => esc_html(__('Available for all countries', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))), WC()->countries->get_countries()),
                'default'     => 'all',
                'description' => sprintf(esc_html(__('Select one or more billing countries for which %s should be available.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $this->getName()),
                'desc_tip'    => esc_html(__('Select in which (billing) country this method should be available.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'class'       => 'countryLimit'
            );

            if ($this->showAuthorizeSetting()) {
                $this->form_fields['authorize_status'] = array(
                  'title'       => esc_html(__('Authorize status', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                  'type'        => 'select',
                  'options'     => array(
                    self::STATUS_PROCESSING => wc_get_order_status_name(self::STATUS_PROCESSING),
                    self::STATUS_PENDING => wc_get_order_status_name(self::STATUS_PENDING),
                    self::STATUS_COMPLETED => wc_get_order_status_name(self::STATUS_COMPLETED),
                    self::STATUS_ON_HOLD => wc_get_order_status_name(self::STATUS_ON_HOLD),
                    'parent_status' => esc_html(__('Use default (parent) setting ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                  ),
                  'default'     => self::STATUS_PROCESSING,
                  'description' => sprintf(
                      esc_html(
                          __(
                              'Select which status authorized transactions initially should have.',
                              PPMFWC_WOOCOMMERCE_TEXTDOMAIN
                          )
                      )
                  ),
                );
            }
            if ($this->showLogoSetting()) {
                $this->form_fields['external_logo'] = array(
                    'title' => esc_html(__('External logo', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type' => 'text',
                    'description' => esc_html(__('URL to your own logo as used in the checkout.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'default' => esc_html('')
                );
            }
            if ($this->showDOB()) {
                if ($this->showDOB()) {
                    $this->form_fields['ask_birthdate'] = array(
                        'title' => esc_html(__('Show date of birth field', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                        'type' => 'select',
                        'options' => array(
                            'no' => esc_html(__('No', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                            'yes_optional' => esc_html(__('Yes, as optional', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                            'yes_required' => esc_html(__('Yes, as required', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                        ),
                        'default' => 'yes',
                        'description' => esc_html(__('A date of birth is mandatory for most Buy Now Pay Later payment methods. Show this field in the checkout, to improve your customer\'s payment flow.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), // phpcs:ignore
                    );
                }
            }

            if ($this->showApplePayDetection()) {
                $this->form_fields['applepay_detection'] = array(
                  'title'       => esc_html(__('Apple Detection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                  'type'        => 'select',
                  'options'     => array(
                    'no'  => esc_html(__('No', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'yes' => esc_html(__('Yes', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                  ),
                  'default'     => 'no',
                  'description' => esc_html(__('Only show Apple Pay on Apple devices.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                );
            }

            if ($this->useInvoiceAddressAsShippingAddress()) {
                $this->form_fields['use_invoice_address'] = array(
                    'title' => esc_html(__('Use invoice address for shipping', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type' => 'checkbox',
                    'description' => esc_html(
                        __(
                            'Enable this option only when the shipping address is not forwarded to Pay. correctly when using Buy Now Pay Later payment methods.',
                            PPMFWC_WOOCOMMERCE_TEXTDOMAIN
                        )
                    ),
                    'default' => 'no'
                );
            }

            if ($this->alternativeReturnURL()) {
                $this->form_fields['alternative_return_url'] = array(
                    'title' => esc_html(__('Alternative return URL', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type' => 'text',
                    'placeholder' => 'Enter a valid return URL for pending payments.',
                    'description' => esc_html(__('Use this URL when the payment status is (still) pending after the order has been placed.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'default' => esc_html('')
                );
            }

            if (
                (!$this->get_option('brand_id')) || (strlen($this->get_option('brand_id')) == 0) ||
                (!$this->get_option('min_amount')) || (strlen($this->get_option('min_amount')) == 0) ||
                (!$this->get_option('max_amount')) || (strlen($this->get_option('max_amount')) == 0) ||
                $this->get_option('description') == 'pay_init'
            ) {
                try {
                    $paymentOptions = PPMFWC_Helper_Data::getPaymentOptionsList();
                    $payDefaults = (isset($paymentOptions[$optionId])) ? $paymentOptions[$optionId] : array();
                } catch (Exception $e) {
                    $payDefaults = array();
                }

                $this->set_option_default('brand_id', (isset($payDefaults['brand']['id'])) ? $payDefaults['brand']['id']  : '', true);
                $this->set_option_default('min_amount', (isset($payDefaults['min_amount'])) ? floatval($payDefaults['min_amount'] / 100)  : '', false);
                $this->set_option_default('max_amount', (isset($payDefaults['max_amount'])) ? floatval($payDefaults['max_amount'] / 100)  : '', false);

                $pubDesc = isset($payDefaults['brand']['public_description']) ? $payDefaults['brand']['public_description'] : sprintf(esc_html(__('Pay with %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $this->getName()); // phpcs:ignore
                $this->set_option_default('description', $pubDesc, true);
            }
        } else {
            $this->form_fields = array(
                'message' => array(
                    'title'       => esc_html(__('Disabled', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type'        => 'hidden',
                    'description' => esc_html(__('Payment method not activated, please activate on My.pay.nl first.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'label'       => sprintf(esc_html(__('Enable Pay. %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $this->getName()),
                )
            );
        }
    }

    /**
     * @return boolean Payment methods that are confirmed slowly (like banktransfer) should return true here
     */
    public static function slowConfirmation()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public static function showAuthorizeSetting()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public static function showLogoSetting()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public static function useInvoiceAddressAsShippingAddress()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public static function alternativeReturnURL()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public static function showDOB()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function askBirthdate()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function birthdateRequired()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function showVat()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function vatRequired()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function showCoc()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function cocRequired()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public static function showApplePayDetection()
    {
        return false;
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_settings()
    {
        add_action('woocommerce_thankyou_' . $this->getId(), array($this, 'thankyou_page'));

        parent::init_settings();
    }

    /**
     * @return string
     */
    public static function getTokenCode()
    {
        return get_option('paynl_tokencode');
    }

    /**
     * @return boolean
     */
    public function is_available()
    {
        if (! parent::is_available()) {
            return false;
        }

        # Only check for amounts when there's a cart
        if (WC()->cart) {
            $min_amount = $this->get_option('min_amount');
            $max_amount = $this->get_option('max_amount');

            $orderTotal = $this->get_order_total();
            $billingCountry = WC()->customer->get_billing_country();
            $arrCountriesAllowed = $this->get_option('country_limit');

            if (is_array($arrCountriesAllowed) && !in_array('all', $arrCountriesAllowed)) {
                if (!in_array(strtoupper($billingCountry), $arrCountriesAllowed)) {
                    return false;
                }
            }

            if (!empty($min_amount) && $orderTotal < (float)$min_amount) {
                return false;
            }
            if (!empty($max_amount) && $orderTotal > (float)$max_amount) {
                return false;
            }
            if (strlen(WC()->customer->get_billing_company()) > 0 && $this->get_option('show_for_company') == 'private') {
                return false;
            }
            if (strlen(WC()->customer->get_billing_company()) == 0 && $this->get_option('show_for_company') == 'business') {
                return false;
            }
            if ($this->getOptionId() == 2277 && empty($_COOKIE['applePayAvailable']) && !empty($this->get_option('applepay_detection')) && $this->get_option('applepay_detection') == 'yes') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getIssuers()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getSelectionType()
    {
        return 'none';
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function payment_fields()
    {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }

        if ($this->askBirthdate()) {
            $fieldName = $this->getId() . '_birthdate';
            echo '<fieldset><legend>' . esc_html(__('Date of birth: ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '</legend><input type="date" class="paydate" placeholder="dd-mm-yyyy" name="' . $fieldName . '" id="' . $fieldName . '"></fieldset> '; // phpcs:ignore
        }
    }

    /**
     * @param integer $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $paymentOption = $this->getOptionId();

        try {
            if (PPMFWC_Helper_Data::getPostTextField('updatecustomer')) {
                throw new PPMFWC_Exception_Notice('Updated customer data');
            }

            $dobShow = $this->get_option('ask_birthdate');
            $dobRequired = $this->get_option('birthdate_required');

            if ($dobShow == 'yes_required' || ($dobShow == 'yes' && $dobRequired == 'yes')) {
                $birthdate = PPMFWC_Helper_Data::getPostTextField($this->getId() . '_birthdate');
                if (empty($birthdate) || strlen(trim($birthdate)) != 10) {
                    $message = esc_html(__('Please enter your date of birth, this field is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
                    throw new PPMFWC_Exception_Notice($message);
                }
            }

            if ($this->showVat() && $this->vatRequired()) {
                $vat = PPMFWC_Helper_Data::getPostTextField('vat_number');
                if (empty($vat)) {
                    $message = esc_html(__('Please enter your VAT number, this field is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
                    throw new PPMFWC_Exception_Notice($message);
                }
            }

            if ($this->showCoc() && $this->cocRequired()) {
                $coc = PPMFWC_Helper_Data::getPostTextField('coc_number');
                if (empty($coc)) {
                    $message = esc_html(__('Please enter your COC number, this field is required.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
                    throw new PPMFWC_Exception_Notice($message);
                }
            }

            /** @var $wpdb wpdb The database */
            $order = new WC_Order($order_id);
            $payTransaction = $this->startTransaction($order);

            if (empty($payTransaction)) {
                # We want to know when no exception was thrown and startTransaction returned empty
                PPMFWC_Helper_Data::ppmfwc_payLogger('startTransaction returned false or empty', null, array('wc-order-id' => $order_id, 'paymentOption' => $paymentOption));
                throw new Exception('Could not start payment');
            }

            $order->add_order_note(sprintf(esc_html(__('Pay.: Transaction started: %s (%s)', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $payTransaction->getTransactionId(), $order->get_payment_method_title()));

            if ($this->slowConfirmation()) {
                $initial_status = $this->get_option('initial_order_status');
                $order->update_status($initial_status, sprintf(esc_html(__('Initial status set to %s ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), wc_get_order_status_name($initial_status)));
                if ($initial_status == self::STATUS_ON_HOLD) {
                    wc_reduce_stock_levels($order_id);
                }
            }

            PPMFWC_Helper_Transaction::newTransaction($payTransaction->getTransactionId(), $paymentOption, $order->get_total(), $order_id, '');

            # Return success redirect
            return array(
              'result'   => 'success',
              'redirect' => $payTransaction->getRedirectUrl()
            );
        } catch (PPMFWC_Exception_Notice $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Process payment start notice: ' . $e->getMessage());
            $message = $e->getMessage();
            wc_add_notice($message, 'error');
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not initiate payment. Error: ' . esc_html($e->getMessage()), null, array('wc-order-id' => $order_id, 'paymentOption' => $paymentOption));
            $message = 'Could not initiate payment. Please try again or use another payment method.';
            wc_add_notice(esc_html(__($message, PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), 'error');
        }

        return array(
          'result' => 'failed',
          'errorMessage' => $message
        );
    }

    /**
     * @param WC_Order $order
     * @return false|\Paynl\Result\Transaction\Start
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    protected function startTransaction(WC_Order $order)
    {
        $this->loginSDK(true);

        $returnUrl = add_query_arg(array('wc-api' => 'Wc_Pay_Gateway_Return'), home_url('/'));
        $exchangeUrl = add_query_arg('wc-api', 'Wc_Pay_Gateway_Exchange', home_url('/'));

        $strAlternativeExchangeUrl = self::getAlternativeExchangeUrl();
        if (!empty(trim($strAlternativeExchangeUrl))) {
            $exchangeUrl = $strAlternativeExchangeUrl;
        }

        $ipAddress = $order->get_customer_ip_address();
        if (empty($ipAddress)) {
            $ipAddress = Paynl\Helper::getIp();
        }

        $currency = $order->get_currency();
        $order_id = $order->get_id();
        $billing_country = $order->get_billing_country();

        try {
            $pay_paymentOptionId = $this->getOptionId();
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('No option-ID found: ' . $e->getMessage(), '');
            return false;
        }

        $prefix = empty(get_option('paynl_order_description_prefix')) ? '' : get_option('paynl_order_description_prefix');

        $startData = array(
            'amount'        => $order->get_total(),
            'returnUrl'     => $returnUrl,
            'exchangeUrl'   => $exchangeUrl,
            'orderNumber'   => $order->get_order_number(),
            'paymentMethod' => $pay_paymentOptionId,
            'currency'      => $currency,
            'description'   => str_replace('__', ' ', $prefix) . $order->get_order_number(),
            'extra1'        => apply_filters('paynl-extra1', $order->get_order_number(), $order),
            'extra2'        => apply_filters('paynl-extra2', $order->get_billing_email(), $order),
            'extra3'        => apply_filters('paynl-extra3', $order_id, $order),
            'ipaddress'     => $ipAddress,
            'object'        => PPMFWC_Helper_Data::getObject(),
        );


        $enduser = array(
            'initials'     => $order->get_shipping_first_name(),
            'lastName'     => substr($order->get_shipping_last_name(), 0, 32),
            'phoneNumber'  => $order->get_billing_phone(),
            'emailAddress' => $order->get_billing_email()
        );

        $enduser['birthDate'] = $this->getCustomBirthdate();
        $enduser['company'] = array(
            'name' => $order->get_billing_company(),
            'countryCode' => $billing_country
        );
        $enduser['company']['vatNumber'] = PPMFWC_Helper_Data::getPostTextField('vat_number', true);
        $enduser['company']['cocNumber'] = PPMFWC_Helper_Data::getPostTextField('coc_number', true);

        $startData['enduser'] = $enduser;

        # Retrieve order data
        $shippingAddress = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $billingAddress  = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();

        # Check order meta for postNL plugin house number
        if (!empty($order->get_meta('_shipping_house_number'))) {
            $shippingAddress = $order->get_shipping_address_1() . ' ' . $order->get_meta('_shipping_house_number') . $order->get_shipping_address_2();
        }
        if (!empty($order->get_meta('_billing_house_number'))) {
            $billingAddress = $order->get_billing_address_1() . ' ' . $order->get_meta('_billing_house_number') . $order->get_billing_address_2();
        }

        $aBillingAddress = \Paynl\Helper::splitAddress($billingAddress);
        $aShippingAddress = \Paynl\Helper::splitAddress($shippingAddress);
        $address = array(
            'streetName' => $aShippingAddress[0],
            'houseNumber' => $aShippingAddress[1],
            'zipCode' => $order->get_shipping_postcode(),
            'city' => $order->get_shipping_city(),
            'country' => $order->get_shipping_country()
        );

        if ($this->get_option('use_invoice_address') == 'yes') {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Use_invoice_address=yes. Updating shipping address');
            $address = array(
                'streetName'  => $aBillingAddress[0],
                'houseNumber' => $aBillingAddress[1],
                'zipCode'     => $order->get_billing_postcode(),
                'city'        => $order->get_billing_city(),
                'country'     => $billing_country
                );
        }

        $startData['address'] = $address;
        $startData['invoiceAddress'] = array(
            'initials'    => $order->get_billing_first_name(),
            'lastName'    => substr($order->get_billing_last_name(), 0, 32),
            'streetName'  => $aBillingAddress[0],
            'houseNumber' => $aBillingAddress[1],
            'zipCode'     => $order->get_billing_postcode(),
            'city'        => $order->get_billing_city(),
            'country'     => $billing_country,
        );

        $startData['products'] = $this->getProductLines($order);

        $optionSubId = PPMFWC_Helper_Data::getPostTextField('selectedissuer');
        if (empty($optionSubId)) {
            $optionSubId = PPMFWC_Helper_Data::getPostTextField('option_sub_id');
        }

        if (!empty($optionSubId)) {
            $startData['bank'] = $optionSubId;
        }
        if (PPMFWC_Helper_Data::isTestMode()) {
            $startData['testmode'] = true;
        }
        $language = get_option('paynl_language');

        if ($language == 'browser') {
            $language = PPMFWC_Helper_Data::getBrowserLanguage();
        }

        $startData['language'] = $language;

        $payTransaction = \Paynl\Transaction::start($startData);

        PPMFWC_Helper_Data::ppmfwc_payLogger('Start transaction', $payTransaction->getTransactionId(), array(
          'amount' => $startData['amount'],
          'method' => $this->get_method_title(),
          'wc-order-id' => $order_id));

        $order->update_meta_data('transactionId', $payTransaction->getTransactionId());
        $order->save();

        return $payTransaction;
    }


    /**
     * @return false|string|null
     */
    private function getCustomBirthdate()
    {
        $birthdate = PPMFWC_Helper_Data::getPostTextField($this->getId() . '_birthdate');
        return empty($birthdate) ? null : $birthdate;
    }

    /**
     * @return boolean
     */
    protected static function is_high_risk()
    {
        return get_option('paynl_high_risk') == 'yes';
    }

    /**
     * @return mixed|string|void
     */
    public static function getAlternativeExchangeUrl()
    {
        $strAltUrl = get_option('paynl_exchange_url');

        if (!empty($strAltUrl)) {
            return $strAltUrl;
        }

        return '';
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     * @param boolean $useMulticore
     * @return void
     */
    public static function loginSDK($useMulticore = false)
    {
        \Paynl\Config::setApiToken(self::getApiToken());
        \Paynl\Config::setServiceId(self::getServiceId());

        if ($useMulticore) {
            $failOver = trim(self::getApiBase());
            if (!empty($failOver) && strlen($failOver) > 12) {
                \Paynl\Config::setApiBase($failOver);
            }
        }

        $tokenCode = self::getTokenCode();
        if (!empty($tokenCode)) {
            \Paynl\Config::setTokenCode($tokenCode);
        }

        $verifyPeer = (get_option('paynl_verify_peer') == 'yes');
        \Paynl\Config::setVerifyPeer($verifyPeer);
    }

    /**
     * @return string
     */
    public static function getApiToken()
    {
        return get_option('paynl_apitoken');
    }

    /**
     * @return string
     */
    public static function getServiceId()
    {
        return get_option('paynl_serviceid');
    }

    /**
     * @return mixed
     */
    public static function getApiBase()
    {
        $gateway = get_option('paynl_failover_gateway');
        if ($gateway == 'custom') {
            $gateway = get_option('paynl_custom_failover_gateway');
        }
        return $gateway;
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function getProductLines(WC_Order $order)
    {
        $items = $order->get_items();

        $aProducts = array();

        if (is_array($items)) {
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
        }
        $shipping_total = $order->get_shipping_total();

        # Add shippingcosts information
        $shipping = floatval($shipping_total) + floatval($order->get_shipping_tax());
        if ($shipping != 0) {
            $aProducts[] = array(
                'id'    => 'shipping',
                'name'  => __('Shipping', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
                'price' => $shipping,
                'tax'   => $order->get_shipping_tax(),
                'qty'   => 1,
                'type'  => \Paynl\Transaction::PRODUCT_TYPE_SHIPPING
            );
        }

        # Add discount information
        $discount = $order->get_total_discount(false);
        if ($discount != 0) {
            $discountExcl = $order->get_total_discount(true);
            $discountTax  = $discount - $discountExcl;

            $aProducts[] = array(
                'id'    => 'discount',
                'name'  => __('Discount', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
                'price' => $discount * -1,
                'qty'   => 1,
                'type'  => \Paynl\Transaction::PRODUCT_TYPE_DISCOUNT,
                'tax'   => $discountTax * -1
            );
        }

        # Add fee information
        $fees = $order->get_fees();
        if (! empty($fees)) {
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
     * @param  integer $order_id
     * @param  float $amount
     * @param  string $reason
     *
     * @return  boolean|wp
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        PPMFWC_Helper_Data::ppmfwc_payLogger('process_refund', $order_id, array('orderid' => $order_id, 'amount' => $amount));

        if ($amount <= 0) {
            return new WP_Error('1', "Refund amount must be greater than €0.00");
        }

        $order = wc_get_order($order_id);
        $transactionLocalDB = PPMFWC_Helper_Transaction::getPaidTransactionIdForOrderId($order_id);

        if (empty($order) || empty($transactionLocalDB) || empty($transactionLocalDB['transaction_id'])) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Refund canceled, order empty', $order_id, array('orderid' => $order_id, 'amunt' => $amount, 'transactionId' => $transactionLocalDB['transaction_id'])); // phpcs:ignore
            return new WP_Error(1, esc_html(__('This transaction seems to have already been refunded or may not be captured yet. Please check the status on My.pay.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));
        }

        $transactionId = $transactionLocalDB['transaction_id'];

        try {
            $this->loginSDK();

            # First set local state to refund so that the exchange will not try to refund aswell.
            PPMFWC_Helper_Transaction::updateStatus($transactionId, PPMFWC_Gateways::STATUS_REFUND);

            $result = \Paynl\Transaction::refund($transactionId, $amount, mb_substr($reason, 0, 32));

            $result = (array) $result->getRequest();
            if (isset($result['result']) && empty($result['result'])) {
                throw new Exception($result['errorMessage']);
            }

            $order->add_order_note(sprintf(__('Pay.: Refunded: %s %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), $order->get_currency(), $amount));
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Refund exception:' . $e->getMessage(), $order_id, array('orderid' => $order_id, 'amunt' => $amount));

            $message = esc_html(__('Pay. could not refund the transaction.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
            $message = strpos($e->getMessage(), 'PAY-14') !== false ? esc_html(__('A (partial) refund has just been made on this transaction, please wait a moment, and try again.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) : $message; // phpcs:ignore

            PPMFWC_Helper_Transaction::updateStatus($transactionId, $transactionLocalDB['status']);
            return new WP_Error(1, $message);
        }

        return true;
    }

    /**
     * Output for the order received page.
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function thankyou_page()
    {
        if ($this->get_option('instructions')) {
            echo wpautop(wptexturize($this->get_option('instructions')));
        }
    }
}
