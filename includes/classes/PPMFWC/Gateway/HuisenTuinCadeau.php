<?php

class PPMFWC_Gateway_HuisenTuinCadeau extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_huisentuincadeau';
    }

    public static function getName()
    {
        return 'Huis & Tuin Cadeau';
    }

    public static function getOptionId()
    {
        return 2283;
    }

}