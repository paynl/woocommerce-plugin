function PaynlComponent({image_path, desc}) {
    return React.createElement('div', {className: 'PPMFWC_desc'},
        React.createElement('img', {src: image_path}, null),
        React.createElement('span', {}, desc),
    );
}

function PaynlLabel({image_path, title}) {
    return React.createElement('div', {className: 'PPMFWC_method'},
        title, React.createElement('img', {src: image_path, style: {float: 'right'}}, null),);
}

const registerPaynlPaymentMethods = ({wc, paynl_gateways}) => {
    const {registerPaymentMethod} = wc.wcBlocksRegistry;
    paynl_gateways.forEach(
        (gateway) => {
            registerPaymentMethod(createOptions(gateway, PaynlComponent));
        }
    );
}

const createOptions = (gateway, PaynlComponent) => {
    return {
        name: gateway.paymentMethodId,
        label: React.createElement(PaynlLabel, {image_path: gateway.image_path, title: gateway.title}),
        paymentMethodId: gateway.paymentMethodId,
        edit: React.createElement('div', null, ''),
        canMakePayment: ({cartTotals, billingData}) => {
            return true
        },
        ariaLabel: gateway.title,
        content: React.createElement(PaynlComponent, {image_path: gateway.image_path, desc: gateway.description})
    }
}

registerPaynlPaymentMethods(window)