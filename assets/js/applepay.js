jQuery(document).ready(function () {
    if (window.ApplePaySession) {
        // Set a cookie so we can show apple pay
        document.cookie = "applePayAvailable=true;path=/";
        jQuery(document.body).trigger('update_checkout');
    } else {
        // Delete the cookie when apple pay not available;
        document.cookie = "applePayAvailable=false; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        jQuery(document.body).trigger('update_checkout');
    }
});