<?php

class Pay_Gateway_Ideal extends Pay_Gateway_Abstract
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

    //velden bankselectie ideal
    $optionSubs = Pay_Helper_Data::getOptionSubs($this->getOptionId());

    $selectionType = $this->get_option('paynl_bankselection');
    if (!empty($optionSubs) && $selectionType != 'none')
    {
      if ($selectionType == 'select')
      {
        ?>
        <p>
          <select name="option_sub_id">
              <option value=""><?php echo __('Choose your bank', PAYNL_WOOCOMMERCE_TEXTDOMAIN) ?></option>
            <?php
            foreach ($optionSubs as $optionSub)
            {
              echo '<option value="' . $optionSub['option_sub_id'] . '">' . $optionSub['name'] . '</option>';
            }
            ?>
          </select>
        </p>
      <?php } elseif ($selectionType == 'radio')
      {
        ?>
        <ul style="border:none;width:400px;list-style: none;">
          <?php
          foreach ($optionSubs as $optionSub)
          {
            echo '<li style="float: left; width: 200px;"><label><input type="radio" name="option_sub_id" value="' . $optionSub['option_sub_id'] . '" />&nbsp;<img src="' . $optionSub['image'] . '" alt="' . $optionSub['name'] . '" title="' . $optionSub['name'] . '" /></label></li>';
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
    if (Pay_Helper_Data::isOptionAvailable($optionId))
    {
      $default = get_option('paynl_bankselection');
      if(empty($default)) $default = 'none';
      $this->form_fields['paynl_bankselection'] = array(
        'title' => __('Bankselection', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
        'type' => 'select',
        'options' => array(
          'none' => __('No bankselection', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
          'select' => __('Selectbox', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
          'radio' => __('Radiobuttons', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
        ),
        'description' => __('Pick the type of bankselection', PAYNL_WOOCOMMERCE_TEXTDOMAIN),
        'default' => $default,
      );
    }
  }
}
