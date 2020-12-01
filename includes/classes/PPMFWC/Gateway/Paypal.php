<?php

class PPMFWC_Gateway_Paypal extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_paypal';
    }

    public static function getName()
    {
        return 'Paypal';
    }

    public static function getOptionId()
    {
        return 138;
    }

}