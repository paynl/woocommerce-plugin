<?php

/**
 * PPMFWC_Gateway_Ideal
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */
class PPMFWC_Gateway_Instore extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_instore';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Pinnen (Instore)';
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_form_fields()
    {
        parent::loginSDK();

        parent::init_form_fields();
        $optionId = $this->getOptionId();
        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $terminals = $this->get_terminals();

            $options = array();
            $options['checkout'] = esc_html(__('Select in checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
            $options['checkout_save'] = esc_html(__('Select in checkout and save in cookie', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));

            $pickupLocationTerminalOptions = array();
            $pickupLocationTerminalOptions['select'] = esc_html(__('Select card terminal', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));

            if (!empty($terminals)) {
                foreach ($terminals as $terminal) {
                    $options[$terminal['id']] = $terminal['name'];
                    $pickupLocationTerminalOptions[$terminal['id']] = $terminal['name'];
                }
            }

            $pickupLocationTerminal = array();
            $pickupLocationTerminal['direct'] = esc_html(__('Direct checkout payment (default)', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
            $pickupLocationTerminal['pickup'] = esc_html(__('Payment takes place at the pickup location, only create a backorder', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));
            $pickupLocationTerminal['checkout'] = esc_html(__('Provide this choice in the checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN));

            $this->form_fields['paynl_instore_terminal'] = array(
                'title' => esc_html(__('Selected terminal', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => $options,
                'description' => esc_html(__('Select card terminal to start transaction', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));

            $this->form_fields['paynl_instore_pickup_location'] = array(
                'title' => esc_html(__('Payment location', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => $pickupLocationTerminal,
                'description' => esc_html(__('Setting for where the payment should take place: instant, or at pickup location. Or provide this choice in the checkout.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))); // phpcs:ignore

            $this->form_fields['paynl_instore_pickup_location_terminal'] = array(
                'title' => esc_html(__('Payment location terminal', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => $pickupLocationTerminalOptions,
                'description' => esc_html(__('Select card terminal for the payment location option', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));
        }
    }

    /**
     * @return array
     */
    private function get_terminals()
    {
        try {
            $cache_key = 'paynl_instore_terminals_' . $this->getServiceId();

            $terminals = get_transient($cache_key);
            if ($terminals === false) {
                $terminals = \Paynl\Instore::getAllTerminals()->getList();
                set_transient($cache_key, $terminals, HOUR_IN_SECONDS);
            }
            return $terminals;
        } catch (Exception $e) {
            set_transient($cache_key, array(), HOUR_IN_SECONDS);
            return array();
        }
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 1927;
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function payment_fields()
    {
        parent::payment_fields();

        $terminal = $this->get_option('paynl_instore_terminal');

        $pickupLocation = $this->get_option('paynl_instore_pickup_location');
        $pickupLocationTerminal = $this->get_option('paynl_instore_pickup_location_terminal');
        $shippingMethods = WC()->session->get('chosen_shipping_methods');

        $shippingIsPickupLocation = false;
        if (strpos($shippingMethods[0], 'local_pickup') === 0) {
            $shippingIsPickupLocation = true;
        }

        if ($pickupLocation == 'checkout' && $shippingIsPickupLocation) {
            ?>
            <fieldset id="pin_moment">
                <legend><?php echo esc_html(__('Payment', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></legend>
                <select name="pin_moment">
                    <option value="direct"><?php echo esc_html(__('Pay by card', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) ?></option>
                    <option value="pickup"><?php echo esc_html(__('Pay later, at pickup', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) ?></option>
                </select>
            </fieldset>
            <?php
        }

        if (
            substr($terminal, 0, 8) != 'checkout'
            || ($pickupLocation == 'checkout' && $shippingIsPickupLocation && $pickupLocationTerminal != 'select')
            || ($pickupLocation == 'pickup' && $shippingIsPickupLocation)
        ) {
            return;
        }
        if ($terminal == 'checkout_save') {
            if (!empty($_COOKIE['paynl_instore_terminal_id'])) {
                echo "<input type='hidden' name='terminal_id' value='" . esc_attr($_COOKIE['paynl_instore_terminal_id']) . "' />";
                return;
            }
        }
        $terminals = $this->get_terminals();

        if (!empty($terminals)) {
            ?>
            <fieldset>
                <legend><?php echo esc_html(__('Pay safely instore', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></legend>
                <select name="terminal_id">
                    <option value=""><?php echo esc_html(__('Select a card terminal...', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) ?></option>
                    <?php
                    foreach ($terminals as $terminal) {
                        echo '<option value="' . esc_attr($terminal['id']) . '">' . esc_html($terminal['name']) . '</option>';
                    }
                    ?>
                </select>
            </fieldset> 
            <?php
        }
    }

    /**
     * @param string $order_id
     * @return array|void
     */
    public function process_payment($order_id)
    {
        /** @var $wpdb wpdb The database */
        $order = new WC_Order($order_id);
        $result = null;
        $transactionFailed = false;

        $pickupLocationSetting = $this->get_option('paynl_instore_pickup_location');
        $pickupLocation = false;
        $pin_moment = PPMFWC_Helper_Data::getPostTextField('pin_moment', true);

        if ($pickupLocationSetting == 'pickup' || ($pickupLocationSetting == 'checkout' && $pin_moment == 'pickup')) {
            $pickupLocation = true;
        }

        if ($pickupLocation === true) {
            $order->add_order_note(sprintf(esc_html(__('PAY.: Payment at pick-up selected'))));
            return array('result' => 'success', 'redirect' => $order->get_checkout_order_received_url());
        }

        try {
            $result = $this->startTransaction($order, $pickupLocation);
            $paymentOptionId = $this->getOptionId();
        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not initiate instore payment. Error: ' . esc_html($e->getMessage()));
            $transactionFailed = true;
        }

        if (empty($result) || $transactionFailed) {
            wc_add_notice(esc_html(__('Could not initiate instore payment.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), 'error');
            return;
        }

        $order->add_order_note(sprintf(esc_html(__('Pay.: Transaction started: %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $result->getTransactionId()));

        PPMFWC_Helper_Transaction::newTransaction($result->getTransactionId(), $paymentOptionId, $order->get_total(), $order->get_id(), '');

        $terminal_setting = $this->get_option('paynl_instore_terminal');

        $pickup_location_terminal = $this->get_option('paynl_instore_pickup_location_terminal');
        $shippingMethods = WC()->session->get('chosen_shipping_methods');

        if ($pickupLocationSetting == 'checkout' && $pin_moment == 'direct' && strpos($shippingMethods[0], 'local_pickup') === 0 && !PPMFWC_Helper_Data::getPostTextField('terminal_id')) {
            $terminal = $pickup_location_terminal;
        } elseif (substr($terminal_setting, 0, 8) == 'checkout') {
            $terminal = PPMFWC_Helper_Data::getPostTextField('terminal_id', true);

            if (empty($_POST) && class_exists('WC_POS_Server')) {
                $data = WC_POS_Server::get_raw_data();
                $terminal = $data['payment_details']['terminal_id'];
            }
        } else {
            $terminal = $terminal_setting;
        }
        if ($terminal_setting == 'checkout_save') {
            setcookie('paynl_instore_terminal_id', $terminal, time() + (60 * 60 * 24 * 365));
        }

        # Send to pinterminal
        $result = \Paynl\Instore::payment(array('transactionId' => $result->getTransactionId(), 'terminalId' => $terminal));

        $hash = $result->getHash();
        ini_set('max_execution_time', 65);
        for ($i = 0; $i < 60; $i++) {
            $status = \Paynl\Instore::status(array('hash' => $hash));
            if ($status->getTransactionState() != 'init') {
                switch ($status->getTransactionState()) {
                    case 'approved':
                        $order->payment_complete();
                        return array('result' => 'success', 'redirect' => $order->get_checkout_order_received_url());
                        break;
                    case 'cancelled':
                    case 'expired':
                    case 'error':
                        return array('result' => 'failed');
                        break;
                }
            }

            sleep(1);
        }

        return array('result' => 'expired');
    }
}
