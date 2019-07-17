<?php

class Pay_Gateway_Focum extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_focum';
    }

    public static function getName() {
        return 'Achterafbetalen.nl';
    }

    public static function getOptionId() {
        return 1702;
    }

}