jQuery(document).ready(function () {
    jQuery(".woocommerce-billing-fields__field-wrapper").find('input[name*="company"]').on("blur", function (event) {
        jQuery.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: jQuery('form.checkout').serialize() + '&updatecustomer=true',
            dataType: 'json',
            success: function (result) {
                jQuery(document.body).trigger('update_checkout');
            }
        });
    });
});