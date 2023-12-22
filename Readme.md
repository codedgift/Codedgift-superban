# Superban Package for Laravel

Superban is a Laravel package designed to implement rate-limiting and client banning functionalities. It allows you to ban clients for a specified period after exceeding certain rate limits, with configurable cache drivers and settings.

## Installation

To install the Superban package in your Laravel project, follow these steps:

**1. Add Package to Composer:**
   Add the following to your project's `composer.json`:
   ```json
   "require": {
       "edenlife/superban": "@dev"
   },
   "repositories": [
        {
            "type": "path",
            "url": "./packages/Edenlife/Superban"
        }
    ]
   ```

**2. Update Composer:**
Run the following command to install the package:
```sh
composer update
```

**3. Publish Configuration:**
Publish the configuration file using the Artisan command:
```sh
php artisan vendor:publish --provider="Edenlife\Superban\Providers\SuperbanServiceProvider"
```

**4.Configure:**
Edit the published `config/superban.php` file as needed.

## Usage
Apply the `superban` middleware to routes in your Laravel application to enforce rate limiting and banning:

**For Group Routes:**
```php
Route::middleware(['superban:200,2,1440'])->group(function () {
    Route::get('/thisroute', function () {
        // Route logic here
    });

    Route::post('/anotherroute', function () {
        // Route logic here
    });
});
```
**For Single Routes:**
```php
Route::middleware(['superban:200,2,1440'])->get('/singleroute', function () {
    // Route logic here
});
```

## Configuration
Adjust the settings in the `config/superban.php` file to configure the default cache driver and define cache store settings as per your application requirements.

Made with ❤️ by Gift Amah
