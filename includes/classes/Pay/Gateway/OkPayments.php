<?php

class Pay_Gateway_OkPayments extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_okpayments';
    }

    public static function getName() {
        return 'OK Payments';
    }

    public static function getOptionId() {
        return 2110;
    }

}