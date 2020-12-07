<?php

class PPMFWC_Gateway_P24 extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_p24';
    }

    public static function getName()
    {
        return 'Przelewy 24';
    }

    public static function getOptionId()
    {
        return 2151;
    }

}