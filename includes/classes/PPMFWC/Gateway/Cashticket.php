<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Cashticket extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_cashticket';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Cashticket';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 550;
    }
}
