<?php

// Every key is optional — pwa-for-filament derives sensible values from your
// panel (brand name, colors, logo) when a key is null. Fluent calls on
// PwaPlugin::make() take precedence over this file.
return [

    // Manifest identity. Defaults: panel brand name / app name.
    'name' => null,
    'short_name' => null,
    'description' => null,

    // Hex overrides. Defaults: primary-600 / gray-50 from your panel colors.
    'theme_color' => null,
    'background_color' => null,

    // Shown on the offline fallback page. Default: translated stock message.
    'offline_message' => null,

    // W3C manifest categories. Default: what you picked during pwa:install.
    'categories' => null,

    // Share target override: ['resource' => SomeResource::class, 'field' => 'attachment'].
    // Default: what you picked during pwa:install.
    'share_target' => null,

    'icons' => [
        // Path or URL to the icon source image. Default: resolved from the
        // panel's ->brandLogo() (SVG supported), then ->favicon().
        'source' => null,

        // Background hex for maskable and apple-touch icons. Default: background_color.
        'background' => null,
    ],

    'screenshots' => [
        // Base URL used by pwa:install for screenshot capture. Default: app.url,
        // falling back to a temporary `php artisan serve`.
        'url' => null,
    ],

    'badge' => [
        // Sync the app icon badge with the unread database notifications count.
        'enabled' => true,
    ],

    'banner' => [
        // Show the install banner (per browser session, dismissible).
        'enabled' => true,

        // Milliseconds before the banner appears.
        'delay' => 2000,
    ],

];
