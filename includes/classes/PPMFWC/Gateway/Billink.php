<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Billink extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_billink';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Achteraf betalen via Billink';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 1672;
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
}
