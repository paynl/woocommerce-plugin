<?php

/**
 * PPMFWC_Hooks_FastCheckout_TransactionCreate
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Hooks_FastCheckout_Start
{
    /**
     * Handles the Pay. fast checkout requests
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public static function ppmfwc_onFastCheckoutOrderCreate()
    {
        try {
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

            WC()->session->set('chosen_payment_method', 'pay_gateway_ideal');
            WC()->cart->calculate_totals();

            $shippingMethodArr = self::getShippingMethod($gateway);

            self::addProducts();
            $productArr = self::getProducts($shippingMethodArr);

            $amount = WC()->cart->get_taxes_total() + WC()->cart->get_shipping_total() + WC()->cart->subtotal_ex_tax - ($productArr['orderDiscount'] ?? 0);

            $data = [
                'amount' => $amount,
                'products' => $productArr['products'] ?? [],
                'currency' => get_woocommerce_currency(),
                'paymentMethod' => PPMFWC_Gateway_Ideal::getOptionId()
            ];

            $order = self::createFastCheckoutOrder($data);

            $transaction = new PPMFWC_Hooks_FastCheckout_TransactionCreate();
            $result = $transaction->create($data, $order);

            $transactionId = $result['transactionId'];
            $redirectUrl = $result['redirectURL'];

            $order->update_meta_data('transactionId', $transactionId);
            $order->save();

            PPMFWC_Helper_Transaction::newTransaction($transactionId, $data['paymentMethod'], $order->get_total(), $order->get_id(), '');

            wp_redirect($redirectUrl);
        } catch (\Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_redirect(wc_get_cart_url());
        }
    }

    /**
     * @param object $gateway
     * @return mixed
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function getShippingMethod($gateway)
    {
        $packages = WC()->shipping()->get_packages();
        $source = PPMFWC_Helper_Data::getRequestArg('source') ?? false;
        $shippingMethodId = null;
        $shippingMethod = null;

        if (!empty($packages)) {
            $package = $packages[0];
            $available_methods = $package['rates'];

            if ($source == 'cart') {
                $shippingMethodId = WC()->session->get('chosen_shipping_methods')[0];
            } else {
                WC()->session->set('chosen_shipping_methods', array($gateway->settings['ideal_fast_checkout_shipping_default']));
                WC()->cart->calculate_totals();
                $shippingMethodId = $gateway->settings['ideal_fast_checkout_shipping_default'];
            }
            $shippingMethod = self::checkShippingMethod($available_methods, $shippingMethodId);

            if (!$shippingMethod) {
                WC()->session->set('chosen_shipping_methods', array($gateway->settings['ideal_fast_checkout_shipping_backup']));
                WC()->cart->calculate_totals();
                $shippingMethodId = $gateway->settings['ideal_fast_checkout_shipping_backup'];
            }
            $shippingMethod = self::checkShippingMethod($available_methods, $shippingMethodId);

            if (!$shippingMethod) {
                throw new \Exception("Selected shipping method is not available.");
            }

            return ['shippingMethod' => $shippingMethod, 'shippingMethodId' => $shippingMethodId];
        }

        return false;
    }


    /**
     * @param array $available_methods
     * @param string $id
     * @return mixed
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function checkShippingMethod($available_methods, $id)
    {
        $shippingMethod = false;
        foreach ($available_methods as $method) {
            if ($id == $method->id) {
                $shippingMethod = $method;
            }
        }
        return $shippingMethod;
    }

    /**
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function addProducts()
    {
        $source = PPMFWC_Helper_Data::getRequestArg('source') ?? false;
        if ($source == 'product') {
            $product_id = PPMFWC_Helper_Data::getRequestArg('product_id') ?? 0;
            $quantity = PPMFWC_Helper_Data::getRequestArg('quantity') ?? 0;
            $variation_id = PPMFWC_Helper_Data::getRequestArg('variation_id') ?? 0;

            WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
            WC()->cart->calculate_totals();
        }
    }

    /**
     * @param array $shippingMethodArr
     * @return array
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function getProducts($shippingMethodArr)
    {
        $tax = new WC_Tax();
        $products = [];
        $orderDiscount = 0;

        foreach (WC()->cart->get_cart() as $cart_item) {
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

        if (!empty($shippingMethodArr['shippingMethod'])) {
            $shippingMethod = $shippingMethodArr['shippingMethod'];
            $shippingAmount = WC()->cart->get_shipping_total() + (float) array_shift($shippingMethod->get_taxes());
            if ($shippingAmount > 0) {
                $products[] = [
                    'id' => $shippingMethodArr['shippingMethodId'] ?? 0,
                    'name' => $shippingMethod->label,
                    'qty' => 1,
                    'amount' => $shippingAmount,
                    'amountExclTax' => WC()->cart->get_shipping_total(),
                    'taxPercentage' => round(\Paynl\Helper::calculateTaxPercentage($shippingAmount, array_shift($shippingMethod->get_taxes()))), // phpcs:ignore
                    'type' => 'SHIPPING',
                    'currency' => get_woocommerce_currency()
                ];
            }
        }

        return ['products' => $products, 'orderDiscount' => $orderDiscount];
    }

    /**
     * @param array $data
     * @return WC_Order
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @throws WC_Data_Exception
     */
    public static function createFastCheckoutOrder($data)
    {
        $order = new WC_Order();
        $order->set_created_via('fast checkout');
        $shippingProduct = null;

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

        if (!empty($shippingProduct)) {
            $shipping = new WC_Order_Item_Shipping();
            $shipping->set_method_title($shippingProduct['name'] ?? '');
            $shipping->set_method_id($shippingProduct['id'] ?? null);
            $shipping->set_total($shippingProduct['amountExclTax'] ?? 0);
            $order->add_item($shipping);
        }
        $order->calculate_totals();

        $order->set_payment_method(PPMFWC_Gateway_Ideal::getId());
        $order->set_payment_method_title('iDEAL Fast Checkout');

        $order->add_meta_data('_wc_order_attribution_source_type', 'utm');
        $order->add_meta_data('_wc_order_attribution_utm_source', 'iDEAL Fast Checkout');

        $order->add_order_note(esc_html(__('Pay.: Created iDEAL Fast Checkout order', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)));

        $order->save();

        return $order;
    }
}
