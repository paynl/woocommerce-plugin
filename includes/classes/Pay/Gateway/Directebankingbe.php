<?php
class Pay_Gateway_Directebankingbe extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_directebankingbe';
    }

    public static function getName() {
        return 'Sofortbanking België';
    }

    public static function getOptionId() {
        return 559;
    }

}