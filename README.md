# Print-one Laravel

[![Issues][issues-img]][issues-url]

> The official laravel package for [Print.one](https://print.one)

Laravel package that lets you send automated personalized postcards using Print.one.

## Installation

You can install the package via composer:

```bash
composer require print-one/print-one-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="print-one-config"
```

## Usage example

```php
use Illuminate\Support\Facades\Config;
use Nexibi\PrintOne\Facades\PrintOne;

Config::set('print-one.api_key', 'live_your-api-key');
PrintOne::templates(); // Get available templates
```

## Help

- With problems, questions or suggestions, please file an [issue](https://github.com/Print-one/print-one-laravel/issues).
- For other questions, feel free to contact us at [our support page](https://support.print.one).


