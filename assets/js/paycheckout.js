jQuery(function ($) {
    currentValues = {};
    $(window).on('load', function () {
        $(".woocommerce-billing-fields__field-wrapper").find('input').on("keyup change", function (event) {
            refresh = true;
            id = $(this).attr('id');
            value = $(this).val()
            if (id in currentValues && event.type == 'keyup') {
                if (
                    (currentValues[id].length > 0 && value.length > 0) ||
                    (currentValues[id].length == 0 && value.length == 0)
                ) {
                    refresh = false;
                }
            }
            if (refresh) {
                $.ajax({
                    type: 'POST',
                    url: wc_checkout_params.checkout_url,
                    data: $('form.checkout').serialize() + '&updatecustomer=true',
                    dataType: 'json',
                    success: function (result) {
                        $(document.body).trigger('update_checkout');
                        currentValues[id] = value;
                    }
                });
            }

        });
    });
});