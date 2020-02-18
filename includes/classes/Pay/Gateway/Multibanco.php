<?php

class Pay_Gateway_Multibanco extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_multibanco';
    }

    public static function getName() {
        return 'Multibanco';
    }

    public static function getOptionId() {
        return 2271;
    }

}