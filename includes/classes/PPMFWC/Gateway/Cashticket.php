<?php
class PPMFWC_Gateway_Cashticket extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_cashticket';
    }

    public static function getName() {
        return 'Cashticket';
    }

    public static function getOptionId() {
        return 550;
    }

}

