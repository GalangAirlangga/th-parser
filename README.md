# TerminalHero ISO8583

[![Tests](https://github.com/TerminalHero/parser-iso8583/actions/workflows/tests.yml/badge.svg)](https://github.com/TerminalHero/parser-iso8583/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-red)](https://laravel.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Configuration-driven ISO8583 message parsing and building for PHP and Laravel.

Use it as a foundation for payment gateways, switching networks, ATM/POS hosts, and other banking integrations. Define a spec that matches your network before exchanging production traffic.

---

## Features

- Parse raw messages into `Message` objects and build them back into wire-format strings.
- Define field format, length, padding, bitmap, header, and length-indicator behavior with reusable specs.
- Work with ASCII or BCD variable-length indicators; hexadecimal or raw-binary bitmaps; and common TCP length prefixes.
- Use helpers for common fields, response generation, masked logging, JSON serialization, aliases, and field-level diffs.
- Add structured transformers for positional subfields, EMV TLV data, and token-based private fields.
- Run automated tests across supported PHP, Laravel, and operating-system combinations in GitHub Actions.

---

## Requirements

- PHP `^8.2`
- Laravel `^12.0` when using the service provider or facade

---

## Installation

```bash
composer require terminalhero/iso8583
```

Laravel discovers the service provider automatically through Composer.

---

## Quick Start

### Laravel

```php
use TerminalHero\Iso8583\Facades\Iso8583;
use TerminalHero\Iso8583\Enums\ResponseCode;

$message = Iso8583::makeMessage('0200')
    ->setPan('4111111111111111')
    ->setProcessingCode('000000')
    ->setAmount('000000010000')
    ->setStan('123456')
    ->setRrn('987654321012')
    ->setTerminalId('TERM0001')
    ->setMerchantId('MERCHANT123');

$rawString = Iso8583::build($message);
```

### Plain PHP

```php
use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Iso8583;

$iso = new Iso8583();

$message = $iso->makeMessage('0200')
    ->setField(Field::PROCESSING_CODE, '000000')
    ->setField(Field::AMOUNT_TRANSACTION, '000000010000')
    ->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, '123456');

$rawString = $iso->build($message);
```

### Field aliases

```php
// In your custom Spec:
$this->set(Field::PRIMARY_ACCOUNT_NUMBER, Format::NUMERIC, 19, LengthType::LLVAR)
     ->alias('card_number');
$this->set(Field::AMOUNT_TRANSACTION, Format::NUMERIC, 12, LengthType::FIXED)
     ->alias('amount');

// In your application code:
$message->setByAlias('card_number', '4111111111111111', $spec);
$amount = $message->getByAlias('amount', $spec);
```

### Comparing messages

```php
// Compare Request vs Response or Before vs After
$diff = $request->diff($response);

print_r($diff);
// Output:
// [
//   'added'     => [39 => '00'],       // Field 39 present in response
//   'removed'   => [55 => [...]],      // Field 55 stripped in response
//   'modified'  => [12 => ['from' => '120000', 'to' => '120005']],
//   'unchanged' => [4 => '000000010000']
// ]
```

### Creating a response

```php
// Received request
$request = Iso8583::parse($rawString);

// Creates 0210 from 0200 and copies the standard routing and trace fields.
$response = $request->createResponse(ResponseCode::APPROVED);

echo $response->getMti();        // "0210"
echo $response->isApproved();    // true
echo $response->getResponseCode(); // "00"

// Optional: if you want to clone ALL fields from request into response:
$response = $request->createResponse(ResponseCode::APPROVED, null, copyAllFields: true);

$responseRaw = Iso8583::build($response);
```

### Safer logging

```php
// Masks PAN, Track 2, and PIN data before logging.
Log::info('ISO8583 Message Received', $message->toMaskedArray());
// PAN: "411111******1111", PIN: "***MASKED***"
```

---

## Specs and configuration

The included specs provide useful starting points. ISO8583 implementations vary by network, so extend a spec with your host's exact field definitions, framing, and encoding requirements.

- `Standard1987Spec`
- `Standard1993Spec`
- `Standard2003Spec`

---

## Tests

```bash
# Run the test suite
vendor/bin/phpunit --configuration phpunit.xml
```

GitHub Actions runs the suite on Linux and Windows across the supported PHP and Laravel versions.

---

## 📄 License

MIT — see [LICENSE](LICENSE) for details.
