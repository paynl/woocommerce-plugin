<?php

/**
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 */

class PPMFWC_Gateway_Metjebank extends PPMFWC_Gateway_Abstract
{
    /**
     * @return string
     */
    public static function getId()
    {
        return 'pay_gateway_metjebank';
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return 'Metjebank.nl';
    }

    /**
     * @return integer
     */
    public static function getOptionId()
    {
        return 5358;
    }

    /**
     * @return array
     */
    public function getIssuers()
    {
        $issuers = PPMFWC_Helper_Data::getOptionSubs(self::getOptionId());
        foreach ($issuers as $key => $issuer) {
            $issuers[$key]['image_path'] = $issuer['image'];
        }
        return $issuers;
    }

    /**
     * @return string
     */
    public function getSelectionType()
    {
        return $this->get_option('paynl_bankselection');
    }

    /**
     * @phpcs:ignore Squiz.Commenting.FunctionComment.MissingReturn
     */
    public function payment_fields()
    {
        parent::payment_fields();

        $optionSubs = PPMFWC_Helper_Data::getOptionSubs($this->getOptionId());

        $selectionType = $this->get_option('paynl_bankselection');
        if (!empty($optionSubs) && $selectionType != 'none') {
            if ($selectionType == 'select') {
                ?>
                <fieldset>
                    <legend><?php echo esc_html(__('Pay safely via your bank', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></legend>
                    <select name="issuer_id" id="<?php echo esc_attr($this->getId() . '_issuer'); ?>">
                        <option value=""><?php echo esc_html(__('Select your bank...', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></option>
                        <?php
                        foreach ($optionSubs as $optionSub) {
                            echo '<option value="' . esc_attr($optionSub['issuer_id']) . '">' . esc_html($optionSub['name']) . '</option>';
                        }
                        ?>
                    </select>
                </fieldset>
            <?php } elseif ($selectionType == 'radio') {
                ?>
                <ul class="pay_radio_select">
                    <?php
                    foreach ($optionSubs as $optionSub) {
                        echo '<li><label><input type="radio" name="issuer_id" id="' . esc_attr($this->getId() . '_issuer_' . $optionSub['option_sub_id']) . '" value="' . esc_attr($optionSub['issuer_id']) . '" />&nbsp;<img src="' . esc_url($optionSub['image']) . '" alt="' . esc_attr($optionSub['name']) . '" title="' . esc_attr($optionSub['name']) . '" /><span>' . esc_attr($optionSub['name']) . '</span></label></li>'; // phpcs:ignore
                    }
                    ?>
                </ul>
                <div class="clear"></div>
                <?php
            }
        }
    }
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
                'title' => esc_html(__('Bank selection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'type' => 'select',
                'options' => array('none' => esc_html(__('No bank selection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'select' => esc_html(__('Select box', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'radio' => esc_html(__('Radio buttons', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                ),

                'description' => esc_html(__('Select the type of bank selection.', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                'default' => $default,
            );
        }
    }
}
