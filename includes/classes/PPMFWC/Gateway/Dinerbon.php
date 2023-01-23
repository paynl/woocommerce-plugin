<?php

class PPMFWC_Gateway_Dinerbon extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_dinerbon';
    }

    public static function getName()
    {
        return 'Dinerbon';
    }

    public static function getOptionId()
    {
        return 2670;
    }

}