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

            $api_url = 'https://zero-parts.pay.nl/v1/session/start';

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

            $products = [];
            foreach (WC()->cart->get_cart() as $cart_item) {
                /** @var WC_Product $product */
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                $line_subtotal = $cart_item['line_subtotal'];
                $line_tax = $cart_item['line_subtotal_tax'];

                // Calculate item unit price in cents (excluding tax)
                $unit_price_cents = (int) round(($line_subtotal / $quantity) * 100);
                $line_total_cents = (int) round($line_subtotal * 100);

                // Calculate VAT percentage
                $vat_percentage = 0;
                if ($line_subtotal > 0 && $line_tax > 0) {
                    $vat_percentage = (int) round(($line_tax / $line_subtotal) * 100);
                }

                $products[] = [
                    "description" => $product->get_name(),
                    "quantity" => $quantity,
                    "unitPrice" => $unit_price_cents,
                    "total" => $line_total_cents,
                    "productId" => (string) $product->get_id(),
                    "vatPercentage" => $vat_percentage,
                ];
            }

            // we will be able to add this through an update later so needs to be changed
            $orderId = PPMFWC_Hooks_PayParts_Order::ppmfwc_reserveOrderId();

            $data = [
                "returnUrl" => $returnUrl,
                "exchangeUrl" => $exchangeUrl,
                "amount" => [
                    "value" => $total_amount_cents,
                    "currency" => get_woocommerce_currency(),
                ],
                "description" => 'Session Order', // Temporary label since order ID doesn't exist yet
                "reference" => strval($orderId),
                "expire" => date('c', strtotime('+1 hour')),
                "customer" => [
                    "locale" => str_replace('_', '-', get_user_locale()),
                    "firstName" => WC()->customer->get_billing_first_name(),
                    "lastName" => WC()->customer->get_billing_last_name(),
                    "email" => WC()->customer->get_billing_email(),
                    "phone" => WC()->customer->get_billing_phone(),
                    "address" => [
                        "street" => $street ?: $address_1,
                        "houseNumber" => $house_number,
                        "postalCode" => WC()->customer->get_billing_postcode(),
                        "city" => WC()->customer->get_billing_city(),
                        "country" => WC()->customer->get_billing_country(),
                    ],
                ],
                "order" => [
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
