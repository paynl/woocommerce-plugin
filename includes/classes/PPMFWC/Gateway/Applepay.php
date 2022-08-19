<?php

class PPMFWC_Gateway_Applepay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_applepay';
    }

    public static function getName()
    {
        return 'Apple Pay';
    }

    public static function getOptionId()
    {
        return 2277;
    }

    public static function showApplePayDetection()
    {
        return true;
    }

}