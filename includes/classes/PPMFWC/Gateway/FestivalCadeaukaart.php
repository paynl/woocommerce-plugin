<?php

class PPMFWC_Gateway_FestivalCadeaukaart extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_festivalcadeaukaart';
    }

    public static function getName()
    {
        return 'Festival Cadeaukaart';
    }

    public static function getOptionId()
    {
        return 2511;
    }

}