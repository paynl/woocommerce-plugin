<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Cartebleue extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_cartebleue';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Carte Bleue';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 711;
        }
        return 710;
    }
}
