<?php
class PPMFWC_Gateway_Webshopgiftcard extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_webshopgiftcard';
    }

    public static function getName() {
        return 'Webshop Giftcard';
    }

    public static function getOptionId() {
        return 811;
    }

}
    