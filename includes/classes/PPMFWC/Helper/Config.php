<?php

use PayNL\Sdk\Config\Config as PayConfig;

class PPMFWC_Helper_Config
{

    /**
     * @return PayConfig
     */
    public static function getPayConfig(): PayConfig
    {
        $config = new PayConfig;
        $config->setCaching(false);
        $config->setUsername(get_option('paynl_tokencode'));
        $config->setPassword(get_option('paynl_apitoken'));
        $config->setServiceId(get_option('paynl_serviceid'));

        $failOver = get_option('paynl_failover_gateway');
        if ($failOver == 'custom') {
            $failOver = get_option('paynl_custom_failover_gateway');
        }

        if (!empty($failOver) && in_array($failOver, ['https://connect.pay.nl', 'https://connect.achterelkebetaling.nl', 'https://connect.payments.nl'])) {
            $config->setCore($failOver);
        }

        return $config;
    }

    /**
     * @param  $order
     * @return \PayNL\Sdk\Model\Address
     */
    public static function getInvoiceAddress($order)
    {
        $billingAddress = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
        $aBillingAddress = paynl_split_address($billingAddress);

        $_billing_house_number = $order->get_meta('_billing_house_number');
        if (empty($aBillingAddress[1]) && !empty($_billing_house_number)) {
            $billingAddress = $order->get_billing_address_1() . ' ' . $_billing_house_number . $order->get_billing_address_2();
            $aBillingAddress = paynl_split_address($billingAddress);
        }

        $invAddress = new \PayNL\Sdk\Model\Address();
        $invAddress->setStreetName($aBillingAddress['street']);
        $invAddress->setStreetNumber($aBillingAddress['number']);
        $invAddress->setZipCode($order->get_billing_postcode());
        $invAddress->setCity($order->get_billing_city());
        $invAddress->setCountryCode(strtoupper($order->get_billing_country()));
        return $invAddress;
    }

    /**
     * @param $order
     * @return \PayNL\Sdk\Model\Address
     */
    public static function getDeliveryAddress($order): \PayNL\Sdk\Model\Address
    {
        $shippingAddress = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $aShippingAddress = paynl_split_address($shippingAddress);

        $_shipping_house_number = $order->get_meta('_shipping_house_number');
        if (empty($aShippingAddress[1]) && !empty($_shipping_house_number)) {
            $shippingAddress = $order->get_shipping_address_1() . ' ' . $_shipping_house_number . $order->get_shipping_address_2();
            $aShippingAddress = paynl_split_address($shippingAddress);
        }

        $invAddress = new \PayNL\Sdk\Model\Address();
        $invAddress->setStreetName($aShippingAddress['street']);
        $invAddress->setStreetNumber($aShippingAddress['number']);
        // $invAddress->getStreetNumberExtension($order['addressShippingExtension']);
        $invAddress->setZipCode($order->get_shipping_postcode());
        $invAddress->setCity($order->get_shipping_city());
        $invAddress->setCountryCode(strtoupper($order->get_shipping_country()));
        return $invAddress;
    }


    /**
     * @param $order
     * @param $data
     * @return \PayNL\Sdk\Model\Customer
     */
    public static function getPayCustomer($order, $methodName)
    {
        $customer = new \PayNL\Sdk\Model\Customer();
        $customer->setFirstName($order->get_shipping_first_name());
        $customer->setLastName(substr($order->get_shipping_last_name(), 0, 32));

        $birthdate = PPMFWC_Helper_Data::getPostTextField($methodName . '_birthdate');
        $birthdate = empty($birthdate) ? null : $birthdate;
        if (!empty($birthdate)) {
            $customer->setBirthDate($birthdate);
        }

        $customer->setPhone($order->get_billing_phone());
        $customer->setEmail($order->get_billing_email());

        $company = new \PayNL\Sdk\Model\Company();
        $company->setName($order->get_billing_company());
        $company->setVat((string)PPMFWC_Helper_Data::getPostTextField('vat_number', true));
        $company->setCoc((string)PPMFWC_Helper_Data::getPostTextField('coc_number', true));
        $company->setCountryCode(strtoupper($order->get_billing_country()));
        $customer->setCompany($company);
        $customer->setIpAddress(self::getIpAddress($order));

        return $customer;
    }

    /**
     * @param $order
     * @return mixed|string
     */
    private static function getIpAddress($order)
    {
        $orderIp = $order->get_customer_ip_address();
        switch (get_option('paynl_test_ipadress')) {
            case 'orderremoteaddress':
                return $orderIp;

            case 'remoteaddress':
                return $_SERVER['REMOTE_ADDR'] ?? '';

            case 'httpforwarded':
                $headers = function_exists('getallheaders') ? getallheaders() : [];
                $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

                if (!empty($headers['X-Forwarded-For'])) {
                    $remoteIp = explode(',', $headers['X-Forwarded-For'])[0];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $remoteIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
                }

                return trim($remoteIp, '[]');

            default:
                return paynl_get_ip();
        }
    }

    /**
     * @param WC_Order $order
     * @return \PayNL\Sdk\Model\Products
     */
    public static function getPayProducts(WC_Order $order): \PayNL\Sdk\Model\Products
    {
        $collectionProducts = new PayNL\Sdk\Model\Products();

        $items = $order->get_items();


        if (is_array($items)) {
            foreach ($items as $item) {
                $pricePerPiece = ($item['line_subtotal'] + $item['line_subtotal_tax']) / $item['qty'];
                $code = paynl_determine_vat_class(($item['line_subtotal'] + $item['line_subtotal_tax']), $item['line_subtotal_tax']);

                $collectionProducts->addProduct(
                    new PayNL\Sdk\Model\Product(
                        (string)$item['product_id'],
                        mb_substr($item['name'], 0, 45, "UTF-8"),
                        $pricePerPiece,
                        null,
                        PayNL\Sdk\Model\Product::TYPE_ARTICLE,
                        $item['qty'],
                        $code,
                    )
                );
            }
        }

        $shipping_total = $order->get_shipping_total();

        # Add shipping costs information
        $shipping = floatval($shipping_total) + floatval($order->get_shipping_tax());
        if ($shipping != 0) {
            $code = paynl_determine_vat_class($shipping_total, $order->get_shipping_tax());

            $collectionProducts->addProduct(
                new PayNL\Sdk\Model\Product(
                    'shipping',
                    __('Shipping', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
                    $shipping,
                    null,
                    PayNL\Sdk\Model\Product::TYPE_SHIPPING,
                    1,
                    $code,
                )
            );
        }

        # Add discount information
        $discount = $order->get_total_discount(false);
        if ($discount != 0) {
            $discountExcl = $order->get_total_discount();
            $discountTax = $discount - $discountExcl;
            $code = paynl_determine_vat_class(($discount * -1), $discountTax);
            $collectionProducts->addProduct(
                new PayNL\Sdk\Model\Product(
                    'discount',
                    __('Discount', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
                    $discount * -1,
                    null,
                    PayNL\Sdk\Model\Product::TYPE_DISCOUNT,
                    1,
                    $code
                )
            );
        }

        # Add fee information
        $fees = $order->get_fees();
        if (!empty($fees)) {
            foreach ($fees as $fee) {
                $collectionProducts->addProduct(
                    new PayNL\Sdk\Model\Product(
                        $fee['type'],
                        $fee['name'],
                        $fee['line_total'],
                        null,
                        PayNL\Sdk\Model\Product::TYPE_HANDLING,
                        1,
                        null,
                        null
                    )
                );
            }
        }

        return $collectionProducts;
    }
}