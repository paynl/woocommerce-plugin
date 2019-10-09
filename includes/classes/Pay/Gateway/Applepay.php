<?php

class Pay_Gateway_Applepay extends Pay_Gateway_Abstract {

  public static function getId() {
    return 'pay_gateway_applepay';
  }

  public static function getName() {
    return 'Apple Pay';
  }

  public static function getOptionId() {
    return 2277;
  }

}