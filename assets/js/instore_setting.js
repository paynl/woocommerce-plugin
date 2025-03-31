jQuery(document).ready(function () {
    jQuery('#woocommerce_pay_gateway_instore_paynl_instore_pickup_location').on('change', toggleTdVisibility);
    toggleTdVisibility();
});

function toggleTdVisibility() {
    const pickupLocation = jQuery('#woocommerce_pay_gateway_instore_paynl_instore_pickup_location').val();
    const pickupLocationTerminal = jQuery('#woocommerce_pay_gateway_instore_paynl_instore_pickup_location_terminal').parents().eq(2);

    if (pickupLocation == 'direct') {
        pickupLocationTerminal.hide();
    } else {
        pickupLocationTerminal.show();
    }
}