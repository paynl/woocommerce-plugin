<?php

class PPMFWC_Gateway_Googlepay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_googlepay';
    }

    public static function getName()
    {
        return 'Google Pay';
    }

    public static function getOptionId()
    {
        return 2558;
    }

}