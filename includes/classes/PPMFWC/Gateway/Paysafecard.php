<?php

class PPMFWC_Gateway_Paysafecard extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_paysafecard';
    }

    public static function getName()
    {
        return 'Paysafecard';
    }

    public static function getOptionId()
    {
        return 553;
    }

}