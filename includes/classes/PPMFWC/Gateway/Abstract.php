<?php

use PayNL\Sdk\Model\Request\OrderCreateRequest;


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
    public function getIcon(): string
    {
        if (!empty($this->get_option('external_logo')) && wc_is_valid_url($this->get_option('external_logo'))) {
            return $this->get_option('external_logo');
        }

        $paymentImage = $this->get_option('payment_image_cached');

        if (empty($paymentImage)) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('paymentImage empty: ' . print_r($paymentImage, true));
            return '';
        }

        if ($this->saveLogo($paymentImage)) {
            return PPMFWC_PLUGIN_URL . 'assets/cache' . $paymentImage;
        }

        return 'https://static.pay.nl' . $paymentImage;
    }

    /**
     * Save logo
     *
     * @param string $imagePath
     * @return bool
     */
    public function saveLogo(string $imagePath): bool
    {
        $imagePath = ltrim($imagePath, '/');
        $path = rtrim(PPMFWC_PLUGIN_PATH . 'assets/cache', '/');

        if (file_exists($path . '/' . $imagePath) && (time() - filemtime($path . '/' . $imagePath) < 86400)) {
            return true;
        }

        $imageUrl = 'https://static.pay.nl/' . $imagePath;
        return $this->downloadImage($imageUrl, $path, $imagePath);
    }

    /**
     * Download image from url
     *
     * @param string $url
     * @param string $basePath
     * @param string $image
     * @return bool
     */
    public function downloadImage(string $url, string $basePath, string $image): bool
    {
        $image = ltrim($image, '/');

        if (str_contains($image, '..')) {
            return false;
        }
        if (!preg_match('~^[a-zA-Z0-9/_\.\-]+$~', $image)) {
            return false;
        }

        // Alleen bekende image-extensies toestaan
        if (!preg_match('~\.(svg|png|jpe?g|webp)$~i', $image)) {
            return false;
        }

        // Download (404 e.d. geeft geen warning)
        $data = @file_get_contents($url);
        if ($data === false) {
            return false;
        }


        $fullPath = rtrim($basePath, '/') . '/' . $image;

        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $writeResult = false;
        if (is_writable(dirname($fullPath))) {
            $writeResult = file_put_contents($fullPath, $data) !== false;
        }

        return $writeResult;
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
     * @return array|mixed
     * @throws Exception
     */
    public function get_all_shipping_methods()
    {
        $cache_key = 'paynl_shipping_methods_wll_' . get_current_blog_id();

        $shippingMethods = get_transient($cache_key);

        if ($shippingMethods === false) {
            $zones = array();
            $data_store = WC_Data_Store::load('shipping-zone');
            $raw_zones = $data_store->get_zones();
            foreach ($raw_zones as $raw_zone) {
                $zones[] = new WC_Shipping_Zone($raw_zone);
            }
            $zones[] = new WC_Shipping_Zone(0);

            $shippingMethods = array();

            foreach ($zones as $zone) {
                $zoneName = $zone->get_zone_name();
                $zone_shipping_methods = $zone->get_shipping_methods();
                foreach ($zone_shipping_methods as $method) {
                    $shippingMethods[$method->get_rate_id()] = '[' . $zoneName . '] ' . $method->get_title();
                }
            }

            set_transient($cache_key, $shippingMethods, 5);
        }

        return $shippingMethods;
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
                'options'     => array_merge(array('all' => esc_html(__('Available for all countries', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))), !empty(WC()->countries) ? WC()->countries->get_countries() : array()), // phpcs:ignore
                'default'     => 'all',
                'description' => sprintf(esc_html(__('Select one or more billing countries for which %s should be available.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $this->getName()),
                'desc_tip'    => esc_html(__('Select in which (billing) country this method should be available.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'class'       => 'countryLimit'
            );

            $this->form_fields['shipping_limit'] = array(
                'title'       => esc_html(__('Shipping methods', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type'        => 'multiselect',
                'options'     => array_merge(array('all' => esc_html(__('Available for all shipping methods', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))), $this->get_all_shipping_methods()),
                'default'     => 'all',
                'description' => sprintf(esc_html(__('Select one or more shipping methods for which %s should be available.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $this->getName()),
                'desc_tip'    => esc_html(__('Select which shipping methods this method should be available for.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'class'       => 'shippingLimit'
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
                (!$this->get_option('payment_image_cached')) || (strlen($this->get_option('payment_image_cached')) == 0) ||
                (!$this->get_option('min_amount')) || (strlen($this->get_option('min_amount')) == 0) ||
                (!$this->get_option('max_amount')) || (strlen($this->get_option('max_amount')) == 0) ||
                $this->get_option('description') == 'pay_init'
            ) {
                try {
                    $paymentOptions = PPMFWC_Helper_Data::getPaymentOptionsList();

                    if (is_array($paymentOptions)) {
                        foreach ($paymentOptions as $option) {
                            if ($option->getId() == $optionId) {
                                $payDefaults = $option;
                                break;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $payDefaults = array();
                }

                if ((isset($payDefaults))) {
                    $minAmount = $payDefaults->getMinAmount();
                    $maxAmount = $payDefaults->getMaxAmount();
                    $icon = $payDefaults->getImage();
                }

                $this->set_option_default('payment_image_cached', (isset($icon)) ? $icon : '', true);
                $this->set_option_default('min_amount', (isset($minAmount)) ? floatval($minAmount / 100) : '');
                $this->set_option_default('max_amount', (isset($maxAmount)) ? floatval($maxAmount / 100) : '');

                $pubDesc = (isset($payDefaults->brand->public_description)) ? $payDefaults->brand->public_description : sprintf(esc_html(__('Pay with %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $this->getName()); // phpcs:ignore
                $this->set_option_default('description', $pubDesc);
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
            $shippingMethods = WC()->session->get('chosen_shipping_methods');
            $arrCountriesAllowed = $this->get_option('country_limit');
            $arrShippingAllowed = $this->get_option('shipping_limit');

            if (is_array($arrCountriesAllowed) && !in_array('all', $arrCountriesAllowed)) {
                if (!in_array(strtoupper($billingCountry), $arrCountriesAllowed)) {
                    return false;
                }
            }

            if (is_array($arrShippingAllowed) && !in_array('all', $arrShippingAllowed) && !empty($shippingMethods)) {
                foreach ($shippingMethods as $shippingMethod) {
                    if (!in_array($shippingMethod, $arrShippingAllowed)) {
                        return false;
                    }
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
            $payOrder = $this->startTransaction($order);

            if (empty($payOrder)) {
                # We want to know when no exception was thrown and startTransaction returned empty
                PPMFWC_Helper_Data::ppmfwc_payLogger('startTransaction returned false or empty', null, array('wc-order-id' => $order_id, 'paymentOption' => $paymentOption));
                throw new Exception('Could not start payment');
            }

            $order->add_order_note(sprintf(esc_html(__('Pay.: Transaction started: %s (%s)', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $payOrder->getOrderId(), $order->get_payment_method_title()));

            if ($this->slowConfirmation()) {
                $initial_status = $this->get_option('initial_order_status');
                $order->update_status($initial_status, sprintf(esc_html(__('Initial status set to %s ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), wc_get_order_status_name($initial_status)));
                if ($initial_status == self::STATUS_ON_HOLD) {
                    wc_reduce_stock_levels($order_id);
                }
            }

            PPMFWC_Helper_Transaction::newTransaction($payOrder->getOrderId(), $paymentOption, ($order->get_total() * 100), $order_id, '');

            # Return success redirect
            return array(
                'result' => 'success',
                'redirect' => $payOrder->getPaymentUrl()
            );

        } catch (PPMFWC_Exception_Notice $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Process payment start notice: ' . $e->getMessage());
            $message = $e->getMessage();
            wc_add_notice($message, 'error');

        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not initiate payment. Error ' . $e->getMessage(), null, array('wc_order_id' => $order_id, 'methodid' => $paymentOption), 'critical');
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
     * @param $pickupLocation
     * @return false
     * @throws Exception
     */
    protected function startTransaction(WC_Order $order, $pickupLocation = null)
    {

        $config = PPMFWC_Helper_Config::getPayConfig();
        $request = (new OrderCreateRequest())->setConfig($config);

        $request->setReturnurl(add_query_arg(array('wc-api' => 'Wc_Pay_Gateway_Return'), home_url('/')));
        $exchangeUrl = add_query_arg('wc-api', 'Wc_Pay_Gateway_Exchange', home_url('/'));

        $strAlternativeExchangeUrl = self::getAlternativeExchangeUrl();
        if (!empty(trim($strAlternativeExchangeUrl))) {
            $exchangeUrl = $strAlternativeExchangeUrl;
        }

        $request->setCurrency($order->get_currency());

        $order_id = $order->get_id();

        try { $request->setPaymentMethodId($this->getOptionId());
        } catch (Exception $e) { PPMFWC_Helper_Data::ppmfwc_payLogger('No option-ID found: ' . $e->getMessage(), ''); return false; }

        $prefix = empty(get_option('paynl_order_description_prefix')) ? '' : get_option('paynl_order_description_prefix');

        $request->setServiceId($config->getServiceId());
        $request->setTestmode(PPMFWC_Helper_Data::isTestMode());
        $request->setExchangeUrl($exchangeUrl);
        $request->setDescription(str_replace('__', ' ', $prefix) . $order->get_order_number());
        $request->setAmount($order->get_total());

        $expireTime = get_option('paynl_payment_expire_time');
        if (!empty($expireTime)) {
            $request->setExpire(date('c', time() + ($expireTime * 60)));
        }

        $raw = (string)($order->get_order_number() ?? '');
        $clean = preg_replace('/[^A-Za-z0-9]/u', '', $raw);
        $request->setReference($clean);

        if ($this->getOptionId() == PayNL\Sdk\Model\Method::PIN) {
            $request->setTerminal($this->getTerminal());
        }

        $extra1 = apply_filters('paynl-extra1', $order->get_order_number(), $order);
        $extra2 = apply_filters('paynl-extra2', $order->get_billing_email(), $order);
        $extra3 = apply_filters('paynl-extra3', $order_id, $order);

        $request->setStats((new PayNL\Sdk\Model\Stats)->setExtra1($extra1)->setExtra2($extra2)->setExtra3($extra3)
                ->setObject( PPMFWC_Helper_Data::getObject())
        );

        $request->setCustomer(PPMFWC_Helper_Config::getPayCustomer($order, $this->getId()));

        $requestOrderData = new PayNL\Sdk\Model\Order();
        $requestOrderData->setProducts(PPMFWC_Helper_Config::getPayProducts($order));
        $requestOrderData->setInvoiceAddress(PPMFWC_Helper_Config::getInvoiceAddress($order));
        $requestOrderData->setDeliveryAddress(PPMFWC_Helper_Config::getDeliveryAddress($order));
        $request->setOrder($requestOrderData);

        if ($pickupLocation === true) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Payment at pickup, order has been made but transaction skipped.');
            $order->save();
            return false;
        }

        $payOrder = $request->setConfig($config)->start();

        PPMFWC_Helper_Data::ppmfwc_payLogger('Start transaction', $payOrder->getOrderId(), array(
          'amount' => $order->get_total(),
          'method' => $this->get_method_title(),
          'wc-order-id' => $order_id));

        $order->update_meta_data('transactionId', $payOrder->getOrderId());
        $order->save();

        return $payOrder;
    }

    /**
     * @return false|string
     */
    private function getTerminal()
    {
        $terminalThCode = PPMFWC_Helper_Data::getPostTextField('terminal_id');
        $terminal_setting = $this->get_option('paynl_instore_terminal');

        if ($terminal_setting == 'checkout') {
            # do nothing, just use terminalThCode
        }
        if ($terminal_setting == 'checkout_save') {
            # Only save the tg-code in the cookie. This will then later be integrated in the checkout-view(hidden).
            setcookie('paynl_instore_terminal_id', $terminalThCode, time() + (60 * 60 * 24 * 365));

        } elseif (str_starts_with(strtoupper($terminal_setting), 'TH')) {
            # A designated TH-code is selected as preferred terminal, so always use this one.
            $terminalThCode = $terminal_setting;
        }


        return $terminalThCode ?? '';
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
     * @return string
     */
    public static function getServiceId()
    {
        return PPMFWC_Helper_Config::getServiceId();
    }

    /**
     * Process a refund if supported
     *
     * @param $order_id
     * @param $amount
     * @param $reason
     * @return true|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        if ($amount <= 0) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('process_refund: fund amount must be greater than 0.0', '', array('orderid' => $order_id, 'amount' => $amount));
            return new WP_Error('1', "Refund amount must be greater than €0.00");
        }

        $order = wc_get_order($order_id);
        $transactionLocalDB = PPMFWC_Helper_Transaction::getPaidTransactionIdForOrderId($order_id);
        $transactionId = $transactionLocalDB['transaction_id'] ?? '';

        PPMFWC_Helper_Data::ppmfwc_payLogger('process_refund', $transactionId, array('orderid' => $order_id, 'amount' => $amount));

        if (empty($order) || empty($transactionId)) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Refund canceled, order empty', $order_id, array('orderid' => $order_id, 'amunt' => $amount, 'transactionId' => $transactionId)); // phpcs:ignore
            return new WP_Error(1, esc_html(__('This transaction seems to have already been refunded or may not be captured yet. Please check the status on My.pay.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));
        }


        try {
            # First set local state to refund so that the exchange will not try to refund aswell.
            PPMFWC_Helper_Transaction::updateStatus($transactionId, PPMFWC_Gateways::STATUS_REFUND);

            $config = PPMFWC_Helper_Config::getPayConfig();

            $request = new PayNL\Sdk\Model\Request\TransactionRefundRequest($transactionId, $amount, $order->get_currency());

            $payOrder = $request->setConfig($config)->start();

            $order->add_order_note(sprintf(__('Pay.: Refunded: %s %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), $order->get_currency(), $amount));
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Refund exception:' . $e->getMessage(), $order_id, array('orderid' => $order_id, 'amunt' => $amount));

            $message = esc_html(__('Pay. could not refund the transaction.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
            $message = str_contains($e->getMessage(), 'PAY-14') ? esc_html(__('A (partial) refund has just been made on this transaction, please wait a moment, and try again.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) : $message; // phpcs:ignore

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

    /**
     * @return integer
     */
    public static function getImagePathName()
    {
        return '';
    }

}
