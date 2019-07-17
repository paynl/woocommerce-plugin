<?php
class Pay_Gateway_Maestro extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_maestro';
    }

    public static function getName() {
        return 'Maestro';
    }

    public static function getOptionId() {
        if(self::is_high_risk()) return 715;
        return 712;
    }

}
    