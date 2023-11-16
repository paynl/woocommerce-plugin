<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Cartasi extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_cartasi';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Cartasi';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 1948;
        }
        return 1945;
    }
}
