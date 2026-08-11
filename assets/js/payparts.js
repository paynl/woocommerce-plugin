
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
                window.initPayment($, data.data.sessionToken);
            }
        }
    })
}

async function initPayment($, sessionToken) {

    if (!window.PayPartsSDK) {
        console.error('PayPartsSDK external script has not finished loading on the page.');
        return;
    }

    if (sessionToken === undefined || sessionToken === null) {
        console.error('Session token is not available.');
        return;
    }

    try {
        const checkout = await window.PayPartsSDK.init({
            sessionToken: sessionToken,
            apiUrl: 'https://zero-parts.pay.nl',
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
                var ajaxurl = '/?wc-api=Wc_Pay_Gateway_PaypartsOrderCreate';
                var data = { order_id: event.orderId };
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (data) {
                        console.log('Order Finalized', data);
                    }
                });
                // todo: update session with orderID, currently hasnt been made yet.
                event.resolve();
            },
            onSuccess: (event) => {
                //window.location.href = event.returnUrl . '?&transaction=' + event.orderId;
                event.resolve();
            },
            onError: (event) => {
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
        console.error('Failed to initialize PayParts checkout:', error);
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