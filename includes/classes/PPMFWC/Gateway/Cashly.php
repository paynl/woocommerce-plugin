<?php

class PPMFWC_Gateway_Cashly extends PPMFWC_Gateway_Abstract
{
    public static function getId() {
        return 'pay_gateway_cashly';
    }

    public static function getName() {
        return 'Cashly';
    }

    public static function getOptionId() {
        return 1981;
    }
}
