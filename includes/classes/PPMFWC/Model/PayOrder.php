<?php

class PPMFWC_Model_PayOrder
{
    /**
     * @var array
     */
    private $data;

    public function __construct(array $responseData)
    {
        $this->data = json_decode(json_encode($responseData), true);
        $this->data['paymentDetails']['state'] = $this->data['status']['code'] ?? '';
    }

    /**
     * @return mixed|null
     */
    public function getCurrencyAmount()
    {
        return $this->data['amount']['currency'] ?? null;
    }

    /**
     * @return float|null
     */
    public function getAmountValue()
    {
        return isset($this->data['amount']['value'])
            ? (float)$this->data['amount']['value']
            : null;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return null
     */
    public function getPaidCurrencyAmount()
    {
        return null;
    }

    /**
     * @return float|null
     */
    public function getPaidAmount()
    {
        if (isset($this->data['amount']['value'])) {
            return (float)$this->data['amount']['value'] / 100;
        }

        return null;
    }

    /**
     * Retrieves the checkout data from the data array.
     *
     * @return array The checkout data, or an empty array if not set.
     */
    public function getCheckoutData()
    {
        return $this->data['checkoutData'] ?? [];
    }

    /**
     * @return string
     */
    public function getAccountNumber()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getAccountHolderName()
    {
        return '';
    }

}
