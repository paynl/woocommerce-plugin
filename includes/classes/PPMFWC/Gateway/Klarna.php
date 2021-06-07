<?php

class PPMFWC_Gateway_Klarna extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_klarna';
    }

    public static function getName()
    {
        return 'Klarna';
    }

    public static function getOptionId()
    {
        return 1717;
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

}