<?php

class PPMFWC_Gateway_BataviastadCadeaukaart extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_bataviastadcadeaukaart';
    }

    public static function getName()
    {
        return 'Batavia Stad Cadeaukaart';
    }

    public static function getOptionId()
    {
        return 2955;
    }

}