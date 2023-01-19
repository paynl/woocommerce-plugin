<?php

class PPMFWC_Gateway_Boekenbon extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_boekenbon';
    }

    public static function getName()
    {
        return 'Boekenbon';
    }

    public static function getOptionId()
    {
        return 2838;
    }

}