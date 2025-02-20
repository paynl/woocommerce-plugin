<?php

/**
 * PPMFWC_Gateway_Ideal
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */
class PPMFWC_Gateway_Ideal extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_ideal';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'iDEAL';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 10;
    }

    /**
     * @return array
     */
    public function getIssuers()
    {
        $issuers = PPMFWC_Helper_Data::getOptionSubs(self::getOptionId());
        foreach ($issuers as $key => $issuer) {
            $issuers[$key]['image_path'] = PPMFWC_PLUGIN_URL . 'assets/logos_issuers/qr-' . esc_attr($issuer['option_sub_id']) . '.svg';
        }
        return $issuers;
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_form_fields()
    {
        parent::init_form_fields();
        $optionId = $this->getOptionId();
        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $this->form_fields['fast_checkout_header'] = array(
                'css' => 'display:none;',
                'type' => 'info',
                'title' => esc_html(__('Fast checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'class' => 'paynlheader',
            );

            $this->form_fields['ideal_fast_checkout_on_cart'] = array(
                'title' => esc_html(__('Cart page', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => array(
                    0 => esc_html(__('Off', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    1 => esc_html(__('On', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                ),
                'default' => 0,
                'desc_tip' => __('Show the fast checkout button on the cart page. <br/><br/> This button allows users to checkout directly from the cart without the need to fill in their address.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), // phpcs:ignore
            );

            $this->form_fields['ideal_fast_checkout_on_minicart'] = array(
                'title' => esc_html(__('Minicart', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => array(
                    0 => esc_html(__('Off', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    1 => esc_html(__('On', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                ),
                'default' => 0,
                'desc_tip' => __('Show the fast checkout button on the minicart. <br/><br/> This button allows users to checkout directly from the minicart without the need to fill in their address.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), // phpcs:ignore
                'description' => esc_html(__('Please note that not all themes are compatible with this option.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
            );

            $this->form_fields['ideal_fast_checkout_on_product'] = array(
                'title' => esc_html(__('Product page', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => array(
                    0 => esc_html(__('Off', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    1 => esc_html(__('On', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                ),
                'default' => 0,
                'description' => '<br/>',
                'desc_tip' => __('Show the fast checkout button on every product page. <br/><br/> This button allows users to checkout directly from the cart without the need to fill in their address.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN), // phpcs:ignore
            );

            $this->form_fields['ideal_fast_checkout_shipping_default'] = array(
                'title' => esc_html(__('Default shipping method', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => $this->get_all_shipping_methods(),
                'description' => esc_html(__('Select the shipping method that should be applied first.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'desc_tip' => __('The default shipping method will be applied to fast checkout orders when shipping method cannot be selected by user.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            );

            $this->form_fields['ideal_fast_checkout_shipping_backup'] = array(
                'title' => esc_html(__('Fallback shipping method', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => $this->get_all_shipping_methods(),
                'description' => esc_html(__('Select the fallback shipping method, which will be applied when the default shipping method could not be applied.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'desc_tip' => __('In case the default shipping method could not by applied, this shipping method will be used.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            );

            $this->form_fields['ideal_fast_checkout_guest_only'] = array(
                'title' => esc_html(__('Guest checkout only', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'label' => ' ',
                'type' => 'checkbox',
                'default' => 'no',
                'description' => esc_html(__('Show the fast checkout button, only for guest customers.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'desc_tip' => __('When enabled, the fast checkout button will only be shown on the for guest users.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            );

            $this->form_fields['ideal_fast_checkout_modal'] = array(
                'title' => esc_html(__('Show modal', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'label' => ' ',
                'type' => 'checkbox',
                'default' => 'yes',
                'description' => esc_html(__('Open modal before fast checkout', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'desc_tip' => __('When enabled, a modal explaining on how fast checkout works will show before going through with fast checkout.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN),
            );
        }
    }
}
