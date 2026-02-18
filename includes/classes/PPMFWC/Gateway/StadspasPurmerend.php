<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_StadspasPurmerend extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_stadspaspurmerend';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Stadspas Purmerend';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 5177;
    }
}
