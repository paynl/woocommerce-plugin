<?php

use PayNL\Sdk\Config\Config;
use PayNL\Sdk\Model\Address;
use PayNL\Sdk\Model\Amount;
use PayNL\Sdk\Model\Customer;
use PayNL\Sdk\Model\Order;
use PayNL\Sdk\Model\Product;
use PayNL\Sdk\Model\Request\PayPartsSessionCreateRequest;

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

            $customer = (new Customer())
                ->setLocale(str_replace('_', '-', get_user_locale()))
                ->setFirstName(WC()->customer->get_billing_first_name())
                ->setLastName(WC()->customer->get_billing_last_name())
                ->setEmail(WC()->customer->get_billing_email())
                ->setPhone(WC()->customer->get_billing_phone());

            $deliveryAddress = (new Address())
                ->setFirstName(WC()->customer->get_shipping_first_name())
                ->setLastName(WC()->customer->get_shipping_last_name())
                ->setRegionCode(WC()->customer->get_shipping_state())
                ->setStreetName($street2 ?: $address_2)
                ->setStreetNumber($house_number2)
                ->setZipCode(WC()->customer->get_shipping_postcode())
                ->setCity(WC()->customer->get_shipping_city())
                ->setCountryCode(WC()->customer->get_shipping_country());

            $invoiceAddress = (new Address())
                ->setFirstName(WC()->customer->get_billing_first_name())
                ->setLastName(WC()->customer->get_billing_last_name())
                ->setRegionCode(WC()->customer->get_billing_state())
                ->setStreetName($street ?: $address_1)
                ->setStreetNumber($house_number)
                ->setZipCode(WC()->customer->get_billing_postcode())
                ->setCity(WC()->customer->get_billing_city())
                ->setCountryCode(WC()->customer->get_billing_country());

            $order = new Order();
            $order->setCountryCode(WC()->customer->get_billing_country());
            $order->setDeliveryAddress($deliveryAddress);
            $order->setInvoiceAddress($invoiceAddress);
            foreach (self::getProducts() as $row) {
                $product = (new Product())
                    ->setId($row['id'])
                    ->setDescription($row['description'])
                    ->setType($row['type'])
                    ->setQuantity($row['quantity'])
                    ->setPrice(new Amount($row['price']['value'], $row['price']['currency']))
                    ->setVatPercentage($row['vatPercentage']);
                $order->addProduct($product);
            }

            $config = new Config();
            $config->setUsername(get_option('paynl_serviceid', ''));
            $config->setPassword($gateway->settings['credit_cards_grouped_pay_parts_secret']);

            $request = (new PayPartsSessionCreateRequest())
                ->setAmount(new Amount($total_amount_cents, get_woocommerce_currency()))
                ->setReturnUrl($returnUrl)
                ->setExchangeUrl($exchangeUrl)
                ->setDescription('Credit Card Order')
                ->setReference('0')
                ->setExpire(date('c', strtotime('+1 hour')))
                ->setCustomer($customer)
                ->setOrder($order);
            $response = $request->setConfig($config)->start();
            $result = [
                'sessionId' => $response->getSessionId(),
                'sessionToken' => $response->getSessionToken(),
            ];

            PPMFWC_Helper_Data::ppmfwc_payDebug('Created  PayParts sessionid: ' . $result['sessionId']);

            WC()->session->set('payparts_session', ['id' => $result['sessionId']]);

            $responseData = array(
                'success' => true,
                'data' => $result,
                'nonce' => wp_create_nonce('woocommerce-payparts-order'),
                'startData' => $request->getBodyParameters(),
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

            $unitCents = (int) round($lineAmount * 100 / $qty);
            $row = [
                'id' => empty($product_id) ? 'p' . (string) $itemNumber : (string) $product_id,
                'description' => (string) $product->get_title(),
                'quantity' => $qty,
                "price" => array(
                    "value" => $unitCents,
                    "currency" => get_woocommerce_currency(),
                ),
                'vatPercentage' => (int) round($vatPercentage),
                'type' => 'ARTICLE'
            ];
            if ($unitCents > 0) {
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
                    'vatPercentage' => (int) round($vatPercentage),
                    'type' => 'SHIPPING'
                ];
            }
        }

        return $products;
    }
}
