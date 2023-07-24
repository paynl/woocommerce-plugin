<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Biercheque extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_biercheque';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Biercheque';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2622;
    }
}
