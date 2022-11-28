<?php

class PPMFWC_Gateway_OnlineBankbetaling extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_onlinebankbetaling';
    }

    public static function getName()
    {
        return 'Online Bankbetaling';
    }

    public static function getOptionId()
    {
        return 2970;
    }

}