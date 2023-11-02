const PaynlComponent = (props) =>{
    let {image_path, useEffect, gateway} = props
    const [ selectedIssuer, selectIssuer ] = wp.element.useState('');
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;
    useEffect(() => {
        const unsubscribe = onPaymentSetup(() => {
            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        selectedIssuer: selectedIssuer
                    },
                },
            };
        });
        return () => {
            unsubscribe();
        };
    }, [
        onPaymentSetup, selectedIssuer
    ]);

    let i = gateway.issuers;

    if (gateway.paymentMethodId == 'pay_gateway_ideal') {
        return React.createElement('div', {className: 'PPMFWC_desc'},
                React.createElement('img', {src: image_path}, null),
                React.createElement('span', {}, gateway.description),
                React.createElement('div', {},
                React.createElement('select',  {onChange: (e)=>{
                        selectIssuer(e.target.value)
                    }},
                    React.createElement("option", {}, gateway.text_selectissuer),
                    ...i.map(issuer => React.createElement("option", {value: issuer.option_sub_id}, issuer.name))
            )
        ))
    }

    return  React.createElement('div', {className: 'PPMFWC_desc'},
                React.createElement('img', {src: image_path}, null),
                React.createElement('span', {}, gateway.description),
    );
}

function PaynlLabel({image_path, title})
{
    return React.createElement('div', {className: 'PPMFWC_method'},
        title, React.createElement('img', {src: image_path, style: {float: 'right'}}, null));
}

const registerPaynlPaymentMethods = ({wc, paynl_gateways}) => {
    const {registerPaymentMethod} = wc.wcBlocksRegistry;
    const {useEffect} = wp.element;
    paynl_gateways.forEach(
        (gateway) => {
            registerPaymentMethod(createOptions(gateway, PaynlComponent, useEffect));
        }
    );
}

const createOptions = (gateway, PaynlComponent, useEffect) => {
    return {
        name: gateway.paymentMethodId,
        label: React.createElement(PaynlLabel, {image_path: gateway.image_path, title: gateway.title}),
        paymentMethodId: gateway.paymentMethodId,
        edit: React.createElement('div', null, ''),
        canMakePayment: ({cartTotals, billingData}) => {
            return true
        },
        ariaLabel: gateway.title,
        content: React.createElement(PaynlComponent, {gateway: gateway, image_path: gateway.image_path, useEffect: useEffect})
    }
}

registerPaynlPaymentMethods(window)