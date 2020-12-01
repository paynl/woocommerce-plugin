<?php

class PPMFWC_Gateway_Payconiq extends PPMFWC_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_payconiq';
    }

    public static function getName() {
        return 'Payconiq';
    }

    public static function getOptionId() {
        return 2379;
    }

}