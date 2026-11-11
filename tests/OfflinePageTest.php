<?php

use Blemli\Pwa\Support\ColorConverter;

it('serves the offline page without authentication', function () {
    $this->get('/admin/pwa/offline')
        ->assertOk()
        ->assertSee('You are offline')
        ->assertSee('No internet connection. Check your connection and try again.');
});

it('translates the offline page', function () {
    app()->setLocale('de');

    $this->get('/admin/pwa/offline')
        ->assertOk()
        ->assertSee('Du bist offline');
});

it('prefers the configured offline message', function () {
    config()->set('pwa-for-filament.offline_message', 'Custom offline copy.');

    $this->get('/admin/pwa/offline')->assertSee('Custom offline copy.');
});

it('themes the offline page from the panel', function () {
    bootPanel();

    $button = ColorConverter::buttonColors('primary');

    $this->get('/admin/pwa/offline')
        ->assertSee("--btn-bg: {$button['bg']}", false)
        ->assertSee("--btn-text: {$button['text']}", false)
        // The fixture panel enables (non-forced) dark mode.
        ->assertSee('.dark {', false)
        ->assertSee('theme-color', false);
});

it('reads the theme choice from Filament\'s storage key, old and new', function () {
    bootPanel();

    // Filament renamed `theme` to `filament:theme:mode` in v4.15.3 / v5.11.0.
    $this->get('/admin/pwa/offline')
        ->assertSee("localStorage.getItem('filament:theme:mode') ?? localStorage.getItem('theme')", false);
});
