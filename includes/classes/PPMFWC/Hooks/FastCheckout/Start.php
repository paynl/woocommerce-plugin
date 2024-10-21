<?php

class PPMFWC_Hooks_FastCheckout_Start
{

    /**
     * Handles the Pay. fast checkout requests
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public static function ppmfwc_onFastCheckoutOrderCreate()
    {
        global $wpdb;

        try {
            $products = [];
            $tax = new WC_Tax();
            $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(10);

            if ($gateway->enabled == 'no') {
                throw new \Exception("Selected payment method is not available.");
            }

            if (
                (empty($gateway->settings['ideal_fast_checkout_on_minicart']) || $gateway->settings['ideal_fast_checkout_on_minicart'] == 0) &&
                (empty($gateway->settings['ideal_fast_checkout_on_cart']) || $gateway->settings['ideal_fast_checkout_on_cart'] == 0) &&
                (empty($gateway->settings['ideal_fast_checkout_on_product']) || $gateway->settings['ideal_fast_checkout_on_product'] == 0)
            ) {
                throw new \Exception("Fast checkout is not available.");
            }

            $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : false;

            WC()->session->set('chosen_payment_method', 'pay_gateway_ideal');
            WC()->cart->calculate_totals();

            if ($source == 'product') {
                $product_id = isset($_GET['product_id']) ? $_GET['product_id'] : 0;
                $quantity = isset($_GET['quantity']) ? $_GET['quantity'] : 0;
                $variation_id = isset($_GET['variation_id']) ? $_GET['variation_id'] : 0;

                WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
                WC()->cart->calculate_totals();
            }

            $packages = WC()->shipping()->get_packages();
            if (!empty($packages)) {
                $package = $packages[0];
                $available_methods = $package['rates'];

                if ($source == 'cart') {
                    $shippingMethodId = WC()->session->get('chosen_shipping_methods')[0];
                    $shippingMethod = self::getShippingMethod($available_methods, $shippingMethodId);
                } else {
                    WC()->session->set('chosen_shipping_methods', array($gateway->settings['ideal_fast_checkout_shipping_default']));
                    WC()->cart->calculate_totals();
                    $shippingMethodId = $gateway->settings['ideal_fast_checkout_shipping_default'];
                    $shippingMethod = self::getShippingMethod($available_methods, $shippingMethodId);
                }

                if (!$shippingMethod) {
                    WC()->session->set('chosen_shipping_methods', array($gateway->settings['ideal_fast_checkout_shipping_backup']));
                    WC()->cart->calculate_totals();
                    $shippingMethodId = $gateway->settings['ideal_fast_checkout_shipping_backup'];
                    $shippingMethod = self::getShippingMethod($available_methods, $shippingMethodId);
                }

                if (!$shippingMethod) {
                    throw new \Exception("Selected shipping method is not available.");
                }
            }

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $product_id = $cart_item['product_id'];
                $variation_id = $cart_item['variation_id'];
                $name = $product->get_title();
                $quantity = $cart_item['quantity'];
                $price = $product->get_price();
                $tax_percentage = 0;
                $taxes = $tax->get_rates($product->get_tax_class());
                if (!empty($taxes)) {
                    $rates = array_shift($taxes);
                    $tax_percentage = round(array_shift($rates));
                }
                $products[] = [
                    'id' => $product_id,
                    'variation_id' => $variation_id,
                    'name' => $name,
                    'qty' => $quantity,
                    'amount' => $price,
                    'taxPercentage' => $tax_percentage,
                    'type' => 'ARTICLE',
                    'currency' => get_woocommerce_currency()
                ];
            }

            $orderDiscount = 0;

            if (!empty(WC()->cart->applied_coupons)) {
                foreach (WC()->cart->applied_coupons as $coupon_code) {
                    $coupon = new WC_Coupon($coupon_code);
                    $discount_total = $coupon->get_amount();

                    if ($coupon->get_discount_type() == 'percent') {
                        $discount_total = 0;
                        foreach ($products as $product) {
                            $couponAmount = $coupon->get_amount();
                            $productAmount = $product['amount'];
                            $discount_total += round(($productAmount / 100 * $couponAmount * $product['qty']), 2);
                        }
                    }

                    $products[] = [
                        'id' => $coupon_code,
                        'name' => $coupon_code,
                        'qty' => 1,
                        'amount' => $discount_total,
                        'taxPercentage' => 0,
                        'type' => 'DISCOUNT',
                        'discountType' => $coupon->get_discount_type(),
                        'currency' => get_woocommerce_currency()
                    ];

                    $orderDiscount += $discount_total;
                }
            }

            if ($shippingMethod) {
                $shippingAmount = WC()->cart->get_shipping_total() + (float) array_shift($shippingMethod->get_taxes());
                if ($shippingAmount > 0) {
                    $products[] = [
                        'id' => $shippingMethodId,
                        'name' => $shippingMethod->label,
                        'qty' => 1,
                        'amount' => WC()->cart->get_shipping_total() + (float) array_shift($shippingMethod->get_taxes()),
                        'amountExclTax' => WC()->cart->get_shipping_total(),
                        'taxPercentage' => round(\Paynl\Helper::calculateTaxPercentage(WC()->cart->get_shipping_total() + (float) array_shift($shippingMethod->get_taxes()), array_shift($shippingMethod->get_taxes()))), // phpcs:ignore
                        'type' => 'SHIPPING',
                        'currency' => get_woocommerce_currency()
                    ];
                }
            }

            $amount = WC()->cart->get_taxes_total() + WC()->cart->get_shipping_total() + WC()->cart->subtotal_ex_tax - $orderDiscount;

            $data = [
                'amount' => $amount,
                'products' => $products,
                'currency' => get_woocommerce_currency(),
                'paymentMethod' => PPMFWC_Gateway_Ideal::getOptionId()
            ];

            $order = self::createFastCheckoutOrder($data);

            $transaction = new PPMFWC_Hooks_FastCheckout_TransactionCreate();
            $result = $transaction->create($data, $order);

            $transactionId = $result['transactionId'];
            $redirectUrl = $result['redirectURL'];

            $table_name_fast_checkout = $wpdb->prefix . "pay_fast_checkout";
            $wpdb->replace($table_name_fast_checkout, array('transaction_id' => $transactionId, 'products' => json_encode($products), 'created' => date('Y-m-d H:i:s')), array('%s', '%s', '%s'));

            PPMFWC_Helper_Transaction::newTransaction($transactionId, $data['paymentMethod'], $order->get_total(), $order->get_id(), '');

            wp_redirect($redirectUrl);
        } catch (\Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_redirect(wc_get_cart_url());
        }
    }

    /**
     * @param array $data
     * @return WC_Order
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function createFastCheckoutOrder($data)
    {
        $order = new WC_Order();
        $order->set_created_via('fast checkout');
        $shippingProduct = null;

        $coupons = array();

        foreach ($data['products'] as $product) {
            if ($product['type'] == 'SHIPPING') {
                $shippingProduct = $product;
                continue;
            }
            if ($product['type'] == 'DISCOUNT') {
                $coupon = new WC_Coupon($product['id']);
                $order->apply_coupon($coupon);
                continue;
            }
            if (!empty($product['variation_id']) && $product['variation_id'] > 0) {
                $product_variation = new WC_Product_Variation($product['variation_id']);
                $args = array();
                foreach ($product_variation->get_variation_attributes() as $attribute => $attribute_value) {
                    $args['variation'][$attribute] = $attribute_value;
                }
                $order->add_product($product_variation, $product['qty'], $args);
            } else {
                $order->add_product(
                    wc_get_product($product['id']),
                    $product['qty']
                );
            }
        }

        $shipping = new WC_Order_Item_Shipping();
        $shipping->set_method_title($shippingProduct['name']);
        $shipping->set_method_id($shippingProduct['id']);
        $shipping->set_total($shippingProduct['amountExclTax']);

        $order->add_item($shipping);
        $order->calculate_totals();

        $order->set_payment_method(PPMFWC_Gateway_Ideal::getId());
        $order->set_payment_method_title('iDEAL Fast Checkout');

        $order->add_meta_data('_wc_order_attribution_source_type', 'utm');
        $order->add_meta_data('_wc_order_attribution_utm_source', 'iDEAL Fast Checkout');

        $order->add_order_note(sprintf(esc_html(__('Pay.: Created iDEAL Fast Checkout order', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))));

        $order->save();

        return $order;
    }

    /**
     * @param array $available_methods
     * @param string $id
     * @return mixed
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function getShippingMethod($available_methods, $id)
    {
        $shippingMethod = false;
        foreach ($available_methods as $key => $method) {
            if ($id == $method->id) {
                $shippingMethod = $method;
            }
        }
        return $shippingMethod;
    }

}
