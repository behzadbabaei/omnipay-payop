<?php

declare(strict_types=1);

namespace Omnipay\Payop\Message;

use function array_merge;
use function json_encode;

/**
 * Class PurchaseRequest
 *
 * @package Omnipay\Payop\Message
 */
class PurchaseRequest extends AbstractRequest
{
    /**
     * Sets the request email.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setEmail($value)
    {
        return $this->setParameter('email', $value);
    }

    /**
     * Get the request email.
     *
     * @return mixed
     */
    public function getEmail()
    {
        return $this->getParameter('email');
    }

    /**
     * Sets the request productName.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setProductName($value)
    {
        return $this->setParameter('productName', $value);
    }

    /**
     * Get the request productName.
     *
     * @return mixed
     */
    public function getProductName()
    {
        return $this->getParameter('productName');
    }

    /**
     * Set the request payer.
     *
     * @param array $value
     *
     * @return $this
     */
    public function setPayer(array $value)
    {
        return $this->setParameter('payer', $value);
    }

    /**
     * Get the request payer.
     *
     * @return mixed
     */
    public function getPayer()
    {
        return $this->getParameter('payer', []) ?? [];
    }

    /**
     * Prepare the data for creating the order.
     *
     *
     *
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        $this->validate(
            'currency',
            'amount',
            'transactionId',
            'currency',
            'description',
            'returnUrl',
            'cancelUrl',
            'language'
        );

        $parameters = [
            'publicKey' => $this->getPublicKey(),
            'order' => [
                'id' => $this->getTransactionId(),
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency(),
                'items' => [
                    'name' => $this->getProductName(),
                    'price' => $this->getAmount(),
                    'currency' => $this->getCurrency(),
                ],
                'description' => $this->getDescription(),
            ],
            'payer' => $this->getPayer(),
            'language' => $this->getLanguage(),
            'resultUrl' => $this->getReturnUrl(),
            'failPath' => $this->getCancelUrl(),
            'signature' => $this->signature(),
        ];

        if ($this->getPaymentMethod() != '') {
            $parameters['paymentMethod'] = $this->getPaymentMethod();
        }

        return array_merge($this->getCustomData(), $parameters);
    }

    /**
     * Send data and return response instance.
     *
     * @param mixed $body
     *
     * @return mixed
     */
    public function sendData($body)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $httpResponse = $this->httpClient->request(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            $headers,
            json_encode($body)
        );

        return $this->createResponse($httpResponse->getBody()->getContents(), $httpResponse->getHeaders());
    }

    /**
     * @param       $data
     * @param array $headers
     *
     * @return Response
     */
    protected function createResponse($data, $headers = []): Response
    {
        return $this->response = new Response($this, $data, $headers);
    }

    /**
     * Get the order create endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->getUrl() . 'invoices/create';
    }
}
