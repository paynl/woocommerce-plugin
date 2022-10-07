<?php

class PPMFWC_Gateway_Blik extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_blik';
    }

    public static function getName()
    {
        return 'Blik';
    }

    public static function getOptionId()
    {
        return 2856;
    }
}
