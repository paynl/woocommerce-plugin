<?php

class PPMFWC_Gateway_Gezondheidsbon extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_gezondheidsbon';
    }

    public static function getName()
    {
        return 'Gezondheidsbon';
    }

    public static function getOptionId()
    {
        return 812;
    }

}