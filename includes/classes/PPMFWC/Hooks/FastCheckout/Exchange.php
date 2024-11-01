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

class PPMFWC_Hooks_FastCheckout_Exchange
{
    /**
     * @param array $params
     * @return boolean
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function isFastCheckout($params)
    {
        return strpos($params['orderId'] ?? '', "fastcheckout") !== false && !empty($params['checkoutData'] ?? '');
    }

    /**
     * @param array $params
     * @param string $orderId
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function addAddressToOrder($params, $orderId)
    {
        $checkoutData = $params['checkoutData'];
        $customerData = $checkoutData['customer'] ?? null;
        $billingAddressData = $checkoutData['billingAddress'] ?? null;
        $shippingAddressData = $checkoutData['shippingAddress'] ?? null;

        $billingAddress = array(
            'first_name' => $billingAddressData['firstName'] ?? $customerData['firstName'],
            'last_name' => $billingAddressData['lastName'] ?? $customerData['lastName'],
            'company' => $customerData['company'] ?? '',
            'email' => $customerData['email'] ?? '',
            'phone' => $customerData['phone'] ?? '',
            'address_1' => $billingAddressData['streetName'] . ' ' . $shippingAddressData['streetNumber'] . $shippingAddressData['streetNumberAddition'],
            'address_2' => '',
            'city' => $billingAddressData['city'] ?? '',
            'state' => '',
            'postcode' => $billingAddressData['zipCode'] ?? '',
            'country' => $billingAddressData['countryCode'] ?? '',
        );

        $shippingAddress = array(
            'first_name' => $shippingAddressData['firstName'] ?? $customerData['firstName'],
            'last_name' => $shippingAddressData['lastName'] ?? $customerData['lastName'],
            'company' => $customerData['company'] ?? '',
            'email' => $customerData['email'] ?? '',
            'phone' => $customerData['phone'] ?? '',
            'address_1' => $shippingAddressData['streetName'] . ' ' . $shippingAddressData['streetNumber'] . $shippingAddressData['streetNumberAddition'],
            'address_2' => '',
            'city' => $shippingAddressData['city'] ?? '',
            'state' => '',
            'postcode' => $shippingAddressData['zipCode'] ?? '',
            'country' => $shippingAddressData['countryCode'] ?? '',
        );

        $order = new WC_Order($orderId);

        $order->set_address($billingAddress, 'billing');
        $order->set_address($shippingAddress, 'shipping');

        $order->save();
    }
}
