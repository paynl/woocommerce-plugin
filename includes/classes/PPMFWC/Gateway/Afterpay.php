<?php

class PPMFWC_Gateway_Afterpay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_afterpay';
    }

    public static function getName()
    {
        return 'AfterPay';
    }

    public static function getOptionId()
    {
        return 739;
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