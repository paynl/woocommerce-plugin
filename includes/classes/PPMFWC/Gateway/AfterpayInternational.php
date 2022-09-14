<?php

class PPMFWC_Gateway_AfterpayInternational extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_afterpayinternational';
    }

    public static function getName()
    {
        return 'Afterpay International / Riverty International';
    }

    public static function getOptionId()
    {
        return 2561;
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