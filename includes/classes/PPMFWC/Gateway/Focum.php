<?php

class PPMFWC_Gateway_Focum extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_focum';
    }

    public static function getName()
    {
        return 'Achterafbetalen.nl';
    }

    public static function getOptionId()
    {
        return 1702;
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