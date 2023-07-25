<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_BataviastadCadeaukaart extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_bataviastadcadeaukaart';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Batavia Stad Cadeaukaart';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2955;
    }
}
