<?php

class PPMFWC_Gateway_ParfumCadeaukaart extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_parfumcadeaukaart';
    }

    public static function getName()
    {
        return 'Parfum Cadeaukaart';
    }

    public static function getOptionId()
    {
        return 2682;
    }

}