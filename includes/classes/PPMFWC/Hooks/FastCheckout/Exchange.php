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
     * Adds billing and shipping address information to the given order based on provided checkout data.
     *
     * @param array $checkoutData An associative array containing customer, billing, and shipping address data.
     * @param object $order The order object to which the addresses will be added.
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    public static function addAddressToOrder(array $checkoutData, object $order)
    {
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

        $order->set_address($billingAddress, 'billing');
        $order->set_address($shippingAddress, 'shipping');
        $order->save();
    }
}
