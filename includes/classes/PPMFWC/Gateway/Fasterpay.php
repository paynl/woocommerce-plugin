<?php

class PPMFWC_Gateway_Fasterpay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_fasterpay';
    }

    public static function getName()
    {
        return 'Fasterpay';
    }

    public static function getOptionId()
    {
        return 610;
    }

}