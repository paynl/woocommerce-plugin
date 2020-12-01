<?php
class PPMFWC_Gateway_Directebankingbe extends PPMFWC_Gateway_Abstract {

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