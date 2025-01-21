jQuery(document).ready(function () {
    jQuery('.pay-fast-checkout-product .pay-fast-checkout').click(function (e) {
        e.preventDefault();
        if (jQuery(this).hasClass('fast-checkout-product-modal')) {
            return false;
        }
        window.location.href = "/?wc-api=Wc_Pay_Gateway_Fccreate&source=product&product_id=" + jQuery('input[name="fast-checkout-product-id"]').val() + "&variation_id=" + jQuery('input[name="variation_id"]').val() + "&quantity=" + jQuery('input[name="quantity"]').val();
    });

    jQuery('.fast-checkout-product-modal').click(function (e) {
        e.preventDefault();

        toggleModal("/?wc-api=Wc_Pay_Gateway_Fccreate&source=product&product_id=" + jQuery('input[name="fast-checkout-product-id"]').val() + "&variation_id=" + jQuery('input[name="variation_id"]').val() + "&quantity=" + jQuery('input[name="quantity"]').val())

        return false;
    });
});

function toggleModal(url) {
    jQuery('#modal-backdrop').removeClass('invisible');
    jQuery('#modal-backdrop').addClass('visible');

    jQuery('#fast-checkout-modal').removeClass('invisible');
    jQuery('#fast-checkout-modal').addClass('visible');

    jQuery('#fast-checkout-modal .button-primary').unbind();
    jQuery('#fast-checkout-modal .button-primary').click(function () {
        window.location.href = url;
    });

    document.body.style.overflow = 'hidden';
}

function closeModal() {
    jQuery('#modal-backdrop').removeClass('visible');
    jQuery('#modal-backdrop').addClass('invisible');

    jQuery('#fast-checkout-modal').removeClass('visible');
    jQuery('#fast-checkout-modal').addClass('invisible');

    document.body.style.overflow = '';
}