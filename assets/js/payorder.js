jQuery(function ($) {
  function addButton () {
    if ($('#paynl-pin-refund').length) {
      return
    }
    $('.refund-actions').prepend('<button type="button" id="paynl-pin-refund" class="button button-primary do-api-pin-refund">Refund <span class="wc-order-refund-amount"><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">€</span>&nbsp;0,00</span></span> via Pay. - Pin</button>')
    $('#paynl-pin-refund').click(function () {
      submitPinRefund()
    })
    var options = ''
    paynl_order.terminals.forEach((terminal) => {
      options += '<option value="' + terminal.id + '">' + terminal.name + '</option>'
    })
    $('.wc-order-refund-items table.wc-order-totals').append('<tr><td class="label"><label for="pin_terminal"> Pin terminal (optional): </label></td><td class="total"><select style="margin: 0px 5px;box-shadow: 0 0 0 transparent;border-radius: 4px;border: 1px solid #8c8f94;background-color: #fff;color: #2c3338;padding: 0 8px;line-height: 2;min-height: 30px;width: 96%;float: right;vertical-align: middle;position: relative;left: 4px;" type="text" id="pin_terminal" name="pin_terminal">' + options + '</select><div class="clear"></div></td></tr>')
  }

  $(document).ready(function () {
    addButton()
  })

  $(document).ajaxComplete(function () {
    addButton()
  })

  function submitPinRefund () {
    var amount = $('#refund_amount').val()
    if (amount == 0 || amount.length == 0) {
      alert(paynl_order.texts.i18n_refund_error_zero)
      return
    }

    if (amount > paynl_order.max_amount) {
      alert(paynl_order.texts.i18n_refund_invalid)
      return
    }

    var terminal = $('#pin_terminal').val()

    var ajaxurl = '/?wc-api=Wc_Pay_Gateway_Pinrefund'
    var data = {
      'amount': amount,
      'terminal': terminal,
      'order_id': paynl_order.order_id,
      'returnUrl': window.location.href
    }

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: data,
      dataType: 'json',
      success: function (data) {
        if (data.success) {
          window.location.href = data.url
        } else {
          alert(paynl_order.texts.i18n_refund_error)
        }
      },
      error: function () {
        alert(paynl_order.texts.i18n_refund_error)
      }
    })
  }
})
