<?php

class PPMFWC_Gateway_Nexi extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_nexi';
    }

    public static function getName()
    {
        return 'Nexi';
    }

    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 1948;
        }
        return 1945;
    }

}
    