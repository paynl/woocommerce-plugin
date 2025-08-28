<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class FashionChequeBeauty extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_fashionchequebeauty';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Fashion cheque beauty';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 4428;
    }
}
