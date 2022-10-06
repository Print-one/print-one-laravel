# Nexibi\PrintOne

Laravel package that lets you send automated personalized postcards using Print.one.

## Installation

You can install the package via composer:

```bash
composer require nexibi/print-one
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="print-one-config"
```

## Usage example

```php
$printOne = new Nexibi\PrintOne();
$printOne->templates(); // Get available templates
```

## Testing

```bash
composer test
```
