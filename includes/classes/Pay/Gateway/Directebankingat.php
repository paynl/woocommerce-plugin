<?php
class Pay_Gateway_Directebankingat extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_directebankingat';
    }

    public static function getName() {
        return 'Sofortbanking Oostenrijk';
    }

    public static function getOptionId() {
        return 568;
    }

}