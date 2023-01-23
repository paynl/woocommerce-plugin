<?php

class PPMFWC_Gateway_Bioscoopbon extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_bioscoopbon';
    }

    public static function getName()
    {
        return 'Bioscoopbon';
    }

    public static function getOptionId()
    {
        return 2133;
    }

}