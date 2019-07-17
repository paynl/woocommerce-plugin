<?php

class Pay_Gateway_Givacard extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_givacard';
    }

    public static function getName() {
        return 'Givacard';
    }

    public static function getOptionId() {
        return 1657;
    }

}