<?php

class PPMFWC_Gateway_Fashiongiftcard extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_fashiongiftcard';
    }

    public static function getName() {
        return 'Fashion giftcard';
    }

    public static function getOptionId() {
        return 1669;
    }

}