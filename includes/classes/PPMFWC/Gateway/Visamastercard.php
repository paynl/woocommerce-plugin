<?php

class PPMFWC_Gateway_Visamastercard extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_visamastercard';
    }

    public static function getName()
    {
        return 'Visa/Mastercard';
    }

    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 709;
        }
        return 706;
    }

}
    