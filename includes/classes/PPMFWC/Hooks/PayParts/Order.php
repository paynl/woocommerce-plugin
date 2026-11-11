<?php

/**
 * PPMFWC_Hooks_PayParts_Order
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Hooks_PayParts_Order
{
    /**
     * Handles the Pay. fast checkout requests
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public static function ppmfwc_onPayPartsOrderCreate()
    {

        try {
            $payPartsSession = WC()->session->get('payparts_session', []);
            $nonce = PPMFWC_Helper_Data::getPostTextField('nonce', true);
            if (empty($payPartsSession['id']) || !wp_verify_nonce($nonce, 'woocommerce-payparts-order')) {
                throw new \Exception('Invalid or expired PayParts session');
            }
            $sessionId = $payPartsSession['id'];

            if (!WC()->cart || WC()->cart->is_empty()) {
                throw new \Exception('Cart is empty or not found');
            }

            $order = wc_create_order(array('customer_id' => get_current_user_id(), 'status' => 'checkout-draft'));

            if (is_wp_error($order)) {
                return false;
            }

            $order->update_meta_data('_created_by', 'Pay. Parts');
            $order->add_meta_data('_wc_order_attribution_source_type', 'utm');
            $order->add_meta_data('_wc_order_attribution_utm_source', 'Pay. Parts');
            $order->add_order_note(esc_html(__('Pay.: Created Pay. Parts order', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));

            $order->set_address(WC()->customer->get_billing(), 'billing');
            $order->set_address(WC()->customer->get_shipping(), 'shipping');
            $order->calculate_totals();
            $order->save();

            foreach (WC()->cart->get_cart() as $values) {
                $product_id = $values['product_id'];
                $quantity = $values['quantity'];
                $variation = isset($values['variation']) ? $values['variation'] : array();
                $order->add_product(wc_get_product($values['variation_id'] ?: $product_id), $quantity, array('variation' => $variation));
            }

            foreach (array_keys(WC()->cart->get_coupons()) as $code) {
                $order->apply_coupon($code);
            }

            $chosen_shipping = WC()->session->get('chosen_shipping_methods');
            if (!empty($chosen_shipping)) {
                $shipping_item = new WC_Order_Item_Shipping();
                $shipping_item->set_method_title('Shipping');
                $shipping_item->set_method_id($chosen_shipping[0]);
                $shipping_item->set_total(WC()->cart->get_shipping_total());
                $order->add_item($shipping_item);
            }

            $payment_method = PPMFWC_Gateway_CreditCardsGrouped::getId();
            if ($payment_method) {
                $gateways = WC()->payment_gateways()->payment_gateways();
                if (isset($gateways[$payment_method])) {
                    $order->set_payment_method($gateways[$payment_method]);
                }
            }

            $order->calculate_totals();
            $order->set_status('pending');
            $order->save();

            PPMFWC_Helper_Transaction::newTransaction($sessionId, PPMFWC_Gateway_CreditCardsGrouped::getOptionId(), ($order->get_total() * 100), $order->get_id(), '');

            $payPartsOrders = WC()->session->get('payparts_orders', []);
            $payPartsOrders[$order->get_id()] = true;
            WC()->session->set('payparts_orders', $payPartsOrders);

            PPMFWC_Helper_Data::ppmfwc_payDebug('Created internal transaction for PayParts sessionid: ' . $sessionId);

            $returnarray = array(
                'success' => true,
                'order_id' => $order->get_id(),
                'session_id' => $sessionId,
            );
        } catch (\Exception $e) {
            $returnarray = array(
                'success' => false,
                'error' => $e->getMessage(),
            );
        }

        header('Content-Type: application/json;charset=UTF-8');
        die(json_encode($returnarray));
    }
}
