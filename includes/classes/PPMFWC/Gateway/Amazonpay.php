<?php

class PPMFWC_Gateway_Amazonpay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_amazonpay';
    }

    public static function getName()
    {
        return 'Amazonpay';
    }

    public static function getOptionId()
    {
        return 1903;
    }

}