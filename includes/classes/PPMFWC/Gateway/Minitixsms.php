<?php
class PPMFWC_Gateway_Minitixsms extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_minitixsms';
    }

    public static function getName() {
        return 'Minitix sms';
    }

    public static function getOptionId() {
        return 808;
    }

}
    