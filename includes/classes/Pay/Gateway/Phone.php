<?php
class Pay_Gateway_Phone extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_phone';
    }

    public static function getName() {
        return 'Telefonisch betalen';
    }

    public static function getOptionId() {
        return 1600;
    }

}
    