<?php
class PPMFWC_Gateway_Cartasi extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_cartasi';
    }

    public static function getName() {
        return 'Cartasi';
    }

    public static function getOptionId() {
        if(self::is_high_risk()) return 1948;
        return 1945;
    }

}
    