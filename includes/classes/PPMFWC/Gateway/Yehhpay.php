<?php

class PPMFWC_Gateway_Yehhpay extends PPMFWC_Gateway_Abstract
{

  public static function getId()
  {
    return 'pay_gateway_yehhpay';
  }

  public static function getName()
  {
    return 'Yehhpay';
  }

  public static function getOptionId()
  {
    return 1877;
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
