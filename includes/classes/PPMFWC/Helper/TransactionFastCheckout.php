<?php

class PPMFWC_Helper_TransactionFastCheckout
{ 
    
    /**
     * @return array
     */
    public function getData()
    {
        $parameters = [
            'serviceId' => get_option('paynl_serviceid'),
            'amount' => [
                'value' => 10,
                'currency' => "EUR",
            ],
        ];

        $parameters['paymentMethod'] = ['id' => 10];

        $this->_add($parameters, 'returnUrl', 'test');
        $this->_add($parameters, 'description', '');
        $this->_add($parameters, 'reference', "123456");
        $this->_add($parameters, 'exchangeUrl', 'test');

        $parameters['integration']['test'] = true;

        $optimize['flow'] = 'fastCheckout';
        $optimize['shippingAddress'] = true;
        $optimize['billingAddress'] = true;
        $optimize['contactDetails'] = true;

        $this->_add($parameters, 'optimize', $optimize);

        $orderParameters = array();
        $invoiceAddress = array();
        $productData = array();

        $this->_add($orderParameters, 'products', array());
        $this->_add($parameters, 'order', $orderParameters);

        $stats = array();
        $this->_add($stats, 'info', '');
        $this->_add($stats, 'tool', '');
        $this->_add($stats, 'object', 'fc');
        $this->_add($stats, 'extra1', '');
        $this->_add($stats, 'extra2', '');
        $this->_add($stats, 'extra3', "123456");
        $this->_add($parameters, 'stats', $stats);

        return $parameters;
    }

    /**
     * @param array $returnArr
     * @param string $field
     * @param string $value
     * @return void
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     */
    private function _add(&$returnArr, $field, $value) // phpcs:ignore
    {
        if (!empty($value)) {
            $returnArr = array_merge($returnArr, [$field => $value]);
        }
    }

    /**
     * @return array
     */
    private function getProductData()
    {
        $arrProducts = array();

        foreach ($this->products as $i => $arrProduct) {
            $product = array();
            $product['id'] = $arrProduct['id'] ?? 'p' . $i;
            $product['description'] = $arrProduct['description'] ?? '';
            $product['type'] = $arrProduct['type'] ?? '';
            $product['price'] = [
                'value' => $arrProduct['price'],
                'currency' => $arrProduct['currecny'],
            ];
            $product['quantity'] = $arrProduct['quantity'] ?? 0;
            $product['vatPercentage'] = $arrProduct['vatPercentage'] ?? '';
            $arrProducts[] = $product;
        }

        return $arrProducts;
    }

    /**
     * @return \Paynl\Result\Transaction\Start
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public function create()
    {
        $payload = $this->getData();

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
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
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

}
