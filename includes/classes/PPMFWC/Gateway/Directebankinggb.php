<?php

class PPMFWC_Gateway_Directebankinggb extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_directebankinggb';
    }

    public static function getName()
    {
        return 'Sofortbanking Groot-Brittanië';
    }

    public static function getOptionId()
    {
        return 565;
    }

}