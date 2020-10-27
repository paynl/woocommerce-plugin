<?php

class Pay_Gateway_DeCadeaukaart extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_decadeaukaart';
    }

    public static function getName() {
        return 'De Cadeaukaart';
    }

    public static function getOptionId() {
        return 2601;
    }

}