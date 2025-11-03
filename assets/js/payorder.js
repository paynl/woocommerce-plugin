jQuery(function ($) {
    $(document).ready(function () {
        addButton($);
        addPinButton($);
    });
    $(document).ajaxComplete(function () {
        addButton($);
        addPinButton($);
    });
});

function submitPinRefund($) {
    var amount = parseFloat($('#refund_amount').val().toString().replace(',', '.'));
    if (amount == 0) {
        alert(paynl_order.texts.i18n_refund_error_zero);
        return;
    }

    if (amount > paynl_order.max_amount) {
        alert(paynl_order.texts.i18n_refund_invalid);
        return;
    }

    doAjaxRequest($, amount, 'refund')
}

function submitPinTransaction($)
{
    var amount = parseFloat(paynl_order.order_total.toString().replace(',', '.'));
    if (amount == 0) {
        alert(paynl_order.texts.i18n_pinmoment_error_zero);
        return;
    }
    doAjaxRequest($, amount, 'pinmoment')
}

function doAjaxRequest($, amount, type)
{
    var terminal = $('#pin_terminal').val();
    var ajaxurl = '/?wc-api=Wc_Pay_Gateway_Pinrefund';
    var data = {
        'amount': amount,
        'terminal': terminal,
        'order_id': paynl_order.order_id,
        'returnUrl': window.location.href,
        'type': type
    };

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                window.location.href = data.url;
            } else {
                alert(type === 'refund' ? paynl_order.texts.i18n_refund_error : paynl_order.texts.i18n_pinmoment_error);
            }
        },
        error: function () {
            alert(type === 'refund' ? paynl_order.texts.i18n_refund_error : paynl_order.texts.i18n_pinmoment_error);
        }
    })
}

/**
 * Button for: retour-pin
 */
function addButton($) {
    let buttonPlaced = $('#paynl-pin-refund').length > 0;
    paynl_order.max_amount = parseFloat(paynl_order.max_amount);
    if (!buttonPlaced && paynl_order.max_amount > 0) {
        $('.refund-actions').prepend('<button style="background-color:#5b98c9;" type="button" id="paynl-pin-refund" class="button button-primary do-api-pin-refund">' + paynl_order.texts.i18n_refund_title + ' <span class="wc-order-refund-amount"><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">€</span>&nbsp;0,00</span></span> ' + paynl_order.texts.i18n_retourpin_title + '</button>');
        $('#paynl-pin-refund').click(function () {
            submitPinRefund($);
        })
        $('.do-api-refund').html(paynl_order.texts.i18n_refund_title + ' <span class="wc-order-refund-amount"><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">€</span>&nbsp;0,00</span></span> ' + paynl_order.texts.i18n_api_title);

        var options = getTerminalOptions($)
        $('.wc-order-refund-items table.wc-order-totals').append('<tr><td class="label"><label for="pin_terminal"><span class="woocommerce-help-tip" tabindex="0" aria-label="Note: the refund reason will be visible by the customer."></span> Pin terminal (optional): </label></td><td class="total"><select style="margin: 0px 5px;box-shadow: 0 0 0 transparent;border-radius: 4px;border: 1px solid #8c8f94;background-color: #fff;color: #2c3338;padding: 0 8px;line-height: 2;min-height: 30px;width: 96%;float: right;vertical-align: middle;position: relative;left: 4px;" type="text" id="pin_terminal" name="pin_terminal">' + options + '</select><div class="clear"></div></td></tr>');
    }
}


/**
 * Button for payment an order by "pin" from out the order
 * @param $
 */
function addPinButton($)
{
    let buttonPlaced = $('#paynl-pin-transaction').length > 0;
    paynl_order.order_total = parseFloat(paynl_order.order_total);

    if (!buttonPlaced && paynl_order.order_total > 0) {
        $('.wc-order-bulk-actions').prepend('<button style="background-color:#2271B1;;float: right; margin-left: 5px;" type="button" id="paynl-pin-transaction" class="button button-primary do-api-pin-transaction">' + paynl_order.texts.i18n_pinmoment_title + '</button>');
        $('#paynl-pin-transaction').click(function () {
            submitPinTransaction($);
        })
        var options = getTerminalOptions($)
        $('.wc-order-totals-items').append('<tr style="float:right;"><td class="label"><label for="pin_terminal"></label></span> Pin terminal (optional): </label></td><td class="total"><select style="margin: 0px 5px;box-shadow: 0 0 0 transparent;border-radius: 4px;border: 1px solid #8c8f94;background-color: #fff;color: #2c3338;padding: 0 8px;line-height: 2;min-height: 30px;width: 96%;float: right;vertical-align: middle;position: relative;left: 4px;top: 12px;" type="text" id="pin_terminal" name="pin_terminal">' + options + '</select><div class="clear"></div></td></tr>');
    }
}

function getTerminalOptions($) {
    var options = '';
    paynl_order.terminals.forEach((element) => {
        var selected = '';
        if (element.code == paynl_order.default_terminal) {
            selected = 'selected="selected"';
        }
        options += '<option value="' + element.code + '" ' + selected + '>' + element.name + '</option>';
    })

    return options;
}