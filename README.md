# Print-one Laravel

![Packagist Version](https://img.shields.io/packagist/v/Print-one/print-one-laravel)
[![Issues][issues-img]][issues-url]

[issues-img]:https://img.shields.io/github/issues/Print-one/print-one-laravel/bug
[issues-url]:https://github.com/Print-one/print-one-laravel/issues

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
use PrintOne\PrintOne\Facades\PrintOne;

Config::set('print-one.api_key', 'live_your-api-key');
PrintOne::templates(); // Get available templates
```

## Help

- With problems, questions or suggestions, please file an [issue](https://github.com/Print-one/print-one-laravel/issues).
- For other questions, feel free to contact us at [our support page](https://support.print.one).


## Credits
Initial project was developed by [Nexibi](https://github.com/Nexibi/print-one)
