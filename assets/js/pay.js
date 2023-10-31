jQuery(document).ready(function () {
  if (jQuery('#pay_feature_request_form').length) {
    jQuery('.woocommerce-save-button').hide()
    jQuery('#FR_Submit').click(function (event) {
      event.preventDefault()
      submitFeatureRequestForm()
      return false
    })
    jQuery('.FR_Alertbox').each(function () {
      var alertbox = this
      jQuery(alertbox).click(function (e) {
        if (e.target != alertbox) return
        jQuery(alertbox).hide()
      })
      jQuery(alertbox).find('.FR_close_alert').click(function () {
        jQuery(alertbox).hide()
      })
    })
  }
  if (jQuery('#paynl_payment_methods-description').length) {
    jQuery('.woocommerce-save-button').hide()
  }

  jQuery('#paynl_verify_peer').closest('tr').hide()
  jQuery('#paynl_show_advanced_settings').click(function (e) {
    e.preventDefault()
    jQuery('#paynl_verify_peer').closest('tr').toggle()
  })
})

function submitFeatureRequestForm () {
  jQuery('#email_error').hide()
  jQuery('#message_error').hide()
  var email = jQuery('#FR_Email').val()
  var message = jQuery('#FR_Message').val()

  var regex = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i
  if (jQuery.trim(message) == '' || (jQuery.trim(email) != '' && !regex.test(jQuery('#FR_Email').val()))) {
    if (jQuery.trim(email) != '' && !regex.test(jQuery('#FR_Email').val())) {
      jQuery('#email_error').css('display', 'inline')
    }
    if (jQuery.trim(message) == '') {
      jQuery('#message_error').css('display', 'inline')
    }
    return false
  }

  var ajaxurl = '/?wc-api=Wc_Pay_Gateway_Featurerequest'
  var data = {
    'email': email,
    'message': message
  }

  jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: data,
    dataType: 'json',
    success: function (data) {
      if (data.success) {
        jQuery('#FR_Email').val('')
        jQuery('#FR_Message').val('')
        jQuery('#FR_Alert_Success').show()
      } else {
        jQuery('#FR_Alert_Fail').show()
      }
    },
    error: function () {
      jQuery('#FR_Alert_Fail').show()
    }
  })
}
