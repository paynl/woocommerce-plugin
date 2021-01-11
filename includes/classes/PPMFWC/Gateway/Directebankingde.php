<?php

class PPMFWC_Gateway_Directebankingde extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_directebankingde';
    }

    public static function getName()
    {
        return 'Sofortbanking Duitsland';
    }

    public static function getOptionId()
    {
        return 562;
    }

}