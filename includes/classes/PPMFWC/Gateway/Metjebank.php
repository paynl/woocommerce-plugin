<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Metjebank extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_metjebank';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Metjebank.nl';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 5358;
    }
}
