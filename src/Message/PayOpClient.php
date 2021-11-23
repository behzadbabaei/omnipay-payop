<?php

declare(strict_types = 1);

namespace App\Services\PayOp;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Request;

use function config;
use function json_decode;
use function str_replace;
use function number_format;
use function strtoupper;
use function ksort;
use function array_values;
use function hash;
use function implode;

class PayOpClient
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Payop app public key
     *
     * @var string
     */
    protected $publicKey;

    /**
     * Payop app secret key
     *
     * @var string
     */
    protected $secretKey;

    /**
     * Authentication access token
     *
     * @var string
     */
    protected $accessToken;

    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    protected $processingEndpoint;

    /**
     * @var string
     */
    protected $applicationKey;

    /**
     * PayopClient constructor.
     */
    public function __construct()
    {
        $this->client = new Client(['base_uri' => config('payop.base_url')]);
        $this->publicKey = config('payop.public_key');
        $this->applicationKey = config('payop.application_key');
        $this->secretKey = config('payop.secret_key');
        $this->accessToken = config('payop.access_token');
        $this->processingEndpoint = config('payop.payop_processing_endpoint');
    }

    /**
     * Get merchant payment methods
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentMethods() : array
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            'instrument-settings/payment-methods/available-for-application',
            [RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$this->accessToken]]
        );

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    /**
     * @param        $transactionId
     * @param string $locale
     *
     * @return string
     */
    public function getPaymentLink($transactionId, $locale = 'en') : string
    {
        $mainUrl = $this->processingEndpoint;
        $mainUrl = str_replace('{{locale}}', $locale, $mainUrl);
        $mainUrl = str_replace('{{invoiceId}}', $transactionId, $mainUrl);

        return $mainUrl;
    }

    /**
     * @param array  $order
     * @param array  $payer
     * @param string $resultUrl
     * @param string $failUrl
     *
     * @return mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createInvoice(
        array $order,
        array $payer,
        string $resultUrl,
        string $failUrl
    ) : ?string {
        // "payer" is a structure with a specific set of fields
        // such as: email, name, phone, extraFields.
        // "email" always required. Other fields depends on selected payment method.
        // To avoid rigid binding to the structure,
        // which does not give the entire possible list of fields to save all possible data
        // we can use "extraFields" field to save payer extra fields.
        //        $resultUrl = route('payop.success').'?orderId='.$order->transaction_id;
        try {
            $payer['extraFields'] = $payer;
            $amount = (float) $order['amount'];
            $params = [
                'publicKey' => $this->publicKey,
                'order'     => [
                    'id'          => $order['transaction_id'],
                    // just example. You can pass decimals: 2|3|4
                    'amount'      => number_format($amount, 3, '.', ''),
                    'currency'    => strtoupper($order['currency']),
                    'items'       => [
                        'name'     => $order['items']['name'],
                        'price'    => $order['items']['price'],
                        'currency' => strtoupper($order['items']['currency']),
                    ],
                    'description' => "Payment for order #{$order['transaction_id']}",
                ],
                // email always required.
                // Extra fields can be necessary by payment method requirements.
                'payer'     => $payer,
                'language'  => 'en',
                'resultUrl' => $resultUrl,
                'failPath'  => $failUrl,
                'signature' => $this->signature($order['transaction_id'], $amount, $order['currency']),
            ];

            if (!empty($order['pmId'])) {
                $params['paymentMethod'] = $order['pmId'];
            }

            $response = $this->client->request(
                Request::METHOD_POST,
                'invoices/create',
                [
                    RequestOptions::JSON    => $params,
                    RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$this->accessToken]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'];
        } catch (Exception $e) {
            \Log::info("createInvoice error & Message:".$e->getMessage().' & File:'.$e->getFile().' & Line:'.$e->getLine());

            return null;
        }
    }

    /**
     * @param string $invoiceId
     *
     * @return array|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInvoice(string $invoiceId) : ?array
    {
        try {
            $response = $this->client->request(
                Request::METHOD_GET,
                "invoices/{$invoiceId}",
                [
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer '.$this->accessToken
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents(), true)['data'];
        } catch (Exception $e) {
            \Log::info("getInvoice:".$e->getMessage());

            return null;
        }
    }

    /**
     * @param string      $invoiceId
     * @param string      $checkStatusUrl
     * @param array       $customer
     * @param string|null $cardToken
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTransaction(
        string $invoiceId,
        string $checkStatusUrl,
        array $customer,
        ?string $cardToken
    ) : array {
        try {
            $params = [
                'invoiceIdentifier' => $invoiceId,
                'customer'          => $customer,
                'cardToken'         => $cardToken,
                'checkStatusUrl'    => $checkStatusUrl,
            ];

            $response = $this->client->request(
                Request::METHOD_POST,
                'checkout/create',
                [
                    RequestOptions::JSON    => $params,
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer '.$this->accessToken
                    ]
                ]
            );
            $data = json_decode($response->getBody()->getContents(), true);

            // Here you have to handle possible errors from response
            return $data['data'];
        } catch (Exception $e) {
            $data = ['status' => 'error', 'message' => 'Code:'.$e->getCode().' Error:'.$e->getMessage()];

            return $data;
        }
    }

    /**
     * @param string $invoiceId
     * @param array  $card
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createCardToken(string $invoiceId, array $card) : array
    {
        try {
            $params = [
                'invoiceIdentifier' => $invoiceId,
                'pan'               => $card['pan'],
                'expirationDate'    => $card['expirationDate'],
                'cvv'               => $card['cvv'],
                'holderName'        => $card['holderName'],
            ];

            $response = $this->client->request(
                Request::METHOD_POST,
                'payment-tools/card-token/create',
                [
                    RequestOptions::JSON    => $params,
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer '.$this->accessToken
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['data'];
        } catch (Exception $e) {
            $data = ['status' => 'error', 'message' => 'Code:'.$e->getCode().' Error:'.$e->getMessage()];

            return $data;
        }
    }

    /**
     * @param string $txid
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkTransactionStatus(string $txid) : array
    {
        try {
            $response = $this->client->request(
                Request::METHOD_GET,
                "checkout/check-transaction-status/{$txid}",
                [
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer '.$this->accessToken
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents(), true)['data'];
        } catch (Exception $e) {
            $data = ['status' => 'error', 'message' => 'Code:'.$e->getCode().' Error:'.$e->getMessage()];

            return $data;
        }
    }

    /**
     * @param string $txid
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransaction(string $txid) : array
    {
        try {
            $response = $this->client->request(
                Request::METHOD_GET,
                "transactions/{$txid}",
                [
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer '.$this->accessToken
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents(), true)['data'];
        } catch (Exception $e) {
            $data = ['status' => 'error', 'message' => 'Code:'.$e->getCode().' Error:'.$e->getMessage()];

            return $data;
        }
    }

    /**
     * @param $orderId
     * @param $amount
     * @param $currencyCode
     *
     * @return string
     */
    private function signature($orderId, $amount, $currencyCode) : string
    {
        $amount = (float) $amount;
        $params = [
            'id'       => $orderId,
            'amount'   => number_format($amount, 3, '.', ''),
            'currency' => strtoupper($currencyCode),
        ];

        ksort($params, SORT_STRING);

        $params = array_values($params);
        $params[] = $this->secretKey;

        return hash('sha256', implode(':', $params));
    }
}
