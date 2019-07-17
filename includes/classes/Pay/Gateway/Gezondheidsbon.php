<?php

class Pay_Gateway_Gezondheidsbon extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_gezondheidsbon';
    }

    public static function getName() {
        return 'Gezondheidsbon';
    }

    public static function getOptionId() {
        return 812;
    }

}