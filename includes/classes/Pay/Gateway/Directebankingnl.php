<?php
class Pay_Gateway_Directebankingnl extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_directebankingnl';
    }

    public static function getName() {
        return 'Sofortbanking Nederland';
    }

    public static function getOptionId() {
        return 556;
    }

}