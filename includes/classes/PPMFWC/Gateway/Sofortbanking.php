<?php

class PPMFWC_Gateway_Sofortbanking extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_sofortbanking';
    }

    public static function getName()
    {
        return 'Sofortbanking';
    }

    public static function getOptionId($getDefault = false)
    {
        if (self::is_high_risk() && !$getDefault) {
            return 595;
        }
        return 559;
    }

    public static function slowConfirmation()
    {
        return true;
    }
}