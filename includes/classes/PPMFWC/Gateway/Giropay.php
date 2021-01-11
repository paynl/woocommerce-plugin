<?php

class PPMFWC_Gateway_Giropay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_giropay';
    }

    public static function getName()
    {
        return 'Giropay';
    }

    public static function getOptionId()
    {
        return 694;
    }

}