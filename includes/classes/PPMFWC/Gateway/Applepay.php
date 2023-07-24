<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Applepay extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_applepay';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Apple Pay';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2277;
    }

    /**
     * @return boolean
     */
    public static function showApplePayDetection()
    {
        return true;
    }
}
