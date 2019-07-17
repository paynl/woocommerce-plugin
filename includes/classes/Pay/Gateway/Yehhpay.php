<?php

class Pay_Gateway_Yehhpay extends Pay_Gateway_Abstract
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

  public function init_form_fields()
  {
    parent::init_form_fields();

    $this->form_fields['ask_birthdate'] = array(
      'title' => __('Ask birthdate', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
      'type' => 'checkbox',
      'description' => __('Ask the customer for his birthdate, this will fasten the checkout process', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
      'default' => 'yes'
    );

  }

  public function payment_fields()
  {
    parent::payment_fields();
    $ask_birthdate = $this->get_option('ask_birthdate');
    if ($ask_birthdate == 'yes')
    {
      echo __('Birthdate: ', PAYNL_WOOCOMMERCE_TEXTDOMAIN) . " <input name='birthdate' id='birthdate-yehhpay' ></input>";

      $js = 'jQuery( "#birthdate-yehhpay" ).css("width","125px").datepicker({ changeMonth: true, changeYear: true, yearRange:"-100:+0", dateFormat: "dd-mm-yy" });';
      wp_enqueue_style('jquery-ui', PAYNL_PLUGIN_URL . 'assets/jquery-ui/jquery-ui.min.css');
      wp_enqueue_script('jquery-ui', PAYNL_PLUGIN_URL . 'assets/jquery-ui/jquery-ui.min.js');

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