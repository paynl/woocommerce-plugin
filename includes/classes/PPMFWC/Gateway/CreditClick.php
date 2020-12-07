<?php

class PPMFWC_Gateway_CreditClick extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_creditclick';
    }

    public static function getName()
    {
        return 'CreditClick';
    }

    public static function getOptionId()
    {
        return 2107;
    }

}