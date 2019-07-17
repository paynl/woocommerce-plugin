<?php

class Pay_Gateway_Yourgift extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_yourgift';
    }

    public static function getName() {
        return 'Yourgift';
    }

    public static function getOptionId() {
        return 1645;
    }

}