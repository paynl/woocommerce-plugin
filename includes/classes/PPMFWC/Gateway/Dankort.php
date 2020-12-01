<?php
class PPMFWC_Gateway_Dankort extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_dankort';
    }

    public static function getName() {
        return 'Dankort';
    }

    public static function getOptionId() {
        if(self::is_high_risk()) return 1942;
        return 1939;
    }

}
    