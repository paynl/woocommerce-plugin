<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_GiftCardsGrouped extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_giftcardsgrouped';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Giftcards or Voucher';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 3189;
    }

    /**
     * @return integer
     */
    public static function getImagePathName()
    {
        return 'VOUCHER.png';
    }

}
