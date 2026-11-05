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
     *
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
            if (empty($result['sessionId']) || empty($result['sessionToken'])) {
                throw new \Exception('Could not start PayParts session');
            }

            PPMFWC_Helper_Data::ppmfwc_payDebug('Created  PayParts sessionid: ' . $result['sessionId']);

            WC()->session->set('payparts_session', ['id' => $result['sessionId']]);

            $responseData = array(
                'success' => true,
                'data' => $result,
                'nonce' => wp_create_nonce('woocommerce-payparts-order'),
                'startData' => $data,
            );
        } catch (\Exception $e) {
            PPMFWC_Helper_Data::ppmfwc_payLogger('Exception onPayPartsSessionCreate failed: ' . $e->getMessage());

            $responseData = array(
                'success' => false,
                'error' => $e->getMessage(),
            );
        }

        header('Content-Type: application/json;charset=UTF-8');
        die(json_encode($responseData));
    }


    /**
     * @return array
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function getProducts()
    {
        $products = [];

        foreach (WC()->cart->get_cart() as $itemNumber => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $qty = (int) $cart_item['quantity'];
            if ($qty <= 0) {
                continue;
            }

            $lineAmount = (float) $cart_item['line_total'] + (float) $cart_item['line_tax'];
            $taxes = $cart_item['line_tax_data']['total'] ?? [];
            $vatPercentage = count($taxes) === 1
                ? WC_Tax::get_rate_percent_value(array_key_first($taxes))
                : paynl_calc_vat_percentage($lineAmount, (float) $cart_item['line_tax']);

            $lineCents = (int) round($lineAmount * 100);
            $unitCents = intdiv($lineCents, $qty);
            $remainder = $lineCents % $qty;
            $row = [
                'id' => empty($product_id) ? 'p' . (string) $itemNumber : (string) $product_id,
                'description' => (string) $product->get_title(),
                'quantity' => $qty - $remainder,
                "price" => array(
                    "value" => $unitCents,
                    "currency" => get_woocommerce_currency(),
                ),
                'vatPercentage' => $vatPercentage,
                'type' => 'ARTICLE'
            ];
            if ($unitCents > 0) {
                $products[] = $row;
            }

            if ($remainder > 0) {
                $row['id'] .= '-rounding';
                $row['quantity'] = $remainder;
                $row['price']['value'] = $unitCents + 1;
                $products[] = $row;
            }
        }

        if (WC()->cart->get_shipping_total() > 0) {
            $shippingTax = (float) WC()->cart->get_shipping_tax();
            $shippingAmount = (float) WC()->cart->get_shipping_total() + $shippingTax;
            $taxes = WC()->cart->get_shipping_taxes();

            if ($shippingAmount > 0) {
                $vatPercentage = count($taxes) === 1
                    ? WC_Tax::get_rate_percent_value(array_key_first($taxes))
                    : paynl_calc_vat_percentage($shippingAmount, $shippingTax);

                $products[] = [
                    'id' => 'shipping',
                    'description' => __('Shipping', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
                    'quantity' => 1,
                    "price" => array(
                        "value" => (int) round($shippingAmount * 100),
                        "currency" => get_woocommerce_currency(),
                    ),
                    'vatPercentage' => $vatPercentage,
                    'type' => 'SHIPPING'
                ];
            }
        }

        return $products;
    }
}
