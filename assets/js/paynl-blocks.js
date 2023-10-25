const PaynlComponent = (props) =>{
    let {image_path, useEffect, gateway} = props
    const [ selectedIssuer, selectIssuer ] = wp.element.useState('');
    const [ cocNumber, setCocNumber ] = wp.element.useState('');
    const [ vatNumber, setVatNumber ] = wp.element.useState('');
    const [ dob, selectDate ] = wp.element.useState('');
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;
    let payIssuers = gateway.issuers;

    useEffect(() => {
        const unsubscribe = onPaymentSetup(() => {
            let response = {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {},
            };

            let paymentMethodData = {};
            paymentMethodData['selectedIssuer'] = selectedIssuer;
            paymentMethodData['vat_number'] = vatNumber;
            paymentMethodData['coc_number'] = cocNumber;
            paymentMethodData[gateway.paymentMethodId + '_birthdate'] = dob;
            response.meta.paymentMethodData = paymentMethodData;
            return response;
        });
        return () => {
            unsubscribe();
        };
    }, [onPaymentSetup, selectedIssuer, dob, cocNumber, vatNumber]);

        return React.createElement('div', {className: 'PPMFWC_container'},
                React.createElement('img', {src: image_path}, null),
                React.createElement('span', {className: 'description'}, gateway.description),
                React.createElement('div', {},
                    (gateway.paymentMethodId == 'pay_gateway_ideal' ?
                        React.createElement('select',  {onChange: (e)=>{
                                    selectIssuer(e.target.value)
                                }},
                            React.createElement("option", {}, gateway.texts.selectissuer),
                            ...payIssuers.map(issuer => React.createElement("option", {value: issuer.option_sub_id}, issuer.name))
                        ) : ''),
                    (gateway.showbirthdate == true ?
                        React.createElement('div', {className: 'field'},
                                 React.createElement('span', {}, gateway.texts.enterbirthdate + ': '),
                                 React.createElement('input', {type: 'date', onChange: (e)=>{ selectDate(e.target.value) }})
                        ) : '' ),
                    (gateway.showCocField == true ?
                    React.createElement('div', {className: 'field'},
                        React.createElement('span', {}, gateway.texts.enterCocNumber + ': '),
                        React.createElement('input', {type: 'text', onChange: (e)=>{ setCocNumber(e.target.value) }})
                        ) : '' ),
                    ( gateway.showVatField == true ?
                        React.createElement('div', {className: 'field'},
                            React.createElement('span', {}, gateway.texts.enterVatNumber + ': '),
                            React.createElement('input', {type: 'text', onChange: (e)=>{ setVatNumber(e.target.value) }})
                        ) : '' )
        ))

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