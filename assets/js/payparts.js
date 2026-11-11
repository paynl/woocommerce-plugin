

async function initSession($)
{
    var cardSection = $('#card-section');
    if (!cardSection.length || cardSection.data('payparts-initialized')) {
        return;
    }
    cardSection.data('payparts-initialized', true);

    console.info('initSession()');
    var data = {};
    var ajaxurl = '/?wc-api=Wc_Pay_Gateway_PaypartsSession';

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                window.initPayment($, data.data.sessionToken, data.nonce);
            } else {
                cardSection.removeData('payparts-initialized');
                console.error('Pay.: couldn\'t load pay parts: ' + data.error);
            }
        },
        error: function () {
            cardSection.removeData('payparts-initialized');
        }
    })
}

async function initPayment($, sessionToken, nonce)
{
    var cardSection = $('#card-section');

    console.info('initPayment()');

    if (!window.PayPartsSDK || sessionToken == null) {
        cardSection.removeData('payparts-initialized');
        return;
    }

    try {
        var woocommerceOrderId = null;
        var woocommerceOrderIdAdded = false;
        console.info('initPayment() -> init pay parts');
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
            onReady: (event) =>
            {
                console.log('initPayment onReady');
                event.resolve();
            },
            onSubmit: (event) =>
            {
                console.log('initPayment onSubmit');
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
                    var data = { nonce: nonce };
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: data,
                        dataType: 'json',
                        success: function (data) {
                            if (!data.success || !data.order_id || !data.session_id) {
                                $checkoutForm.unblock();
                                event.reject(new Error(data.error || 'Order creation failed'));
                                return;
                            }
                            woocommerceOrderId = data.order_id;
                            woocommerceOrderIdAdded = true;

                            event.resolve({ reference: data.session_id });
                        },
                        error: function (data) {
                            $checkoutForm.unblock();
                            event.reject(new Error('Order creation failed'));
                        }
                    });
                } else {
                    event.resolve();
                }
            },
            onSuccess: (event) => {
                console.log('initPayment onSuccess');
                window.location.href = event.returnUrl + '&id=' + event.orderId + '&orderId=' + woocommerceOrderId + '&statusCode=' + event.paymentStatus.action + '&orderStatusId=' + event.paymentStatus.code;
                event.resolve();
            },
            onError: (event) => {
                console.log('initPayment onError');
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
        cardSection.removeData('payparts-initialized');
        return;
    }
}

window.initSession = initSession;
window.initPayment = initPayment;

jQuery(function ($) {
    $(document).ready(function ()
    {
        console.log('doc ready - init session')
        $(document.body).on('updated_checkout payment_method_selected', function (event, data)
        {
            if ($('input[name="payment_method"]:checked').val() === 'pay_gateway_creditcardsgrouped') {
                initSession($);
            }
        });
    });
});
