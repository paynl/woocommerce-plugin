<?php
class Pay_Gateway_Eps extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_eps';
    }

    public static function getName() {
        return 'Eps-Überweisung';
    }

    public static function getOptionId() {
        return 2062;
    }

}
    