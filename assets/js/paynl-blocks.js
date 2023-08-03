const PaynlComponent = React.createElement('h1', {}, 'description', React.createElement("div", {className: "blue"}, 'subDescription'));

const registerPaynlPaymentMethods = ({wc, paynl_gateways}) =>
{
    const {registerPaymentMethod} = wc.wcBlocksRegistry;

    paynl_gateways.forEach(
        (gateway) => {
            registerPaymentMethod(createOptions(gateway, PaynlComponent));
        }
    );
}


const createOptions = ( gateway , PaynlComponent) =>
{
    return {
        name: gateway.title,
        label: gateway.title,
        paymentMethodId: gateway.paymentMethodId,
        edit: React.createElement('div', null, ''),
        canMakePayment: ({cartTotals, billingData}) =>    {
            if(billingData.country != 'NL')
            {
                return false;
            }
            return true
        },
        ariaLabel: gateway.title,
        content: PaynlComponent
    }
}

registerPaynlPaymentMethods(window)