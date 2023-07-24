<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Directebankinggb extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_directebankinggb';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Sofortbanking Groot-Brittanië';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 565;
    }
}
