<?php

class PPMFWC_Gateway_Biller extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_biller';
    }

    public static function getName()
    {
        return 'Biller';
    }

    public static function getOptionId()
    {
        return 2931;
    }
}
