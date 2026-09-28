<?php

/**
 * PPMFWC_Hooks_PayParts_Session
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Hooks_PayParts_Session
{
    /**
     * Handles the Pay. fast checkout requests
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public static function ppmfwc_onPayPartsSessionCreate()
    {
        try {
            if (!WC()->cart || !WC()->customer) {
                throw new \Exception('Cart or customer not found');
            }

            $gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(11);

            if ($gateway->enabled == 'no') {
                throw new \Exception("Selected payment method is not available.");
            }

            $api_url = 'https://parts.pay.nl/v1/session/start';

            $returnUrl = add_query_arg(array('wc-api' => 'Wc_Pay_Gateway_Return'), home_url('/'));
            $exchangeUrl = add_query_arg('wc-api', 'Wc_Pay_Gateway_Exchange', home_url('/'));

            $strAlternativeExchangeUrl = PPMFWC_Gateway_Abstract::getAlternativeExchangeUrl();
            if (!empty(trim($strAlternativeExchangeUrl))) {
                $exchangeUrl = $strAlternativeExchangeUrl;
            }

            WC()->cart->calculate_totals();


            $total_amount_cents = (int) round(WC()->cart->get_total('edit') * 100);

            $address_1 = WC()->customer->get_billing_address_1();
            $street = preg_replace('/\s+[0-9]+.*$/', '', $address_1);
            $house_number = trim(str_replace($street, '', $address_1));

            $address_2 = WC()->customer->get_shipping_address_1();
            $street2 = preg_replace('/\s+[0-9]+.*$/', '', $address_2);
            $house_number2 = trim(str_replace($street2, '', $address_2));

            $products = self::getProducts();

            $data = [
                "returnUrl" => $returnUrl,
                "exchangeUrl" => $exchangeUrl,
                "amount" => [
                    "value" => $total_amount_cents,
                    "currency" => get_woocommerce_currency(),
                ],
                "description" => 'Credit Card Order',
                "reference" => '0',
                "expire" => date('c', strtotime('+1 hour')),
                "customer" => [
                    "locale" => str_replace('_', '-', get_user_locale()),
                    "firstName" => WC()->customer->get_billing_first_name(),
                    "lastName" => WC()->customer->get_billing_last_name(),
                    "email" => WC()->customer->get_billing_email(),
                    "phone" => WC()->customer->get_billing_phone(),
                ],
                "order" => [
                    "countryCode" => WC()->customer->get_billing_country(),
                    "deliveryAddress" => [
                        "firstName" => WC()->customer->get_shipping_first_name(),
                        "lastName" => WC()->customer->get_shipping_last_name(),
                        "region" => WC()->customer->get_shipping_state(),
                        "street" => $street2 ?: $address_2,
                        "streetNumber" => $house_number2,
                        "zipCode" => WC()->customer->get_shipping_postcode(),
                        "city" => WC()->customer->get_shipping_city(),
                        "country" => WC()->customer->get_shipping_country(),
                    ],
                    "invoiceAddress" => [
                        "firstName" => WC()->customer->get_billing_first_name(),
                        "lastName" => WC()->customer->get_billing_last_name(),
                        "region" => WC()->customer->get_billing_state(),
                        "street" => $street ?: $address_1,
                        "streetNumber" => $house_number,
                        "zipCode" => WC()->customer->get_billing_postcode(),
                        "city" => WC()->customer->get_billing_city(),
                        "country" => WC()->customer->get_billing_country(),
                    ],
                    "products" => $products,
                    "shippingCosts" => (int) round(WC()->cart->get_shipping_total() * 100),
                    "totalVat" => (int) round(WC()->cart->get_taxes_total() * 100),
                ],
            ];

            $ch = curl_init($api_url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(get_option('paynl_serviceid', '') . ':' . $gateway->settings['credit_cards_grouped_pay_parts_secret']),
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                die('cURL Error: ' . $error_msg);
            }

            curl_close($ch);

            $result = json_decode($response, true);

            $returnarray = array(
                'success' => true,
                'data' => $result,
                'startData' => $data,
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


    /**
     * @return array
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function getProducts()
    {
        $products = [];
        $orderDiscount = 0;

        foreach (WC()->cart->get_cart() as $itemNumber => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];

            $line_total = $cart_item['line_total']; // excl tax
            $line_tax = $cart_item['line_tax'];   // tax amount
            $qty = $cart_item['quantity'];

            $price_ex_tax = $line_total / $qty;
            $tax_per_unit = $line_tax / $qty;
            $price_inc_tax = $price_ex_tax + $tax_per_unit;

            $tax_percentage = $price_ex_tax > 0
                ? round(($tax_per_unit / $price_ex_tax) * 100, 2)
                : 0;

            $products[] = [
                'id' => empty($product_id) ? 'p' . (string) $itemNumber : (string) $product_id,
                'description' => (string) $product->get_title(),
                'quantity' => $cart_item['quantity'],
                "price" => array(
                    "value" => round($price_inc_tax * 100, 2),
                    "currency" => get_woocommerce_currency(),
                ),
                'vatPercentage' => $tax_percentage,
                'type' => 'ARTICLE'
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
                        $productAmount = $product['price']['value'];
                        $discount_total += round(($productAmount / 100 * $couponAmount * $product['quantity']), 2);
                    }
                }

                $products[] = [
                    'id' => (string) $coupon_code,
                    'description' => (string) $coupon_code,
                    'quantity' => 1,
                    "price" => array(
                        "value" => round($discount_total * 100, 2),
                        "currency" => get_woocommerce_currency(),
                    ),
                    'vatPercentage' => 0,
                    'type' => 'DISCOUNT',
                    'discountType' => $coupon->get_discount_type()
                ];

                $orderDiscount += $discount_total;
            }
        }

        if (WC()->cart->get_shipping_total() > 0) {

            $chosen_methods = WC()->session->get('chosen_shipping_methods');

            if (!empty($chosen_methods[0])) {
                $chosen_method_id = $chosen_methods[0];
                $packages = WC()->shipping()->get_packages();
                if (isset($packages[0]['rates'][$chosen_method_id])) {
                    $shippingMethod = $packages[0]['rates'][$chosen_method_id];
                }
            }

            $shippingAmount = WC()->cart->get_shipping_total() + (float) array_shift($shippingMethod->get_taxes());

            if ($shippingAmount > 0) {
                $shipTotal = WC()->cart->get_shipping_total();
                $shipTax = WC()->cart->get_shipping_tax();
                $vatPerc = round((100 * $shipTax) / $shipTotal);

                $products[] = [
                    'id' => 'shipping',
                    'description' => $shippingMethod->label,
                    'quantity' => 1,
                    "price" => array(
                        "value" => round($shippingAmount * 100, 2),
                        "currency" => get_woocommerce_currency(),
                    ),
                    'vatPercentage' => $vatPerc,
                    'type' => 'SHIPPING'
                ];
            }
        }

        return $products;
    }
}
