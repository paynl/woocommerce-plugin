<?php

class PPMFWC_Gateway_BloemenCadeaukaart extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_bloemencadeaukaart';
    }

    public static function getName()
    {
        return 'Bloemen Cadeaukaart';
    }

    public static function getOptionId()
    {
        return 2607;
    }

}