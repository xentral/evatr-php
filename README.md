# eVATR PHP

PHP SDK for the German BZSt eVATR API — verify EU VAT identification numbers.

## Installation

```bash
composer require xentral/evatr-php
```

## Usage

```php
<?php

use Xentral\EvatrPhp\EvatrClient;
use Xentral\EvatrPhp\Request\ConfirmationQuery;

$client = new EvatrClient();

// Simple query
$result = $client->verifyVatId(
    ConfirmationQuery::simple('DE123456789', 'ATU12345678')
);
echo $result->isValid(); // true/false

// Qualified query (with company data comparison)
$result = $client->verifyVatId(
    ConfirmationQuery::qualified('DE123456789', 'ATU12345678', 'Firma GmbH', 'Wien')
);
$result->companyNameResult; // ComparisonResult::MATCH (A), MISMATCH (B), NOT_REQUESTED (C), NOT_PROVIDED (D)

// List EU member states and VIES availability
$states = $client->getMemberStates();
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Sanan Guliyev](https://github.com/sananguliyev)
- All contributors who have helped improve this package
