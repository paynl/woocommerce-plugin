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
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function payment_fields()
    {
        parent::payment_fields();

        # Fieldselection iDEAL Banks
        $optionSubs = PPMFWC_Helper_Data::getOptionSubs($this->getOptionId());

        $selectionType = $this->get_option('paynl_bankselection');
        if (!empty($optionSubs) && $selectionType != 'none') {
            if ($selectionType == 'select') {
                ?>
                <fieldset>
                    <legend><?php echo esc_html(__('Pay safely via your bank', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></legend>
                    <select name="option_sub_id">
                        <option value=""><?php echo esc_html(__('Select your bank...', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></option>
                        <?php
                        foreach ($optionSubs as $optionSub) {
                            echo '<option value="' . esc_attr($optionSub['option_sub_id']) . '">' . esc_html($optionSub['name']) . '</option>';
                        }
                        ?>
                    </select>
                </fieldset>   
            <?php } elseif ($selectionType == 'radio') {
                ?>
                <ul class="pay_radio_select">
                    <?php
                    foreach ($optionSubs as $optionSub) {
                        echo '<li><label><input type="radio" name="option_sub_id" value="' . esc_attr($optionSub['option_sub_id']) . '" />&nbsp;<img src="' . PPMFWC_PLUGIN_URL . 'assets/logos_issuers/qr-' . esc_attr($optionSub['option_sub_id']) . '.svg" alt="' . esc_attr($optionSub['name']) . '" title="' . esc_attr($optionSub['name']) . '" /><span>' . esc_attr($optionSub['name']) . '</span></label></li>'; // phpcs:ignore
                    }
                    ?>
                </ul>
                <div class="clear"></div>
                <?php
            }
        }
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function init_form_fields()
    {
        parent::init_form_fields();
        $optionId = $this->getOptionId();
        if (PPMFWC_Helper_Data::isOptionAvailable($optionId)) {
            $default = get_option('paynl_bankselection');
            if (empty($default)) {
                $default = 'select';
            }
            $this->form_fields['paynl_bankselection'] = array(
                    'title' => esc_html(__('Bankselection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type' => 'select',
                    'options' => array('none' => esc_html(__('No bankselection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                          'select' => esc_html(__('Selectbox', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                          'radio' => esc_html(__('Radiobuttons', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                        ),

             'description' => esc_html(__('Pick the type of bankselection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
             'default' => $default,);
        }
    }
}
