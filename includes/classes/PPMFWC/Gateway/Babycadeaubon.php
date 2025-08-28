<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class Babycadeaubon extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_babycadeaubon';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Babycadeaubon';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 4416;
    }
}
