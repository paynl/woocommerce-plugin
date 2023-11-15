<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_PostepayHighRisk extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_postepayhighrisk';
    }

    /**
     * @return stringF
     */
    public static function getName()
    {
        return 'Postepay';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 708;
    }
}
