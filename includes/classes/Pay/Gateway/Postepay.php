<?php
class Pay_Gateway_Postepay extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_postepay';
    }

    public static function getName() {
        return 'Postepay';
    }

    public static function getOptionId() {
        if(self::is_high_risk()) return 708;
        return 707;
    }

}
    