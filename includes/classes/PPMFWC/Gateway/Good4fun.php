<?php

class PPMFWC_Gateway_Good4fun extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_good4fun';
    }

    public static function getName()
    {
        return 'Good4fun';
    }

    public static function getOptionId()
    {
        return 2628;
    }

}