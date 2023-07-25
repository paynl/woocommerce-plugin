<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Fashioncheque extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_fashioncheque';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Fashioncheque';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 815;
    }
}
