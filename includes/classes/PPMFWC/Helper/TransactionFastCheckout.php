<?php

/**
 * PPMFWC_Helper_TransactionFastCheckout
 *
 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 * @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName
 * @phpcs:disable PSR12.Properties.ConstantVisibility
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

class PPMFWC_Helper_TransactionFastCheckout
{
    /**
     * @param array $data
     * @param WC_Order $order
     * @return \Paynl\Result\Transaction\Start
     */
    public function getData($data, $order)
    {
        $parameters = [
            'serviceId' => get_option('paynl_serviceid'),
            'amount' => [
                'value' => $data['amount'] * 100,
                'currency' => $data['currency'],
            ],
        ];

        $parameters['paymentMethod'] = ['id' => $data['paymentMethod']];

        $returnUrl = add_query_arg(array('wc-api' => 'Wc_Pay_Gateway_Return'), home_url('/'));
        $exchangeUrl = add_query_arg('wc-api', 'Wc_Pay_Gateway_Exchange', home_url('/'));

        $strAlternativeExchangeUrl = self::getAlternativeExchangeUrl();
        if (!empty(trim($strAlternativeExchangeUrl))) {
            $exchangeUrl = $strAlternativeExchangeUrl;
        }

        $this->_add($parameters, 'returnUrl', $returnUrl);
        $this->_add($parameters, 'description', '');
        $this->_add($parameters, 'reference', 'fastcheckout');
        $this->_add($parameters, 'exchangeUrl', $exchangeUrl);

        $parameters['integration']['test'] = true;

        $optimize['flow'] = 'fastCheckout';
        $optimize['shippingAddress'] = true;
        $optimize['billingAddress'] = true;
        $optimize['contactDetails'] = true;

        $this->_add($parameters, 'optimize', $optimize);

        $orderParameters = array();
        $invoiceAddress = array();
        $productData = $this->getProductData($data['products']);

        $this->_add($orderParameters, 'products', $productData);
        $this->_add($parameters, 'order', $orderParameters);

        $stats = array();
        $this->_add($stats, 'info', '');
        $this->_add($stats, 'tool', '');
        $this->_add($stats, 'object', PPMFWC_Helper_Data::getObject() . ' | fc');
        $this->_add($stats, 'extra1', apply_filters('paynl-extra1', $order->get_order_number(), $order));
        $this->_add($stats, 'extra2', apply_filters('paynl-extra2', $order->get_billing_email(), $order));
        $this->_add($stats, 'extra3', apply_filters('paynl-extra3', $order_id, $order));
        $this->_add($parameters, 'stats', $stats);

        return $parameters;
    }

    /**
     * @param array $returnArr
     * @param string $field
     * @param string $value
     * @return void
     */
    private function _add(&$returnArr, $field, $value) // phpcs:ignore
    {
        if (!empty($value)) {
            $returnArr = array_merge($returnArr, [$field => $value]);
        }
    }

    /**
     * @param array $products 
     * @return array
     */
    private function getProductData($products)
    {
        $arrProducts = array();

        foreach ($products as $i => $arrProduct) {
            $product = array();
            $product['id'] = $arrProduct['id'] ?? 'p' . $i;
            $product['description'] = $arrProduct['name'] ?? '';
            $product['type'] = $arrProduct['type'] ?? '';
            $product['price'] = [
                'value' => $arrProduct['amount'] * 100,
                'currency' => $arrProduct['currency'],
            ];
            $product['quantity'] = $arrProduct['qty'] ?? 0;
            $product['vatPercentage'] = $arrProduct['taxPercentage'] ?? '';
            $arrProducts[] = $product;
        }

        return $arrProducts;
    }

    /**
     * @param array $data
     * @param WC_Order $order
     * @return \Paynl\Result\Transaction\Start
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public function create($data, $order)
    {
        $payload = $this->getData($data, $order);

        $payload = json_encode($payload);

        $url = 'https://connect.payments.nl/v1/orders';

        $rawResponse = (array) $this->sendCurlRequest($url, $payload, get_option('paynl_tokencode'), get_option('paynl_apitoken'));

        $response = array(
            'redirectURL' => $rawResponse['links']->redirect ?? '',
            'transactionId' => $rawResponse['orderId'] ?? ''
        );

        return $response;
    }

    /**
     * @param string $requestUrl
     * @param string $payload
     * @param string $tokenCode
     * @param string $apiToken
     * @param string $method
     * @return array
     * @throws \Exception
     */
    public function sendCurlRequest($requestUrl, $payload, $tokenCode, $apiToken, $method = 'POST')
    {
        $authorization = base64_encode($tokenCode . ':' . $apiToken);

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $requestUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "authorization: Basic " . $authorization,
                    "content-type: application/json",
                ],
            ]
        );

        $rawResponse = curl_exec($curl);
        $response = json_decode($rawResponse);

        $error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($error) {
            throw new \Exception($error);
        } elseif (!empty($response->violations)) {
            $field = $response->violations[0]->propertyPath ?? ($response->violations[0]->code ?? '');
            throw new \Exception($field . ': ' . ($response->violations[0]->message ?? ''));
        }

        return (array) $response;
    }

    /**
     * @return mixed|string|void
     */
    public static function getAlternativeExchangeUrl()
    {
        $strAltUrl = get_option('paynl_exchange_url');

        if (!empty($strAltUrl)) {
            return $strAltUrl;
        }

        return '';
    }
}
