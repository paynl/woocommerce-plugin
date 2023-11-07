<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_AfterpayInternational extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_afterpayinternational';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Riverty';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2561;
    }

    /**
     * @return boolean
     */
    public static function showAuthorizeSetting()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public static function showDOB()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public static function useInvoiceAddressAsShippingAddress()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public static function alternativeReturnURL()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function askBirthdate()
    {
        return $this->get_option('ask_birthdate') != 'no';
    }

    /**
     * @return boolean
     */
    public function birthdateRequired()
    {
        return ($this->get_option('ask_birthdate') == 'yes_required' || ($this->get_option('ask_birthdate') == 'yes' && $this->get_option('birthdate_required') == 'yes')); // phpcs:ignore
    }

    /**
     * @return boolean
     */
    public function showVat()
    {
        return get_option('paynl_show_vat_number') == "yes";
    }

    /**
     * @return boolean
     */
    public function showCoc()
    {
        return get_option('paynl_show_coc_number') == "yes";
    }
}
