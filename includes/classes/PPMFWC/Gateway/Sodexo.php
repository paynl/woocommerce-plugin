<?php

class PPMFWC_Gateway_Sodexo extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_sodexo';
    }

    public static function getName()
    {
        return 'Sodexo';
    }

    public static function getOptionId()
    {
        return 3030;
    }

}