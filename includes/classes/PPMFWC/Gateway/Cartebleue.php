<?php

class PPMFWC_Gateway_Cartebleue extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_cartebleue';
    }

    public static function getName()
    {
        return 'Carte Bleue';
    }

    public static function getOptionId()
    {
        if (self::is_high_risk()) {
            return 711;
        }
        return 710;
    }

}
    