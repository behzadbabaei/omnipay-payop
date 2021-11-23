# Omnipay Revoult
PayOp gateway for Omnipay payment processing library
This package has implemented the Merchant API of PayOp Payment systems
For more information please visit the following link:[Developer Document]()

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

```json
{
    "require": {
        "behzadbabaei/omnipay-payop": "dev-master"
    }
}
```

And run composer to update your dependencies:

    composer update

Or you can simply run

    composer require behzadbabaei/omnipay-payop

## Basic Usage

1. Use Omnipay gateway class:

```php
    use Omnipay\Omnipay;
```

2. Initialize PayOp gateway:

```php

    $gateway = Omnipay::create('Payop');
    $gateway->setAccessToken('Access-Token');
    $gateway->setLanguage('EN'); // Language
    $gateway->setAccountId('Merchant-Accounti-Id');
    $gateway->setAmount(31.90); // Amount to charge
    $gateway->setTransactionId(XXXX); // Transaction ID from your system

```

# Creating an order
Call purchase, it will return the response which includes the public_id for further process.
Please refer to the [Developer Document]() for more information.

```php

         $purchase = $gateway->purchase();
         $purchase->setAmount(12.12);
         $purchase->setCurrency("USD");
         $purchase->setCaptureMode('AUTOMATIC');
         $purchase->setMerchantOrderReference('123121');
         $purchase->setEmail('behzadbabaei69@gmail.com');
         $purchase->setDescription('order test');
         $purchase->setSettlementCurrency('GBP');
         $purchase->setCustomerId(1212);
         $result = $purchase->send()->getData();

```
OR

```php

         $result1 = $gateway->purchase([
              'amount'      => 12.12,
              'currency'    => 'USD',
              'captureMode' => 'AUTOMATIC',
              'merchantOrderReference' => 123121,
              'email' => 'behzadbabaei69@gmail.com',
              'description' => 'order test',
              'settlementCurrency' => 'GBP',
              'customerId' => 1212,
         ])->send()->getData();

```

# Capture an order
Please refer to the [Developer Document](https://developer.revolut.com/api-reference/merchant/#operation/captureOrder) for more information.

```php

        $capture = $gateway->capture();
        $capture->setAmount(31.90);
        $capture->setOrderId(1);
        $result = $capture->send()->getData();

```
OR

```php

       $result = $gateway->capture([
            'amount'  => 31.90,
            'orderId' => 1
        ])->send()->getData();

```

# Confirm an order
Please refer to the [Developer Document]() for more information.

```php

        $complete = $gateway->completePurchase();
        $complete->setAmount(31.90);
        $complete->setOrderId(1);
        $complete->setPaymentMethod(12121);
        $result1 = $complete->send()->getData();

```
OR

```php

        $result = $gateway->completePurchase([
            'orderId'       => 1,
            'paymentMethod' => 1
        ])->send()->getData();

```

# Refund an order
Please refer to the [Developer Document](https://developer.revolut.com/api-reference/merchant/#operation/refundOrder) for more information.

```php

        $refund = $gateway->refund();
        $refund->setAmount(31.90);
        $refund->setCurrency('USD');
        $refund->setOrderId(1);
        $refund->setMerchantOrderReference(1);
        $refund->setDescription("Test Description");
        $result1 = $refund->send()->getData();

```
OR

```php

         $result = $gateway->refund([
            'amount'                 => 31.90,
            'currency'               => 'USD',
            'orderId'                => 1,
            'merchantOrderReference' => 1000,
            'description'            => 'Test Description',
        ])->send()->getData();

```

# Cancel an order
Please refer to the [Developer Document]() for more information.

```php

        $cancel = $gateway->cancel();
        $cancel->setOrderId(1);
        $result1 = $cancel->send()->getData();

```
OR

```php

       $result = $gateway->cancel([
            'orderId'                => 1,
        ])->send()->getData();

```

# Retrieve an order
Please refer to the [Developer Document](https://developer.revolut.com/api-reference/merchant/#operation/retrieveOrder) for more information.

```php

        $fetch = $gateway->fetchTransaction();
        $fetch->setOrderId(1);
        $result1 = $fetch->send()->getData();

```
OR

```php

        $result = $gateway->fetchTransaction([
            'orderId'                => 1,
        ])->send()->getData();

```


For general usage instructions, please see the main [Omnipay](https://github.com/thephpleague/omnipay)
repository.

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](),
or better yet, fork the library and submit a pull request.

