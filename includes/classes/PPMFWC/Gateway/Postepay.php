<?php

class PPMFWC_Gateway_Postepay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_postepay';
    }

    public static function getName()
    {
        return 'Postepay';
    }

    public static function getOptionId($getDefault = false)
    {
        if (self::is_high_risk() && !$getDefault) {
            return 708;
        }
        return 707;
    }

}
    