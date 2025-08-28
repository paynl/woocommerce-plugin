<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_SportsGiftCard extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_sportsgiftcard';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Sports giftcard';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 4422;
    }
}
