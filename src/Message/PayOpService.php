<?php

declare(strict_types = 1);

namespace App\Services\PayOp;

use App\Helpers\StringHelper;
use App\Models\Invoice;
use Exception;
use Throwable;

use function in_array;
use function route;
use function number_format;
use function strtoupper;

class PayOpService
{
    /**
     * @var \App\Services\PayOp\PayOpClient
     */
    protected PayOpClient $payopClient;

    //        0	new	New invoice
    //        1	accepted	Invoice was paid successfully
    //        4	pending	Invoice pending
    //        5	failed	Invoice failed
    const PAYOP_STATUS_NEW = 0;
    const PAYOP_STATUS_SUCCESSFUL = 1;
    const PAYOP_STATUS_PENDING = 4;
    const PAYOP_STATUS_FAILED = 5;

    /**
     * PayOpService constructor.
     *
     * @param \App\Services\PayOp\PayOpClient $payopClient
     */
    public function __construct(PayOpClient $payopClient)
    {
        $this->payopClient = $payopClient;
    }

    public function getCardPaymentMethods()
    {
        $paymentMethods = $this->payopClient->getPaymentMethods() ?? [];

        foreach ($paymentMethods as $key => $method) {
            if (!in_array($method['formType'], ['cards'])) {
                unset($paymentMethods[$key]);
            }
        }

        return $paymentMethods;
    }

    /**
     * @param \App\Models\Invoice $invoice
     * @param                     $price
     * @param null                $pmId
     * @param bool                $isInternationalCard
     *
     * @return mixed|null
     */
    public function createInvoice(Invoice $invoice, $price, $pmId = null, bool $isInternationalCard = false)
    {
        try {
            if ($isInternationalCard) {
                $resultUrl = route('card.international_success').'?orderId='.$invoice->transaction_id.'&invoiceId={{invoiceId}}';
                $failUrl = route('card.international_failure').'?orderId='.$invoice->transaction_id.'&invoiceId={{invoiceId}}';
            } else {
                $resultUrl = route('payop.success').'?orderId='.$invoice->transaction_id.'&invoiceId={{invoiceId}}';
                $failUrl = route('payop.failure').'?orderId='.$invoice->transaction_id.'&invoiceId={{invoiceId}}';
            }

            $payer = [
                "email" => $invoice->user->email,
                "phone" => $invoice->phone_number,
                "name"  => $invoice->user->fullName,
            ];

            $order = [
                'transaction_id' => $invoice->transaction_id,
                'amount'         => number_format($price, 3, '.', ''),
                'currency'       => strtoupper($invoice->currency->code),
                'items'          => [
                    'name'     => StringHelper::replaceProductNameFifaVersion("fifa-22", '100k'),
                    'price'    => $price,
                    'currency' => strtoupper($invoice->currency->code),
                ],
                'description'    => "Payment for demo app order #{$invoice->id}",
                'pmId'           => $pmId,
            ];

            return $this->payopClient->createInvoice($order, $payer, $resultUrl, $failUrl);
        } catch (Throwable $e) {
            \Log::info("createInvoice error & Message:".$e->getMessage().' & File:'.$e->getFile().' & Line:'.$e->getLine());

            return null;
        }
    }

    /**
     * @param \App\Models\Invoice $invoice
     * @param                     $price
     * @param string              $resultUrl
     * @param string              $failUrl
     * @param null                $pmId
     *
     * @return mixed|null
     */
    public function createNewInvoice(Invoice $invoice, $price, string $resultUrl, string $failUrl, $pmId = null)
    {
        try {
            $payer = [
                "email" => $invoice->user->email,
                "phone" => $invoice->phone_number,
                "name"  => $invoice->user->fullName,
            ];

            $order = [
                'transaction_id' => $invoice->transaction_id,
                'amount'         => number_format($price, 3, '.', ''),
                'currency'       => strtoupper($invoice->currency->code),
                'items'          => [
                    'name'     => StringHelper::replaceProductNameFifaVersion("fifa-22", '100k'),
                    'price'    => $price,
                    'currency' => strtoupper($invoice->currency->code),
                ],
                'description'    => "Payment for demo app order #{$invoice->id}",
                'pmId'           => $pmId,
            ];

            return $this->payopClient->createInvoice($order, $payer, $resultUrl, $failUrl);
        } catch (Throwable $e) {
            \Log::info("createInvoice error & Message:".$e->getMessage().' & File:'.$e->getFile().' & Line:'.$e->getLine());

            return null;
        }
    }

    /**
     * @param        $transactionId
     * @param string $locale
     *
     * @return string|null
     */
    public function getPaymentLink($transactionId, $locale = 'en') : ?string
    {
        return $this->payopClient->getPaymentLink($transactionId, $locale);
    }

    /**
     * @param string $identifier
     *
     * @return array|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findByIdentifier(string $identifier) : ?array
    {
        try {
            return $this->payopClient->getInvoice($identifier);
        } catch (Exception $e) {
            \Log::info("findByIdentifier error & Message:".$e->getMessage().' & File:'.$e->getFile().' & Line:'.$e->getLine());

            return null;
        }
    }
}
