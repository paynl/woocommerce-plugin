<?php

class PPMFWC_Gateway_Trustly extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_trustly';
    }

    public static function getName()
    {
        return 'Trustly';
    }

    public static function getOptionId()
    {
        return 2718;
    }

}