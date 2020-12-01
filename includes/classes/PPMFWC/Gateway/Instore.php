<?php

class PPMFWC_Gateway_Instore extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_instore';
    }

    public static function getName()
    {
        return 'Pinnen';
    }

    public function init_form_fields()
    {
        parent::loginSDK();

        parent::init_form_fields();
        $optionId = $this->getOptionId();
        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $terminals = $this->get_terminals();

            $options = array();
            $options['checkout'] = __('Choose in checkout', PAYNL_WOOCOMMERCE_TEXTDOMAIN);
            $options['checkout_save'] = __('Choose in checkout and save in cookie', PAYNL_WOOCOMMERCE_TEXTDOMAIN);

            if (isset($terminals) && !empty($terminals)) {
                foreach ($terminals as $terminal) {
                    $options[$terminal['id']] = $terminal['name'];
                }
            }

            $this->form_fields['paynl_instore_terminal'] = array(
                'title' => __('Selected terminal', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
                'type' => 'select',
                'options' => $options,
                'description' => __('Select the terminal the payment should be sent to', PAYNL_WOOCOMMERCE_TEXTDOMAIN)
            );
        }
    }
    private function get_terminals(){
        try {
            $cache_key = 'paynl_instore_terminals_'.$this->getServiceId();

            $terminals = get_transient($cache_key);
            if($terminals === false){
                $terminals = \Paynl\Instore::getAllTerminals()->getList();
                set_transient($cache_key, $terminals, HOUR_IN_SECONDS);
            }
            return $terminals;
        } catch (Exception $e) {
            set_transient($cache_key, array(), HOUR_IN_SECONDS);
            return array();
        }
    }

    public static function getOptionId()
    {
        return 1729;
    }

    public function payment_fields()
    {
        parent::payment_fields();

        $terminal = $this->get_option('paynl_instore_terminal');

        if (substr($terminal, 0, 8) != 'checkout') {
            return;
        }
        if ($terminal == 'checkout_save') {
            if (isset($_COOKIE['paynl_instore_terminal_id']) && !empty($_COOKIE['paynl_instore_terminal_id'])) {
                echo "<input type='hidden' name='terminal_id' value='" . $_COOKIE['paynl_instore_terminal_id'] . "' />";
                return;
            }
        }
        $terminals = $this->get_terminals();

        if (!empty($terminals)) {
            ?>
            <p>
                <select name="terminal_id">
                    <option value=""><?php echo __('Choose the pin terminal', PAYNL_WOOCOMMERCE_TEXTDOMAIN) ?></option>
                    <?php
                    foreach ($terminals as $terminal) {
                        echo '<option value="' . $terminal['id'] . '">' . $terminal['name'] . '</option>';
                    }
                    ?>
                </select>
            </p>
            <?php

        }
    }

    public function process_payment($order_id)
    {
        /** @var $wpdb wpdb The database */
        $order = new WC_Order($order_id);

        try {
            $result = $this->startTransaction($order);

            $paymentOptionId = $this->getOptionId();
        } catch (Exception $e) {
            wc_add_notice(__('Payment error:', PAYNL_WOOCOMMERCE_TEXTDOMAIN), 'error');
            return;
        }

        $order->add_order_note(sprintf(__('PAY.: Transaction started: %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN), $result->getTransactionId()));

        PPMFWC_Helper_Transaction::newTransaction($result->getTransactionId(), $paymentOptionId, $order->get_total(), $order->get_id(), '');

        $terminal_setting = $this->get_option('paynl_instore_terminal');


        if (substr($terminal_setting, 0, 8) == 'checkout') {
            $terminal = isset($_POST['terminal_id']) ? sanitize_text_field($_POST['terminal_id']) : '';

            if (empty($_POST) && class_exists('WC_POS_Server')) {
                $data = WC_POS_Server::get_raw_data();
                $terminal = $data['payment_details']['terminal_id'];
            }
        } else {
            $terminal = $terminal_setting;
        }
        if($terminal_setting == 'checkout_save'){
            setcookie('paynl_instore_terminal_id', $terminal, time()+(60*60*24*365));
        }

        # Send to pinterminal
        $result = \Paynl\Instore::payment(array(
            'transactionId' => $result->getTransactionId(),
            'terminalId' => $terminal
        ));

        $hash = $result->getHash();
        ini_set('max_execution_time', 65);
        for ($i = 0; $i < 60; $i++) {
            $status = \Paynl\Instore::status(array('hash' => $hash));
            if ($status->getTransactionState() != 'init') {
                switch ($status->getTransactionState()) {
                    case 'approved':
                        $order->payment_complete();
                        return array('result' => 'success',
                            'redirect' => $order->get_checkout_order_received_url()
                        );
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

        return array(
            'result' => 'expired'
        );
    }

}