<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Givacard extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_givacard';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Givacard';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 1657;
    }

    /**
     * @return boolean
     */
    public static function showLogoSetting()
    {
        return true;
    }
}
