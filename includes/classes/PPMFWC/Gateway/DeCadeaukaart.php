<?php

class PPMFWC_Gateway_DeCadeaukaart extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_decadeaukaart';
    }

    public static function getName()
    {
        return 'De Cadeaukaart';
    }

    public static function getOptionId()
    {
        return 2601;
    }

}