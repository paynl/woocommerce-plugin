<?php

class PPMFWC_Gateway_VVVGiftcard extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_vvvgiftcard';
    }

    public static function getName() {
        return 'VVV Giftcard';
    }

    public static function getOptionId() {
        return 1714;
    }

}