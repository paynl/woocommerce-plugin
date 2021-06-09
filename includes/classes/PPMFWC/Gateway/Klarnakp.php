<?php

class PPMFWC_Gateway_Klarnakp extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_klarnakp';
    }

    public static function getName()
    {
        return 'Klarna KP';
    }

    public static function getOptionId()
    {
        return 2265;
    }

    public static function showAuthorizeSetting()
    {
        return true;
    }

    public static function useInvoiceAddressAsShippingAddress()
    {
        return true;
    }
}