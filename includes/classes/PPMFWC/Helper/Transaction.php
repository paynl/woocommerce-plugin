<?php

class PPMFWC_Helper_Transaction
{

    public static function newTransaction($transactionId, $opionId, $amount, $orderId, $startData, $optionSubId = null)
    {
        global $wpdb;

        $table_name_transactions = $wpdb->prefix . "pay_transactions";

        $wpdb->insert(
            $table_name_transactions, array(
            'transaction_id' => $transactionId,
            'option_id' => $opionId,
            'option_sub_id' => $optionSubId,
            'amount' => $amount,
            'order_id' => $orderId,
            'status' => PPMFWC_Gateways::STATUS_PENDING,
            'start_data' => $startData,
        ), array(
                '%s', '%d', '%d', '%d', '%d', '%s', '%s'
            )
        );
        $insertId = $wpdb->insert_id;
        return $insertId;
    }

    public static function getPaidTransactionIdForOrderId($orderId)
    {
        global $wpdb;
        $table_name_transactions = $wpdb->prefix . "pay_transactions";
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT transaction_id FROM $table_name_transactions WHERE order_id = %s AND status = 'SUCCESS'", $orderId), ARRAY_A
        );
        if (!empty($result)) {
            return $result[0]['transaction_id'];
        } else {
            return false;
        }
    }

    private static function updateStatus($transactionId, $status)
    {
        global $wpdb;
        $table_name_transactions = $wpdb->prefix . "pay_transactions";
        $wpdb->query(
            $wpdb->prepare("
                        UPDATE $table_name_transactions SET status = %s WHERE transaction_id = %s
                    ", $status, $transactionId)
        );
    }

    /**
     * @param $transactionId
     * @return false|mixed
     */
    private static function getTransaction($transactionId)
    {
        global $wpdb;

        $table_name_transactions = $wpdb->prefix . "pay_transactions";
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name_transactions WHERE transaction_id = %s", $transactionId), ARRAY_A
        );

        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * @param $transactionId
     * @param null $status
     * @return mixed|string|void
     * @throws Pay_Exception
     * @throws PPMFWC_Exception_Notice
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public static function processTransaction($transactionId, $status = null)
    {
        global $woocommerce;

        # Retrieve local paymentstate
        $transaction = self::getTransaction($transactionId);

        if (empty($transaction)) {
            throw new Pay_Exception(__('Local transaction not found: ' . $transactionId, ''));
        }
        if (!isset($transaction['order_id'])) {
            throw new Pay_Exception(__('OrderId not set in local transaction: ' . $transactionId, ''));
        }

        $orderId = $transaction['order_id'];

        try {
          $order = new WC_Order($orderId);
        } catch (Exception $e) {
          # Could not retrieve order from WooCommerce (this is a notice so exchange wont repeat)
          throw new PPMFWC_Exception_Notice('Woocommerce could not find internal order ' . $orderId);
        }

        if ($status == $transaction['status'])
        {
            if ($status == PPMFWC_Gateways::STATUS_CANCELED) {
                return add_query_arg('paynl_status', PPMFWC_Gateways::STATUS_CANCELED, wc_get_checkout_url());
            }
            # We dont have to update
            return self::getOrderReturnUrl($order);
        }

        # Retieve PAY. transaction paymentstate
        PPMFWC_Gateway_Abstract::loginSDK();
        $transaction = \Paynl\Transaction::get($transactionId);

        $paidCurrencyAmount = $transaction->getPaidCurrencyAmount();

        $data = $transaction->getData();
        $apiStatus = PPMFWC_Gateways::getStatusFromStatusId($data['paymentDetails']['state']);

        if ($transaction->isAuthorized()) {
            $paidCurrencyAmount = $transaction->getCurrencyAmount();
        }

        self::updateStatus($transactionId, $apiStatus);

        if (WooCommerce::instance()->version < 3) {
            $wcOrderStatus = $order->status;
        } else {
            $wcOrderStatus = $order->get_status();
        }

        if ($wcOrderStatus == 'complete' || $wcOrderStatus == 'processing') {
            throw new PPMFWC_Exception_Notice('Order is already completed');
        }

        // aan de hand van apistatus gaan we hem updaten
        switch ($apiStatus) {
            case PPMFWC_Gateways::STATUS_SUCCESS:
                $woocommerce->cart->empty_cart();

                # Check the amount
                if ($order->get_total() != $paidCurrencyAmount && $order->get_total() != $transaction->getPaidAmount()) {
                  $order->update_status('on-hold', sprintf(__("Validation error: Paid amount does not match order amount. \npaidAmount: %s, \norderAmount: %s\n", PAYNL_WOOCOMMERCE_TEXTDOMAIN), $paidCurrencyAmount . ' / ' . $transaction->getPaidAmount(), $order->get_total()));
                } else {
                    $order->payment_complete($transactionId);
                }

                update_post_meta($orderId, 'CustomerName', esc_attr($transaction->getAccountHolderName()));
                update_post_meta($orderId, 'CustomerKey', esc_attr($transaction->getAccountNumber()));

                $order->add_order_note(sprintf(__('PAY.: Payment complete. customerkey: %s', PAYNL_WOOCOMMERCE_TEXTDOMAIN), $transaction->getAccountNumber()));

                $url = self::getOrderReturnUrl($order);
                break;
            case PPMFWC_Gateways::STATUS_CANCELED:
               

                if (WooCommerce::instance()->version >= 3) {
                    $method = $order->get_payment_method();

                    if (substr($method, 0, 11) != 'pay_gateway') {
                        throw new PPMFWC_Exception_Notice('Not cancelling, last used method is not a PAY. method');
                    }

                    if ($order->is_paid()) {
                        throw new PPMFWC_Exception_Notice('Not cancelling, order is already paid');
                    }

                    if (!$order->has_status('pending')) {
                        throw new PPMFWC_Exception_Notice('Cancel ignored, order is ' . $order->get_status());
                    }

                    $order->set_status('failed');
                    $order->save();
                    
                    $order->add_order_note(__('PAY.: Payment canceled', PAYNL_WOOCOMMERCE_TEXTDOMAIN));
                }

                $url = add_query_arg('paynl_status', PPMFWC_Gateways::STATUS_CANCELED, wc_get_checkout_url());

                break;
            case PPMFWC_Gateways::STATUS_VERIFY:
                $order->update_status('on-hold', __("Transaction needs to be verified", PAYNL_WOOCOMMERCE_TEXTDOMAIN));
                $url = self::getOrderReturnUrl($order);
                break;
            default:
                // Pending doen we niks mee
                $url = self::getOrderReturnUrl($order);
                break;
        }

        return $url;
    }

    public static function getOrderReturnUrl(WC_Order $order)
    {
        // return url returnen
        $return_url = $order->get_checkout_order_received_url();
        if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
            $return_url = str_replace('http:', 'https:', $return_url);
        }

        return apply_filters('woocommerce_get_return_url', $return_url, $order);

    }
}
