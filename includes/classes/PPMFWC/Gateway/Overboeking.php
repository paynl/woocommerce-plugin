<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_Overboeking extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_overboeking';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Overboeking';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 136;
    }

    /**
     * @return boolean
     */
    public static function slowConfirmation()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public static function showAuthorizeSetting()
    {
        return true;
    }
}
