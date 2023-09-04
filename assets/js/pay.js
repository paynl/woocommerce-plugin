jQuery(document).ready(function () {
    if (jQuery("#pay_feature_request_form").length) {        
        jQuery('.woocommerce-save-button').hide();
        jQuery("#FR_Submit").click(function (event) {
            event.preventDefault();            
            submitFeatureRequestForm();
            return false;
        });
    }
    if (jQuery("#paynl_payment_methods-description").length) {        
        jQuery('.woocommerce-save-button').hide();      
    }
});


function submitFeatureRequestForm() {    
    jQuery('#email_error').hide();
    jQuery('#message_error').hide();
    var email = jQuery('#FR_Email').val();
    var message = jQuery('#FR_Message').val();  
    
    var regex = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
    if(jQuery.trim(message) == '' || (jQuery.trim(email) != '' && !regex.test(jQuery('#FR_Email').val()))){        
        if(jQuery.trim(email) != '' && !regex.test(jQuery('#FR_Email').val())){
            jQuery('#email_error').css('display', 'inline');
        }
        if(jQuery.trim(message) == ''){
            jQuery('#message_error').css('display', 'inline');
        }
        return false;
    }

    var ajaxurl = '/?wc-api=Wc_Pay_Gateway_Featurerequest';        
    var data = {
        'email' : email,
        'message' : message
    };    

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (data) {        
            if (data.success) {
                jQuery('#FR_Email').val("");
                jQuery('#FR_Message').val("");
                alert('Sent! Thank you for your contribution.');
            } else {
                alert('Couldn\'t send email.');
            }
        },
        error: function () {                        
            alert('Couldn\'t send email....');
        }
    });   
};