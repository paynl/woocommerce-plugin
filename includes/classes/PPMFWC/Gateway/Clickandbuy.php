<?php
class PPMFWC_Gateway_Clickandbuy extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_clickandbuy';
    }

    public static function getName() {
        return 'Clickandbuy';
    }

    public static function getOptionId() {
        return 139;
    }

}
