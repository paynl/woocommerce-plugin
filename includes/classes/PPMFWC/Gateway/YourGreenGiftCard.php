<?php

class PPMFWC_Gateway_YourGreenGiftCard extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_yourgreengiftcard';
    }

    public static function getName()
    {
        return 'Your Green Gift Cadeaukaart';
    }

    public static function getOptionId()
    {
        return 2925;
    }

}