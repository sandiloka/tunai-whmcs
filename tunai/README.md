## Tunai.id lib for PHP

This is a Tunai.id lib for PHP.

## Install

This lib can be installed using Composer. Add the following definition to your `composer.json`:

```
{
    "require": {
        "sandiloka/tunai": "*"
    }
}
```

## Interact with Invoice object

```php

use Sandiloka\Tunai\Invoice;

$invoice = new Invoice('key', `secret`);

$invoiceData = array
    (
        'refId' => '1234567',
        'expired' => time() . '000', // to match JS new Date().valueOf()
        'customer' => array
            (
                "name": "Joni Iskandar",
                "phone": "08122039966",
                "address": "Jalan Senandung Raya No. 1"
            ),
        'items' => array(
            array(
                "id": "123456",
                "description": "Sepatu Warna Merah Jambu",
                "qty": 1,
                "price": 100000
            )
        )

    );

$res = $invoice.create($invoiceData);

$statusCode = $res.getStatusCode();
$json = $res.getBody();
```

## License

MIT
