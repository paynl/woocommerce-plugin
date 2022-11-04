<?php

class PPMFWC_Gateway_CapayableGespreid extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_capayablegespreid';
    }

    public static function getName()
    {
        return 'IN3 – Betaal in 3 delen, 0% rente';
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

    public static function useInvoiceAddressAsShippingAddress()
    {
        return true;
    }

    public static function alternativeReturnURL()
    {
        return true;
    }

}