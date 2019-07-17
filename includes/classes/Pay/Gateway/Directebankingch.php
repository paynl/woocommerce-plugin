<?php
class Pay_Gateway_Directebankingch extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_directebankingch';
    }

    public static function getName() {
        return 'Sofortbanking Zwitserland';
    }

    public static function getOptionId() {
        return 571;
    }

}
