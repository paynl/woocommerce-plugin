<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing 
 */

class PPMFWC_Gateway_HuisenTuinCadeau extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_huisentuincadeau';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Huis & Tuin Cadeau';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2283;
    }
}
