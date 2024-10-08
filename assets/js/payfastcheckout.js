jQuery(document).ready(function () {
    jQuery('.pay-fast-checkout-product .pay-fast-checkout').click(function (e) {
        e.preventDefault();   
        window.location.href = "/?wc-api=Wc_Pay_Gateway_Fccreate&source=product&product_id=" + jQuery('input[name="fast-checkout-product-id"]').val() + "&variation_id=" + jQuery('input[name="variation_id"]').val() + "&quantity=" + jQuery('input[name="quantity"]').val();
    });
});