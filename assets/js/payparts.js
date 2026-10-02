
async function initSession($) {
    var ajaxurl = '/?wc-api=Wc_Pay_Gateway_PaypartsSession';
    var data = {};
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                window.initPayment($, data.data.sessionToken, data.data.sessionId);
            }
        }
    })
}

async function initPayment($, sessionToken, sessionId) {

    if (!window.PayPartsSDK) {
        return;
    }

    if (sessionToken === undefined || sessionToken === null) {
        return;
    }

    try {

        var woocommerceOrderId = null;
        var woocommerceOrderIdAdded = false;
        const checkout = await window.PayPartsSDK.init({
            sessionToken: sessionToken,
            apiUrl: 'https://parts.pay.nl',
            language: 'nl',
            country: 'NL',
            debugMode: false,
            creditCardOptions: {
                hideSubmitButton: true,
            }
        });

        checkout.events = {
            onReady: (event) => {
                event.resolve();
            },
            onSubmit: (event) => {
                var $checkoutForm = $('form.checkout');
                $checkoutForm.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });

                if (!woocommerceOrderIdAdded) {
                    var ajaxurl = '/?wc-api=Wc_Pay_Gateway_PaypartsOrderCreate';
                    var data = { sessionId: sessionId };
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function (data) {
                            woocommerceOrderId = data.order_id;
                            woocommerceOrderIdAdded = true;
                            event.resolve({ reference: woocommerceOrderId.toString() });
                        },
                        error: function (data) {
                            event.reject(new Error('Order creation failed'));
                        }
                    });
                } else {
                    event.resolve();
                }
            },
            onSuccess: (event) => {
                window.location.href = event.returnUrl + '&id=' + event.orderId + '&orderId=' + woocommerceOrderId + '&statusCode=' + event.paymentStatus.action + '&orderStatusId=' + event.paymentStatus.code;
                event.resolve();
            },
            onError: (event) => {
                $('form.checkout').unblock();
                event.resolve();
            }
        };

        const cardComponent = checkout.prepare('card-payments');
        await cardComponent.bind('#card-section');

        var targetMethod = 'pay_gateway_creditcardsgrouped';
        $(document.body).off('click', '#place_order');
        $(document.body).on('click', '#place_order', function (e) {
            var selectedMethod = $('input[name="payment_method"]:checked').val();
            if (selectedMethod === targetMethod) {
                e.preventDefault();
                e.stopImmediatePropagation();
                cardComponent.submit();
                return false;
            }
        });

    } catch (error) {
        return;
    }
}

window.initSession = initSession;
window.initPayment = initPayment;

jQuery(function ($) {
    $(document).ready(function () {
        initSession($);
        $(document.body).on('updated_checkout', function (event, data) {
            initSession($);
        });
    });
});
