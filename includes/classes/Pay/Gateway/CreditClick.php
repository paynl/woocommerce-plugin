<?php

class Pay_Gateway_CreditClick extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_creditclick';
    }

    public static function getName() {
        return 'Credit Click';
    }

    public static function getOptionId() {
        return 2107;
    }

}