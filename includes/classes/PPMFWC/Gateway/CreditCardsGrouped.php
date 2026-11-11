<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_CreditCardsGrouped extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_creditcardsgrouped';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Credit- & Debitcards';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 11;
    }

    /**
     * @return integer
     */
    public static function getImagePathName()
    {
        return 'CNP.png';
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function payment_fields()
    {
        parent::payment_fields();

        echo '<div id="card-payments"></div>';
        echo '<div id="card-section"></div>';
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_form_fields()
    {
        parent::init_form_fields();
        $optionId = $this->getOptionId();
        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $this->form_fields['fast_checkout_header'] = array(
                'css' => 'display:none;',
                'type' => 'info',
                'title' => esc_html(__('PayParts', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'class' => 'paynlheader',
            );

            $this->form_fields['credit_cards_grouped_pay_parts'] = array(
                'title' => esc_html(__('PayParts', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => array(
                    0 => esc_html(__('Off', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    1 => esc_html(__('On', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                ),
                'default' => 0,
                'desc_tip' => __('Show the PayParts credit card in checkout.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), // phpcs:ignore
            );

            $this->form_fields['credit_cards_grouped_pay_parts_secret'] = array(
                'title' => esc_html(__('Secret *', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'text',
                'desc_tip' => __('The Secret connected to your Sales Location.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            );

        }
    }

}
