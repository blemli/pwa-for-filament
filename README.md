# pwa-for-filament

> Zero config. Fully installable.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blemli/pwa-for-filament.svg?style=flat-square)](https://packagist.org/packages/blemli/pwa-for-filament)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blemli/pwa-for-filament/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/blemli/pwa-for-filament/actions?query=workflow%3Atests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/blemli/pwa-for-filament.svg?style=flat-square)](https://packagist.org/packages/blemli/pwa-for-filament)

Turns your Filament panel (v4/v5) into an installable PWA: manifest and colors from your panel config, icons from your brand logo (SVG welcome), install banner (Chromium + Firefox), offline page, app-icon badge from database notifications, Web Share Target into a FileUpload of your choice, and light/dark screenshots captured for you.

![Install banner](https://raw.githubusercontent.com/blemli/pwa-for-filament/main/art/install-banner.png)

## Install

```bash
composer require blemli/pwa-for-filament
php artisan pwa:install
```

Register the plugin in your panel provider:

```php
->plugin(\Blemli\Pwa\PwaPlugin::make())
```

That's it — everything is derived from the panel. Optional tweaks via `config/pwa-for-filament.php` or fluently:

```php
PwaPlugin::make()
    ->offlineMessage('Back soon.')
    ->shareTarget(DocumentResource::class, 'attachment')
    ->badge(false)
```

Uninstall cleanly with `php artisan pwa:uninstall`.

## License

MIT © [blemli](https://github.com/blemli)
