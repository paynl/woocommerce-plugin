<?php

class PPMFWC_Gateway_Monizze extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_monizze';
    }

    public static function getName()
    {
        return 'Monizze';
    }

    public static function getOptionId()
    {
        return 3027;
    }

}