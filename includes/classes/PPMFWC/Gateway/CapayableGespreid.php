<?php

class PPMFWC_Gateway_CapayableGespreid extends PPMFWC_Gateway_Abstract
{

    public static function getId()
    {
        return 'pay_gateway_capayablegespreid';
    }

    public static function getName()
    {
        return 'in3 keer betalen, 0% rente';
    }

    public static function getOptionId()
    {
        return 1813;
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
            echo esc_html(__('Birthdate: ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ' <input name="birthdate_capayble_gespreid" id="birthdate_capayble_gespreid"></input>';

            $js = 'jQuery( "#birthdate_capayble_gespreid" ).css("width","125px").datepicker({ changeMonth: true, changeYear: true, yearRange:"-100:+0", dateFormat: "dd-mm-yy" });';
            wp_enqueue_style('jquery-ui', PPMFWC_PLUGIN_URL . 'assets/css/jquery-ui.min.css');
            wp_enqueue_script('jquery-ui-datepicker');

            if ($required == 'yes') {
                $js .= 'jQuery("#place_order").click(function(e){if (!jQuery("#birthdate_capayble_gespreid").val() && jQuery("input[name=payment_method]:checked").val() == "pay_gateway_capayble_gespreid"){e.preventDefault();alert("Please select a date of birth before finishing the transaction!");}})';
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