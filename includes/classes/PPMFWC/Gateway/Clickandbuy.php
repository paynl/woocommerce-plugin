<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Clickandbuy extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_clickandbuy';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Clickandbuy';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 139;
    }
}
