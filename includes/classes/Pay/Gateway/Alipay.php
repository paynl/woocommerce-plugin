<?php

class Pay_Gateway_Alipay extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_alipay';
    }

    public static function getName() {
        return 'Alipay';
    }

    public static function getOptionId() {
        return 2080;
    }

}