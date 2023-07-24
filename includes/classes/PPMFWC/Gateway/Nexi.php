<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_Nexi extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_nexi';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Nexi';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 1948;
        }
        return 1945;
    }
}
