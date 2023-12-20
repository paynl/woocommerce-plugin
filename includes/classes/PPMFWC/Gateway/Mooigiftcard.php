<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_Mooigiftcard extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_mooigiftcard';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'MOOI giftcard';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 3183;
    }
}
