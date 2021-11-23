<?php

declare(strict_types = 1);

namespace Omnipay\Payop\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\RedirectResponseInterface;

use function json_decode;
use function in_array;

/**
 * Payop Response.
 *
 * This is the response class for all Payop requests.
 *
 * @see \Omnipay\Payop\Gateway
 */
class Response extends AbstractResponse implements RedirectResponseInterface
{
    const PAY_OP_STATUS_NEW = 0;
    const PAY_OP_STATUS_SUCCESSFUL = 1;
    const PAY_OP_STATUS_PENDING = 4;
    const PAY_OP_STATUS_FAILED = 5;

    /**
     * Request id
     *
     * @var string URL
     */
    protected $requestId = null;

    /**
     * @var array
     */
    protected $headers = [];

    public function __construct(RequestInterface $request, $data, $headers = [])
    {
        parent::__construct($request, $data);

        $this->request = $request;
        $this->data = json_decode($data, true);
        $this->headers = $headers;
    }

    /**
     * Is the transaction in processing status?
     *
     * @return bool
     */
    public function isProcessing() : bool
    {
        return in_array($this->getOrderStatus(), [self::PAY_OP_STATUS_NEW]);
    }

    /**
     * Is the transaction in pending status?
     *
     * @return bool
     */
    public function isPending() : bool
    {
        return in_array($this->getOrderStatus(), [self::PAY_OP_STATUS_PENDING]);
    }

    /**
     * Is the transaction successful?
     *
     * @return bool
     */
    public function isSuccessful() : bool
    {
        if ($this->getOrderStatus()) {
            return $this->isCompleted() && $this->isNotError();
        }

        return $this->isNotError();
    }

    /**
     * Is the response no error
     *
     * @return bool
     */
    public function isNotError() : bool
    {
        return in_array($this->getCode(), [self::PAY_OP_STATUS_FAILED]);
    }

    /**
     * Is the orderStatus completed
     * Full authorization of the order amount
     *
     * @return bool
     */
    public function isCompleted() : bool
    {
        return in_array($this->getOrderStatus(), [self::PAY_OP_STATUS_SUCCESSFUL]);
    }

    /**
     * @return bool
     */
    public function isRedirect() : bool
    {
        return false;
    }

    /**
     * Get response redirect url
     *
     * @return string
     */
    public function getRedirectUrl() : string
    {
        //https://payop.com/{{locale}}/payment/invoice-preprocessing/{{invoiceId}}
        return '';
    }


    /**
     * @return mixed|null
     */
    public function getPublicId()
    {
        if (isset($this->data['public_id'])) {
            return $this->data['public_id'];
        }

        return null;
    }

    /**
     * Get the orderStatus.
     *
     * @return |null
     */
    public function getOrderStatus()
    {
        if (isset($this->data['state'])) {
            return $this->data['state'];
        }

        return null;
    }

    /**
     * Get the error message from the response.
     *
     * Returns null if the request was successful.
     *
     * @return string|null
     */
    public function getMessage() : ?string
    {
        return null;
    }

    /**
     * Get the error code from the response.
     *
     * Returns null if the request was successful.
     *
     * @return string|null
     */
    public function getCode() : ?string
    {
        if (isset($this->data['errorId'])) {
            return $this->data['errorId'];
        }

        return null;
    }
}
