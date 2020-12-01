<?php

class PPMFWC_Gateway_Tikkie extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_tikkie';
    }

    public static function getName() {
        return 'Tikkie';
    }

    public static function getOptionId() {
        return 2104;
    }

}