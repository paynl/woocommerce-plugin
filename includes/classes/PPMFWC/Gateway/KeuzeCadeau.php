<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_KeuzeCadeau extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_keuzecadeau';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Keuze Cadeau';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 4917;
    }
}
