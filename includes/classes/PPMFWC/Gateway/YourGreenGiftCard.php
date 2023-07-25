<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_YourGreenGiftCard extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_yourgreengiftcard';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Your Green Gift Cadeaukaart';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2925;
    }
}
