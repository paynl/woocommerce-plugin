<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Billink extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_billink';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Achteraf betalen via Billink';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 1672;
    }

    /**
     * @return boolean
     */
    public static function showAuthorizeSetting()
    {
        return true;
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
    public static function useInvoiceAddressAsShippingAddress()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public static function alternativeReturnURL()
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
        return (!empty(get_option('paynl_show_vat_number')) && get_option('paynl_show_vat_number') != "no");
    }

    /**
     * @return boolean
     */
    public function showCoc()
    {
        return (!empty(get_option('paynl_show_coc_number')) && get_option('paynl_show_coc_number') != "no");
    }

    /**
     * @return boolean
     */
    public function vatRequired()
    {
        return get_option('paynl_show_vat_number') == "yes_required";
    }

    /**
     * @return boolean
     */
    public function cocRequired()
    {
        return get_option('paynl_show_coc_number') == "yes_required";
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_form_fields()
    {
        parent::init_form_fields();
        $optionId = $this->getOptionId();
        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $this->form_fields['b2b_invoices_disabled'] = array(
                'title' => esc_html(__('B2B invoice emails', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'label' => esc_html(__('Disable invoice emails business orders.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => esc_html(__('Enable this option to stop sending invoice emails in case of business orders.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)), // phpcs:ignore
            );
        }
    }
}
