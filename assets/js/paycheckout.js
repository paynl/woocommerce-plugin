jQuery(function ($) {
    $(window).on('load', function () {              
        $(".woocommerce-billing-fields__field-wrapper").find('input').change(function () {       
            $.ajax({
                type:		'POST',
                url:		wc_checkout_params.checkout_url,
                data:		$('form.checkout').serialize() + '&updatecustomer=true',
                dataType:   'json',
                success:	function( result ) {                  
                    console.log(result); 
                    $(document.body).trigger('update_checkout');
                }
            });
        }); 
    });
});