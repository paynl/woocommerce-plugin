<?php

class PPMFWC_Gateway_SofortbankingDigitalServices extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_sofortbankingdigitalservices';
    }

    public static function getName()
    {
        return 'Sofortbanking Digital Services';
    }

    public static function getOptionId()
    {
        return 577;
    }

}