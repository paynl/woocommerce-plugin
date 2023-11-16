<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Biller extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_biller';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Biller';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 2931;
    }

    /**
     * @return boolean
     */
    public static function showDOB()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function askBirthdate()
    {
        return $this->get_option('ask_birthdate') != 'no';
    }

    /**
     * @return boolean
     */
    public function birthdateRequired()
    {
        return ($this->get_option('ask_birthdate') == 'yes_required' || ($this->get_option('ask_birthdate') == 'yes' && $this->get_option('birthdate_required') == 'yes')); // phpcs:ignore
    }

    /**
     * @return boolean
     */
    public function showVat()
    {
        return get_option('paynl_show_vat_number') == "yes";
    }

    /**
     * @return boolean
     */
    public function showCoc()
    {
        return get_option('paynl_show_coc_number') == "yes";
    }
}
