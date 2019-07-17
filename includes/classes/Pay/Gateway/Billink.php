<?php

class Pay_Gateway_Billink extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_billink';
    }

    public static function getName() {
        return 'Billink';
    }

    public static function getOptionId() {
        return 1672;
    }

}