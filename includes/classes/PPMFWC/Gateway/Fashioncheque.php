<?php

class PPMFWC_Gateway_Fashioncheque extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_fashioncheque';
    }

    public static function getName()
    {
        return 'Fashioncheque';
    }

    public static function getOptionId()
    {
        return 815;
    }

}