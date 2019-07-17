<?php

class Pay_Gateway_Wijncadeau extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_wijncadeau';
    }

    public static function getName() {
        return 'Wijncadeau';
    }

    public static function getOptionId() {
        return 1666;
    }

}