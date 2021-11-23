# Omnipay PayOp
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

```

# Creating an order
Call purchase, it will return the response which includes the public_id for further process.
Please refer to the [Developer Document]() for more information.

```php

         $purchase = $gateway->purchase();
         $purchase->setAmount(12.12);
         $result = $purchase->send()->getData();

```
OR

```php

         $result1 = $gateway->purchase([
              'amount'      => 12.12,
              'currency'    => 'USD',
              'description' => 'order test',
         ])->send()->getData();

```

# Retrieve an order
Please refer to the [Developer Document]() for more information.

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

If you want to keep up to date with release announcements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](),
or better yet, fork the library and submit a pull request.
