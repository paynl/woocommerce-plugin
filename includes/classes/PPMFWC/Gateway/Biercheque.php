<?php

class PPMFWC_Gateway_Biercheque extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_biercheque';
    }

    public static function getName()
    {
        return 'Biercheque';
    }

    public static function getOptionId()
    {
        return 2622;
    }

}