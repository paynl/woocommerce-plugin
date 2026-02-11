<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_CreditCardsGrouped extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_creditcardsgrouped';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Credit- & Debitcards';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 11;
    }

    /**
     * @return integer
     */
    public static function getImagePathName()
    {
        return 'CNP.png';
    }

}
