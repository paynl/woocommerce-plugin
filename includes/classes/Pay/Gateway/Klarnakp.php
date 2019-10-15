<?php

class Pay_Gateway_Klarnakp extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_klarnakp';
    }

    public static function getName() {
        return 'Klarna KP';
    }

    public static function getOptionId() {
        return 2265;
    }

}