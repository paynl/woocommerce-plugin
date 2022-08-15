<?php

class PPMFWC_Gateway_Billink extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_billink';
    }

    public static function getName()
    {
        return 'Achteraf betalen via Billink';
    }

    public static function getOptionId()
    {
        return 1672;
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