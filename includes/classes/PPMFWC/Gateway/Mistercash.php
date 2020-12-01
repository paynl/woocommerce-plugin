<?php

class PPMFWC_Gateway_Mistercash extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_mistercash';
    }

    public static function getName()
    {
        return 'MisterCash / Bancontact';
    }

    public static function getOptionId()
    {
        return 436;
    }

}
    