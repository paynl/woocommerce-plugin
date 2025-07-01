<?php

/**
 * PPMFWC_Helper_Transaction
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Helper_Transaction
{
    /**
     * @param mixed $payStatus
     * @return false|mixed|null
     */
    public static function getCustomWooComOrderStatus($payStatus)
    {
        $arrStatus['processing'] = get_option('paynl_status_paid');
        $arrStatus['cancel'] = get_option('paynl_status_cancel');
        $arrStatus['failed'] = get_option('paynl_status_failed');
        $arrStatus['authorised'] = get_option('paynl_status_authorized');
        $arrStatus['verify'] = get_option('paynl_status_verify');
        $arrStatus['chargeback'] = get_option('paynl_status_chargeback');

        $arrDefaultStatus['processing'] = 'processing';
        $arrDefaultStatus['cancel'] = 'cancelled';
        $arrDefaultStatus['failed'] = 'failed';
        $arrDefaultStatus['authorised'] = 'processing';
        $arrDefaultStatus['verify'] = 'on-hold';
        $arrDefaultStatus['chargeback'] = 'off';

        return $arrStatus[$payStatus] === false ? $arrDefaultStatus[$payStatus] : $arrStatus[$payStatus];
    }

    /**
     * @param string $transactionId
     * @param string $opionId
     * @param string $amount
     * @param string $orderId
     * @param string $startData
     * @param string|null $optionSubId
     * @return string
     */
    public static function newTransaction($transactionId, $opionId, $amount, $orderId, $startData, $optionSubId = null)
    {
        global $wpdb;

        $table_name_transactions = $wpdb->prefix . "pay_transactions";

        $wpdb->insert(
            $table_name_transactions,
            array(
                'transaction_id' => $transactionId,
                'option_id' => $opionId,
                'option_sub_id' => $optionSubId,
                'amount' => $amount,
                'order_id' => $orderId,
                'status' => PPMFWC_Gateways::STATUS_PENDING,
                'start_data' => $startData,
            ),
            array(
                '%s', '%d', '%d', '%d', '%d', '%s', '%s',
            )
        );
        $insertId = $wpdb->insert_id;
        return $insertId;
    }

    /**
     * @param string $orderId
     * @return string|boolean
     */
    public static function getPaidTransactionIdForOrderId($orderId)
    {
        global $wpdb;
        $table_name_transactions = $wpdb->prefix . "pay_transactions";
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name_transactions WHERE order_id = %s  AND status IN ('SUCCESS', 'REFUND', 'AUTHORIZE') ", $orderId),
            ARRAY_A
        );
        if (!empty($result)) {
            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * @param string $orderId
     * @return boolean
     */
    public static function getSuccessTransactionIdForOrderId($orderId)
    {
        global $wpdb;
        $table_name_transactions = $wpdb->prefix . "pay_transactions";
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name_transactions WHERE order_id = %s  AND status = 'SUCCESS'", $orderId),
            ARRAY_A
        );
        return !empty($result);
    }

    /**
     * @param string $transactionId
     * @param string $status
     * @return void
     */
    public static function updateStatus($transactionId, $status)
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
     * @param string $transactionId
     * @return false|mixed
     */
    public static function getTransaction($transactionId)
    {
        global $wpdb;

        $table_name_transactions = $wpdb->prefix . "pay_transactions";
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name_transactions WHERE transaction_id = %s", $transactionId),
            ARRAY_A
        );

        return isset($result[0]) ? $result[0] : false;
    }

    /**
     * @param $transactionId
     * @param $status
     * @param $methodid
     * @param array $params
     * @return mixed|string|null
     * @throws PPMFWC_Exception
     * @throws PPMFWC_Exception_Notice
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public static function processTransaction($transactionId, $status = null, $methodid = null, array $params = [])
    {
        # Retrieve local payment state
        $transactionLocalDB = self::getTransaction($transactionId);
        $localTransactionStatus = $transactionLocalDB['status'] ?? '';
        $orderId = $transactionLocalDB['order_id'] ?? null;

        if (empty($transactionLocalDB)) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Notice: Cant find local transaction: ' . $transactionId);
            $orderId = PPMFWC_Helper_Data::getRequestArg('extra1') ?? null;
        }

        try {
            $order = new WC_Order($orderId);
        } catch (Exception $e) {
            # Could not retrieve order from WooCommerce (this is a notice so exchange won't repeat)
            throw new PPMFWC_Exception_Notice('Woocommerce could not find internal order ' . $orderId);
        }

        PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction', $orderId, array($status, $localTransactionStatus));

        if ($status == $localTransactionStatus) {
            if (in_array($status, array(PPMFWC_Gateways::STATUS_SUCCESS, PPMFWC_Gateways::STATUS_AUTHORIZE))) {
                WC()->cart->empty_cart();
            }
            PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction - status allready up-to-date', $transactionId, array('status' => $status));
            # We don't have to update
            return $status;
        }

        # Retrieve Pay. transaction paymentstate
        PPMFWC_Gateway_Abstract::loginSDK(true);

        if (PPMFWC_Hooks_FastCheckout_Exchange::isPaymentBasedCheckout($params)) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('getting tgu-status');
            $transaction = self::getTguStatus($transactionId);
            if (!$transaction) {
                $order->add_order_note(esc_html(__('Pay.: Could not update address info. Please check your order at Pay.')));
                throw new PPMFWC_Exception_Notice('Could not retrieve tgu status for ' . $transactionId);
            }
        } else {
            PPMFWC_Helper_Data::ppmfwc_payLogger('getting gms-status');
            $transaction = \Paynl\Transaction::status($transactionId);
        }

        if (empty($transactionLocalDB)) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction', $orderId, array($status, $localTransactionStatus));
            PPMFWC_Helper_Transaction::newTransaction($transactionId, $methodid ?? 0, $order->get_total(), $orderId, 'start-data');
        }

        $transactionPaid = array($transaction->getCurrencyAmount(), $transaction->getPaidCurrencyAmount(), $transaction->getPaidAmount());

        $data = $transaction->getData();
        $internalPAYSatus = $data['paymentDetails']['state'];
        $payApiStatus = PPMFWC_Gateways::ppmfwc_getStatusFromStatusId($internalPAYSatus);

        if ($localTransactionStatus != $payApiStatus) {
            if ($localTransactionStatus === PPMFWC_Gateways::STATUS_SUCCESS && $payApiStatus === PPMFWC_Gateways::STATUS_CANCELED) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('Not changing order status to canceled, order is paid.');
            } else {
                self::updateStatus($transactionId, $payApiStatus);
            }
        }
        $wcOrderStatus = $order->get_status();

        $logArray['wc-order-id'] = $orderId;
        $logArray['wcOrderStatus'] = $wcOrderStatus;
        $logArray['PAY status'] = $payApiStatus;
        $logArray['PAY status id'] = $internalPAYSatus;

        PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction', $transactionId, $logArray);

        if (in_array($wcOrderStatus, array('complete', 'processing'))) {
            if ($payApiStatus == PPMFWC_Gateways::STATUS_REFUND) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction - Continue to process refund', $transactionId);
            } elseif ($payApiStatus == PPMFWC_Gateways::STATUS_CHARGEBACK) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction - Continue to process chargeback', $transactionId);
            } elseif ($status == PPMFWC_Gateways::STATUS_PINREFUND && $payApiStatus == PPMFWC_Gateways::STATUS_SUCCESS) {
                PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction - Continue to process pinrefund', $transactionId);
                $order->add_order_note(sprintf(esc_html(__('Pay.: Refunded: EUR %s via Retourpinnen', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), PPMFWC_Helper_Data::getRequestArg('amount')));
                self::processRefund($order, PPMFWC_Helper_Data::getRequestArg('amount'));
                return PPMFWC_Gateways::STATUS_REFUND;
            } else {
                PPMFWC_Helper_Data::ppmfwc_payLogger('processTransaction - Done', $transactionId);
                throw new PPMFWC_Exception_Notice('Order is already completed or processed');
            }
        }

        $newStatus = $payApiStatus;

        # Update status
        switch ($payApiStatus) {
            case PPMFWC_Gateways::STATUS_AUTHORIZE:
            case PPMFWC_Gateways::STATUS_SUCCESS:
                # Check the amount
                if (!in_array($order->get_total(), $transactionPaid)) {
                    $order->update_status('on-hold', sprintf(__("Validation error: Paid amount does not match order amount. \npaidAmount: %s, \norderAmount: %s\n", PPMFWC_WOOCOMMERCE_TEXTDOMAIN), implode(' / ', $transactionPaid), $order->get_total())); // phpcs:ignore
                } else {

                    if (PPMFWC_Hooks_FastCheckout_Exchange::isPaymentBasedCheckout($params)) {
                        if ($transactionId == $order->get_meta('transactionId')) {
                            PPMFWC_Helper_Data::ppmfwc_payLogger('adding AddressToOrder');
                            PPMFWC_Hooks_FastCheckout_Exchange::addAddressToOrder($transaction->getCheckoutData(), $order);
                        }
                    }

                    if ($payApiStatus == PPMFWC_Gateways::STATUS_AUTHORIZE) {
                        $method = $order->get_payment_method();
                        $methodSettings = get_option('woocommerce_' . $method . '_settings');
                        $auth_status = empty($methodSettings['authorize_status']) ? null : $methodSettings['authorize_status'];

                        if ($auth_status == 'parent_status') {
                            $auth_status = self::getCustomWooComOrderStatus('authorised');
                        }

                        try {
                            $order->set_transaction_id($transactionId);
                        } catch (Exception $e) {
                            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not set transaction_id.', $transactionId);
                        }

                        # Launch action for custom implementation
                        do_action('paynl_order_authorised', $order->get_id(), $auth_status);

                        # auth_status is null when paymentmethods don't have the authorize_status-setting, like iDEAL.
                        if ($auth_status !== null) {
                            if ($wcOrderStatus != $auth_status) {
                                $order->update_status($auth_status);
                                $newStatus = $auth_status . ' as configured in settings of ' . $method;
                                $order->add_order_note(sprintf(esc_html(__('Pay.: Authorised order set to ' . $auth_status . ' according to settings.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $transaction->getAccountNumber())); // phpcs:ignore
                            }

                            if ($auth_status == PPMFWC_Gateway_Abstract::STATUS_PROCESSING) {
                                # Treat as success. So continue, don't break, and set payment as complete...
                            } else {
                                # Save transaction and stop further actions
                                PPMFWC_Helper_Data::ppmfwc_payLogger('Setting transactionId, and break.', $transactionId);
                                $order->save();
                                break;
                            }
                        }
                    }

                    $initialMethod = $order->get_payment_method();
                    $usedMethod = PPMFWC_Gateways::ppmfwc_getGateWayById($methodid);

                    if (!empty($usedMethod) && $usedMethod->getId() != $initialMethod && get_option('paynl_payment_method_display') == 1) {
                        if (PPMFWC_Helper_Data::isOptionAvailable($usedMethod->getOptionId())) {
                            PPMFWC_Helper_Data::ppmfwc_payLogger('Changing payment method', $transactionId, array('usedMethod' => $usedMethod->getId(), 'method' => $initialMethod));
                            try {
                                $order->set_payment_method($usedMethod->getId());
                                $order->set_payment_method_title($usedMethod->getName());
                                $order->add_order_note(sprintf(esc_html(__('Pay.: Changed method to %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $usedMethod->getName()));
                            } catch (Exception $e) {
                                PPMFWC_Helper_Data::ppmfwc_payLogger('Could not update new method names: ' . $e->getMessage(), $transactionId);
                            }
                        } else {
                            PPMFWC_Helper_Data::ppmfwc_payLogger('Could not change method, option is not available: ' . $usedMethod->getOptionId(), $transactionId);
                        }
                    }

                    $customStatus = self::getCustomWooComOrderStatus($payApiStatus == PPMFWC_Gateways::STATUS_AUTHORIZE ? 'authorised' : 'processing');
                    if (!in_array($customStatus, array('processing', 'authorised'))) {
                        $order->add_order_note(sprintf(esc_html(__('Pay.: Order status set to custom-status: %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $customStatus));
                        $order->update_status($customStatus, 'According to Pay. plugin settings');
                        $order->save();
                    } else {
                        $order->payment_complete($transactionId);
                        if (!empty($transaction->getAccountNumber())) {
                            $order->add_order_note(sprintf(esc_html(__('Pay.: Payment complete (%s). customerkey: %s', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $payApiStatus, $transaction->getAccountNumber())); // phpcs:ignore
                        } else {
                            $order->add_order_note(sprintf(esc_html(__('Pay.: Payment complete (%s).', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), $payApiStatus));
                        }
                    }
                }

                update_post_meta($orderId, 'CustomerName', esc_attr($transaction->getAccountHolderName()));
                update_post_meta($orderId, 'CustomerKey', esc_attr($transaction->getAccountNumber()));

                break;

            case PPMFWC_Gateways::STATUS_DENIED:
                $order->add_order_note(esc_html(__('Pay.: Payment denied. ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));
                $order->update_status(self::getCustomWooComOrderStatus('failed'));
                break;

            case PPMFWC_Gateways::STATUS_REFUND:
                if (get_option('paynl_externalrefund') == "yes") {
                    PPMFWC_Helper_Data::ppmfwc_payLogger('Changing order state to `refunded`', $transactionId);
                    $order->set_status('refunded');
                    wc_increase_stock_levels($orderId);
                    $order->save();
                }
                break;

            case PPMFWC_Gateways::STATUS_CHARGEBACK:
                $status = self::getCustomWooComOrderStatus('chargeback');
                if ($status == 'off') {
                    throw new PPMFWC_Exception_Notice('Ignoring: chargeback');
                }
                PPMFWC_Helper_Data::ppmfwc_payLogger('Changing order state to `chargeback`', $transactionId);
                $order->set_status($status, 'Pay. Chargeback. Reason: "' . PPMFWC_Helper_Data::getRequestArg('external_reason_description') . '".');
                wc_increase_stock_levels($orderId);
                $order->save();
                break;

            case PPMFWC_Gateways::STATUS_CANCELED:
                $method = $order->get_payment_method();

                if (substr($method, 0, 11) != 'pay_gateway') {
                    throw new PPMFWC_Exception_Notice('Not cancelling, last used method is not a Pay. method');
                }
                if ($order->is_paid()) {
                    throw new PPMFWC_Exception_Notice('Not cancelling, order is already paid');
                }
                if (!$order->has_status('pending') && !$order->has_status('on-hold')) {
                    throw new PPMFWC_Exception_Notice('Cancel ignored, order is ' . $order->get_status());
                }
                $databaseStatusSuccess = self::getSuccessTransactionIdForOrderId($order->get_id());
                if ($databaseStatusSuccess) {
                    throw new PPMFWC_Exception_Notice('Not cancelling, order is paid.');
                }

                $order->set_status(self::getCustomWooComOrderStatus('cancel'));
                $order->save();

                $order->add_order_note(esc_html(__('Pay.: Payment cancelled', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));
                break;

            case PPMFWC_Gateways::STATUS_VERIFY:
                $order->set_status(self::getCustomWooComOrderStatus('verify'), 'Pay.: ' . esc_html(__("To be verified. ", PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));
                $order->save();
                break;
        }

        return $newStatus;
    }

    /**
     * @param $transactionId
     * @return false|PPMFWC_Model_PayOrder
     */
    public static function getTguStatus($transactionId)
    {
        try {
            $response = self::sendRequest('https://connect.pay.nl/v1/orders/' . $transactionId . '/status',
                get_option('paynl_tokencode'),
                get_option('paynl_apitoken'),
                null,
                'GET');

            return new PPMFWC_Model_PayOrder($response);

        } catch (Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Notice: get tgu status failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param $requestUrl
     * @param $payload
     * @param $tokenCode
     * @param $apiToken
     * @param string $method
     * @return array
     * @throws Exception
     */
    public static function sendRequest($requestUrl, $tokenCode, $apiToken, $payload = null, string $method = 'POST')
    {
        $authorization = base64_encode($tokenCode . ':' . $apiToken);

        $args = [
            'method'  => $method,
            'headers' => [
                'Authorization' => 'Basic ' . $authorization,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'timeout' => 30,
        ];

        if (!empty($payload)) {
            $args['body'] = $payload;
        }

        $response = wp_remote_request($requestUrl, $args);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true); // decode as array

        if (!empty($data['violations'])) {
            $field = $data['violations'][0]['propertyPath'] ?? ($data['violations'][0]['code'] ?? '');
            throw new \Exception($field . ': ' . ($data['violations'][0]['message'] ?? ''));
        }

        return $data;
    }


    /**
     * @param $order
     * @param $amount
     * @return void
     * @throws PPMFWC_Exception
     */
    public static function processRefund($order, $amount)
    {
        $orderId = $order->get_id();
        $refund = wc_create_refund(array(
            'amount' => (int) $amount,
            'reason' => null,
            'order_id' => $orderId,
            'line_items' => array(),
            'refund_payment' => false,
            'restock_items' => false,
        ));
        if ($refund instanceof WP_Error && $refund->has_errors()) {
            throw new PPMFWC_Exception($refund->get_error_message());
        }
        if (PPMFWC_Helper_Data::getRequestArg('amount') == $order->get_remaining_refund_amount()) {
            $order->set_status('refunded');
            wc_increase_stock_levels($orderId);
        }
        $order->save();
    }

    /**
     * @param string $transactionId
     * @return array|object|stdClass[]
     */
    public static function checkProcessing($transactionId)
    {
        global $wpdb;

        $table_name_processing = $wpdb->prefix . "pay_processing";
        try {
            $result = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name_processing WHERE transaction_id = %s AND created > date_sub('%s', interval 1 minute)",
                $transactionId,
                date('Y-m-d H:i:s')
            ), ARRAY_A);
            $processing = !empty($result[0]);

            if (!$processing) {
                $wpdb->replace($table_name_processing, array('transaction_id' => $transactionId, 'created' => date('Y-m-d H:i:s')), array('%s', '%s'));
            }
        } catch (\Exception $e) {
            $processing = false;
        }

        return $processing;
    }

    /**
     * @param string $transactionId
     * @return void
     */
    public static function removeProcessing($transactionId)
    {
        global $wpdb;
        $table_name_processing = $wpdb->prefix . "pay_processing";
        $wpdb->delete($table_name_processing, array('transaction_id' => $transactionId), array('%s'));
    }
}
