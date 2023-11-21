<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_In3business extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_in3business';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'In3 Business';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 3192;
    }
}
