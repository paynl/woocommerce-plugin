<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Directebankingde extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_directebankingde';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Sofortbanking Duitsland';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 562;
    }
}
