<?php

class PPMFWC_Gateway_Capayable extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_capayable';
    }

    public static function getName()
    {
        return 'Capayable Achteraf Betalen';
    }

    public static function getOptionId()
    {
        return 1744;
    }

    public static function showAuthorizeSetting()
    {
        return true;
    }

    public static function showDOB()
    {
        return true;
    }

    public static function useInvoiceAddressAsShippingAddress()
    {
        return true;
    }

    public static function differentReturnURL()
    {
        return true;
    }

}