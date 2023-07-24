<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Bioscoopbon extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_bioscoopbon';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Bioscoopbon';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2133;
    }
}
