<?php

class Pay_Gateway_Amex extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_amex';
    }

    public static function getName() {
        return 'American Express';
    }

    public static function getOptionId() {
        return 1705;
    }

}