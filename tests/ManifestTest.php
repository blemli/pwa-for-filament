<?php

use Blemli\Pwa\Support\InstallState;
use Blemli\Pwa\Tests\Fixtures\TestResource;

it('serves a manifest for the panel without authentication', function () {
    $response = $this->get('/admin/manifest.webmanifest');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/manifest+json');
});

it('derives every zero-config member from the panel', function () {
    $manifest = $this->get('/admin/manifest.webmanifest')->json();

    expect($manifest['name'])->toBe('Test App')
        ->and($manifest['short_name'])->toBe('Test App')
        ->and($manifest['id'])->toBe('/admin/')
        ->and($manifest['start_url'])->toBe('/admin/')
        ->and($manifest['scope'])->toBe('/admin/')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['display_override'])->toBe(['standalone', 'minimal-ui'])
        ->and($manifest['orientation'])->toBe('any')
        ->and($manifest['dir'])->toBe('auto')
        ->and($manifest['lang'])->toBe('en')
        ->and($manifest['theme_color'])->toMatch('/^#[0-9a-f]{6}$/')
        ->and($manifest['background_color'])->toMatch('/^#[0-9a-f]{6}$/')
        ->and($manifest['launch_handler'])->toBe(['client_mode' => 'navigate-existing'])
        ->and($manifest['prefer_related_applications'])->toBeFalse()
        ->and($manifest)->not->toHaveKeys(['share_target', 'categories', 'description']);
});

it('follows the app locale', function () {
    app()->setLocale('de');

    expect($this->get('/admin/manifest.webmanifest')->json('lang'))->toBe('de');
});

it('lists generated icons and dev-dropped screenshots', function () {
    $directory = InstallState::directory('admin');
    File::ensureDirectoryExists("{$directory}/screenshots");

    $image = imagecreatetruecolor(64, 32);
    imagepng($image, "{$directory}/icon-192.png");
    imagepng($image, "{$directory}/screenshots/custom.png");

    $manifest = $this->get('/admin/manifest.webmanifest')->json();

    expect($manifest['icons'])->toHaveCount(1)
        ->and($manifest['icons'][0]['sizes'])->toBe('192x192')
        ->and($manifest['screenshots'][0]['sizes'])->toBe('64x32')
        ->and($manifest['screenshots'][0]['form_factor'])->toBe('wide')
        ->and($manifest['screenshots'][0]['label'])->toBe('Desktop view');
});

it('honors config overrides and the install state', function () {
    config()->set('pwa-for-filament.name', 'Custom Name');
    config()->set('pwa-for-filament.categories', ['books', 'music']);
    config()->set('pwa-for-filament.theme_color', '#123456');

    InstallState::write('admin', ['version' => 'test']);

    $manifest = $this->get('/admin/manifest.webmanifest')->json();

    expect($manifest['name'])->toBe('Custom Name')
        ->and($manifest['categories'])->toBe(['books', 'music'])
        ->and($manifest['theme_color'])->toBe('#123456');
});

it('exposes the share target when configured', function () {
    config()->set('pwa-for-filament.share_target', [
        'resource' => TestResource::class,
        'field' => 'attachment',
        'accept' => ['image/*'],
    ]);

    $shareTarget = $this->get('/admin/manifest.webmanifest')->json('share_target');

    expect($shareTarget['action'])->toBe('/admin/pwa/share')
        ->and($shareTarget['method'])->toBe('POST')
        ->and($shareTarget['enctype'])->toBe('multipart/form-data')
        ->and($shareTarget['params']['files'][0]['accept'])->toBe(['image/*']);
});

it('offers panel resources as shortcuts', function () {
    $shortcuts = $this->get('/admin/manifest.webmanifest')->json('shortcuts');

    expect($shortcuts)->toHaveCount(1)
        ->and($shortcuts[0]['name'])->not->toBeEmpty()
        ->and($shortcuts[0]['url'])->toContain('/admin/');
});
