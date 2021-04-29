<?php

class PPMFWC_Gateway_Afterpay extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_afterpay';
    }

    public static function getName()
    {
        return 'AfterPay';
    }

    public static function getOptionId()
    {
        return 739;
    }

    public static function showAuthorizeSetting()
    {
        return true;
    }

    public static function showDOB()
    {
        return true;
    }

    public function payment_fields()
    {
        parent::payment_fields();
        $ask_birthdate = $this->get_option('ask_birthdate');
        $required = $this->get_option('birthdate_required');

        if ($ask_birthdate == 'yes') {
            echo esc_html(__('Birthdate: ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . '<input name="birthdate_afterpay" id="birthdate_afterpay">';

            $js = 'jQuery("#birthdate_afterpay").css("width","125px").datepicker({ changeMonth: true, changeYear: true, yearRange:"-100:+0", dateFormat: "dd-mm-yy"});';
            wp_enqueue_style('jquery-ui', PPMFWC_PLUGIN_URL . 'assets/css/jquery-ui.min.css');
            wp_enqueue_script('jquery-ui-datepicker');

            if ($required == 'yes') {
                $js .= 'jQuery("#place_order").click(function(e){if (!jQuery("#birthdate_afterpay").val() && jQuery("input[name=payment_method]:checked").val() == "pay_gateway_afterpay"){e.preventDefault();alert("Please select a date of birth before finishing the transaction!");}})';
            }

            echo "
    <script type='text/javascript'>
        jQuery(document).ready(function(){
            " . $js . "
        });
    </script>
    ";
        }
    }


}