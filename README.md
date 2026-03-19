# MyAdmin Payza Payments Plugin

Payza (formerly AlertPay) payment gateway integration for the [MyAdmin](https://github.com/detain/myadmin) billing and hosting management platform. This plugin handles checkout form generation, invoice processing, and payment callbacks through the Payza payment processor.

## Badges

[![Build Status](https://github.com/detain/myadmin-payza-payments/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-payza-payments/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-payza-payments/version)](https://packagist.org/packages/detain/myadmin-payza-payments)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-payza-payments/downloads)](https://packagist.org/packages/detain/myadmin-payza-payments)
[![License](https://poser.pugx.org/detain/myadmin-payza-payments/license)](https://packagist.org/packages/detain/myadmin-payza-payments)

## Features

- Payza checkout form generation with configurable merchant settings
- Invoice-based payment processing with multi-item support
- Arbitrary-precision arithmetic (bcmath) for financial calculations
- IPN token verification via random string generation
- Configurable enable/disable toggle and merchant email through MyAdmin settings
- Event-driven architecture using Symfony EventDispatcher

## Requirements

- PHP 8.2 or higher
- ext-soap
- ext-bcmath
- Symfony EventDispatcher 5.x, 6.x, or 7.x

## Installation

Install via Composer:

```sh
composer require detain/myadmin-payza-payments
```

## Configuration

The plugin registers two settings in the MyAdmin admin panel under **Billing > Payza**:

| Setting         | Description                          |
|-----------------|--------------------------------------|
| `payza_enable`  | Enable or disable the Payza gateway  |
| `payza_email`   | Merchant email for Payza checkout    |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1-only](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.
