<?php

class PPMFWC_Gateway_Overboeking extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_overboeking';
    }

    public static function getName()
    {
        return 'Overboeking';
    }

    public static function getOptionId()
    {
        return 136;
    }

    public static function slowConfirmation()
    {
        return true;
    }

    public static function showAuthorizeSetting()
    {
        return true;
    }

}