<?php

class PPMFWC_Gateway_Winkelcheque extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_winkelcheque';
    }

    public static function getName()
    {
        return 'Winkelcheque';
    }

    public static function getOptionId()
    {
        return 2616;
    }

}