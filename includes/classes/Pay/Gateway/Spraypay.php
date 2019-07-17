<?php

class Pay_Gateway_Spraypay extends Pay_Gateway_Abstract
{
    public static function getId() {
        return 'pay_gateway_spraypay';
    }

    public static function getName() {
        return 'SprayPay';
    }

    public static function getOptionId() {
        return 1987;
    }
}
