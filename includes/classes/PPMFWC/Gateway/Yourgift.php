<?php

class PPMFWC_Gateway_Yourgift extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_yourgift';
    }

    public static function getName() {
        return 'Yourgift';
    }

    public static function getOptionId() {
        return 1645;
    }

}