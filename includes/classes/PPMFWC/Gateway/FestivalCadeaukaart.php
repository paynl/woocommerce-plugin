<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_FestivalCadeaukaart extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_festivalcadeaukaart';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Festival Cadeaukaart';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2511;
    }
}
