<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Alipay extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_alipay';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Alipay';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2080;
    }
}
