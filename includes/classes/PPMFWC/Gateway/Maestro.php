<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_Maestro extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_maestro';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Maestro';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 715;
        }
        return 712;
    }
}
