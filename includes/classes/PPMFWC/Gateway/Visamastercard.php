<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_Visamastercard extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_visamastercard';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Visa/Mastercard';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 709;
        }
        return 706;
    }
}
