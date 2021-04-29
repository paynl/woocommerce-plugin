<?php

class PPMFWC_Gateway_Yehhpay extends PPMFWC_Gateway_Abstract
{

  public static function getId()
  {
    return 'pay_gateway_yehhpay';
  }

  public static function getName()
  {
    return 'Yehhpay';
  }

  public static function getOptionId()
  {
    return 1877;
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
      echo esc_html(__('Birthdate: ', PPMFWC_WOOCOMMERCE_TEXTDOMAIN)) . ' <input name="birthdate_yehhpay" id="birthdate_yehhpay">';

      $js = 'jQuery( "#birthdate_yehhpay" ).css("width","125px").datepicker({ changeMonth: true, changeYear: true, yearRange:"-100:+0", dateFormat: "dd-mm-yy" });';
        wp_enqueue_style('jquery-ui', PPMFWC_PLUGIN_URL . 'assets/css/jquery-ui.min.css');
        wp_enqueue_script('jquery-ui-datepicker');

        if ($required == 'yes') {
            $js .= 'jQuery("#place_order").click(function(e){if (!jQuery("#birthdate_yehhpay").val() && jQuery("input[name=payment_method]:checked").val() == "pay_gateway_yehhpay"){e.preventDefault();alert("Please select a date of birth before finishing the transaction!");}})';
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
