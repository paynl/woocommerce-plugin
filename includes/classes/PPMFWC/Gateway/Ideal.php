<?php

class PPMFWC_Gateway_Ideal extends PPMFWC_Gateway_Abstract
{
    public static function getId()
    {
        return 'pay_gateway_ideal';
    }

    public static function getName()
    {
        return 'iDEAL';
    }

    public static function getOptionId()
    {
        return 10;
    }

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
                    <legend><?php echo  esc_html(__('Choose your bank', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)); ?></legend>
                    <select name="option_sub_id">
                        <option value="">-</option>
                        <?php
                        foreach ($optionSubs as $optionSub) {
                            echo '<option value="' . esc_attr($optionSub['option_sub_id']) . '">' . esc_html($optionSub['name']) . '</option>';
                        }
                        ?>
                    </select>
                </fieldset>   
            <?php } elseif ($selectionType == 'radio') {
                ?>
                <ul style="border:none;width:200px;list-style: none; margin:0; margin-top:20px;">
                    <?php
                    foreach ($optionSubs as $optionSub) {
                        echo '<li style="float: left; width: 200px;"><label><input type="radio" name="option_sub_id" value="' . esc_attr($optionSub['option_sub_id']) . '" />&nbsp;<img src="' . esc_attr($optionSub['image']) . '" alt="' . esc_attr($optionSub['name']) . '" title="' . esc_attr($optionSub['name']) . '" /></label></li>';
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
                $default = 'none';
            }
            $this->form_fields['paynl_bankselection'] = array(
                    'title' => esc_html(__('Bankselection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                    'type' => 'select',
                    'options' => array('none' => esc_html( __('No bankselection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                          'select' => esc_html(__('Selectbox', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
                          'radio' => esc_html( __('Radiobuttons', PPMFWC_WOOCOMMERCE_TEXTDOMAIN))
                        ),

             'description' => esc_html( __('Pick the type of bankselection', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)),
             'default' => $default,);
        }
    }
}
