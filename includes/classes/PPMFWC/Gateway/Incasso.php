<?php

class PPMFWC_Gateway_Incasso extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_incasso';
    }

    public static function getName()
    {
        return 'Incasso';
    }

    public static function getOptionId()
    {
        return 137;
    }

}