<?php

class PPMFWC_Gateway_Afterpay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_afterpay';
    }

    public static function getName()
    {
        return 'Afterpay';
    }

    public static function getOptionId()
    {
        return 739;
    }

}