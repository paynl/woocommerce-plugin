<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_Upass extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_upass';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'U-Pass';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 4283;
    }
}
