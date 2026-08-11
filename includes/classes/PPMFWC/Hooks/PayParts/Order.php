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

            if (!WC()->cart || WC()->cart->is_empty()) {
                throw new \Exception('Cart is empty or not found');
            }

            $order_id = WC()->session ? WC()->session->get('reserved_order_id') : false;

            if (!$order_id) {
                $order_id = self::ppmfwc_reserveOrderId();
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                throw new \Exception('Order not found');
            }

            foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
                $product_id = $values['product_id'];
                $quantity = $values['quantity'];
                $variation = isset($values['variation']) ? $values['variation'] : array();

                $item_id = $order->add_product(
                    wc_get_product($values['variation_id'] ? $values['variation_id'] : $product_id),
                    $quantity,
                    array('variation' => $variation)
                );
            }

            $order->set_address(WC()->customer->get_billing(), 'billing');
            $order->set_address(WC()->customer->get_shipping(), 'shipping');

            foreach (WC()->cart->get_coupons() as $code => $coupon) {
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

            $payment_method = WC()->session->get('chosen_payment_method');
            if ($payment_method) {
                $gateways = WC()->payment_gateways()->payment_gateways();
                if (isset($gateways[$payment_method])) {
                    $order->set_payment_method($gateways[$payment_method]);
                }
            }

            $order->calculate_totals();
            $order->set_status('pending');
            $order->save();

            WC()->session->__unset('reserved_order_id');

            $transactionId = isset($_POST['order_id']) ? $_POST['order_id'] : 0;
            PPMFWC_Helper_Transaction::newTransaction($transactionId, PPMFWC_Gateway_CreditCardsGrouped::getOptionId(), ($order->get_total() * 100), $order->get_id(), '');

            $returnarray = array(
                'success' => true,
                'order_id' => $order->get_id(),
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

    public static function ppmfwc_reserveOrderId()
    {

        if (!WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        $order_id = WC()->session->get('reserved_order_id');

        if ($order_id) {
            $order = wc_get_order($order_id);

            if ($order && in_array($order->get_status(), array('pending', 'checkout-draft', 'draft'))) {
                $order->calculate_totals();
                return $order_id;
            }
        }

        $order = wc_create_order(array(
            'customer_id' => get_current_user_id(),
            'status' => 'checkout-draft',
        ));

        if (is_wp_error($order)) {
            return false;
        }

        $new_order_id = $order->get_id();

        $order->set_address(WC()->customer->get_billing(), 'billing');
        $order->set_address(WC()->customer->get_shipping(), 'shipping');
        $order->calculate_totals();
        $order->save();

        WC()->session->set('reserved_order_id', $new_order_id);

        return $new_order_id;
    }

}
