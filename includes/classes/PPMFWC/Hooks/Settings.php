<?php

/**
 * PPMFWC_Hooks_Settings
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Hooks_Settings
{

    /**
     * @param string $recipient
     * @param WC_Order $order
     * @param WC_Email $email
     * @return void
     */
    public static function ppmfwc_settings_email($recipient, $order, $email)
    {
        # Setting check: don't send emails in case of B2B
        if (!$order instanceof WC_Order) {
            return $recipient;
        }

        $billing_address = $order->get_address('billing');

        if (is_array($billing_address) && !empty($billing_address['company'])) {
            $billink_gateway = PPMFWC_Gateways::ppmfwc_getGateWayById(1672);
            if ($order->get_payment_method() == $billink_gateway->getId()) {
                if (!empty($billink_gateway->settings['b2b_invoices_disabled']) && $billink_gateway->settings['b2b_invoices_disabled'] == 'yes') {
                    return;
                }
            }
        }
        return $recipient;
    }
}
