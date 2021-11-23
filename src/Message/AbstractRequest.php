<?php

declare(strict_types=1);

namespace Omnipay\Payop\Message;

use Omnipay\Common\Message\AbstractRequest as BaseAbstractRequest;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Payop\Gateway;

/**
 * Class AbstractRequest
 *
 * @package Omnipay\Payop\Message
 */
abstract class AbstractRequest extends BaseAbstractRequest
{
    /**
     * Gateway production endpoint.
     *
     * @var string $prodEndpoint
     */
    protected $prodEndpoint = 'https://payop.com/v1/';

    /**
     * @var string $sandboxEndpoint
     */
    protected $sandboxEndpoint = 'https://payop.com/v1/';

    /**
     * @return string
     */
    abstract public function getEndpoint(): string;

    /**
     * @param mixed $data
     *
     * @return \Omnipay\Common\Message\ResponseInterface
     */
    abstract public function sendData($data);

    /**
     * Sets the request language.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    /**
     * Get the request language.
     *
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * Sets the request public key.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setPublicKey($value)
    {
        return $this->setParameter('publicKey', $value);
    }

    /**
     * Get the request public key.
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->getParameter('publicKey');
    }

    /**
     * Sets the request secret key.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }

    /**
     * Get the request secret key.
     *
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }

    /**
     * Sets the request application key.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setApplicationKey($value)
    {
        return $this->setParameter('applicationKey', $value);
    }

    /**
     * Get the request application key.
     *
     * @return mixed
     */
    public function getApplicationKey()
    {
        return $this->getParameter('applicationKey');
    }

    /**
     * Sets the request access token.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setAccessToken($value)
    {
        return $this->setParameter('accessToken', $value);
    }

    /**
     * Get the request access token.
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->getParameter('accessToken');
    }

    /**
     * Get url Depends on  test mode.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getTestMode() ? $this->sandboxEndpoint : $this->prodEndpoint;
    }

    /**
     * @return string
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function signature()
    {
        $params = [
            'id'       => $this->getTransactionId(),
            'amount'   => (float) $this->getAmount(),
            'currency' => $this->getCurrency(),
        ];

        ksort($params, SORT_STRING);

        $params = array_values($params);
        $params[] = $this->getSecretKey();

        return hash('sha256', implode(':', $params));
    }

    /**
     * Get HTTP Method.
     *
     * This is nearly always POST but can be over-ridden in sub classes.
     *
     * @return string
     */
    public function getHttpMethod(): string
    {
        return 'POST';
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * /**
     * Set custom data to get back as is.
     *
     * @param array $value
     *
     * @return $this
     */
    public function setCustomData(array $value)
    {
        return $this->setParameter('customData', $value);
    }

    /**
     * Get custom data.
     *
     * @return mixed
     */
    public function getCustomData()
    {
        return $this->getParameter('customData', []) ?? [];
    }
}
