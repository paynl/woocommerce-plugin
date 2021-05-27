<?php

class PPMFWC_Gateway_CapayableGespreid extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_capayablegespreid';
    }

    public static function getName()
    {
        return 'in3 keer betalen, 0% rente';
    }

    public static function getOptionId()
    {
        return 1813;
    }

    public static function showAuthorizeSetting()
    {
        return true;
    }

    public static function showDOB()
    {
        return true;
    }

}