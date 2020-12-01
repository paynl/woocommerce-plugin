<?php

class PPMFWC_Gateway_Podiumcadeaukaart extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_podiumcadeaukaart';
    }

    public static function getName()
    {
        return 'Podiumcadeaukaart';
    }

    public static function getOptionId()
    {
        return 816;
    }

}