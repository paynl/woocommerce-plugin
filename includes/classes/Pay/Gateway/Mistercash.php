<?php
class Pay_Gateway_Mistercash extends Pay_Gateway_Abstract {

    public static function getId() {
        return 'pay_gateway_mistercash';
    }

    public static function getName() {
        return 'Bancontact';
    }

    public static function getOptionId() {
        return 436;
    }

}
    