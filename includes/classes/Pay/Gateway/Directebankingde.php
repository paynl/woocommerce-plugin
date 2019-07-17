<?php
class Pay_Gateway_Directebankingde extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_directebankingde';
    }

    public static function getName() {
        return 'Sofortbanking Duitsland';
    }

    public static function getOptionId() {
        return 562;
    }

}