<?php

class PPMFWC_Gateway_OkPayments extends PPMFWC_Gateway_Abstract {

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