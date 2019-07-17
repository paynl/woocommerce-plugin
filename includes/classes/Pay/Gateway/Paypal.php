<?php
class Pay_Gateway_Paypal extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_paypal';
    }

    public static function getName() {
        return 'Paypal';
    }

    public static function getOptionId() {
        return 138;
    }

}