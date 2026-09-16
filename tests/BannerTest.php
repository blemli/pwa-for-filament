<?php

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

it('renders the install banner with only the native variant into the panel body', function () {
    bootPanel();

    $html = (string) FilamentView::renderHook(PanelsRenderHook::BODY_START);

    expect($html)
        ->toContain('id="pwa-install-banner"')
        ->toContain('data-pwa-variant="native"')
        ->not->toContain('data-pwa-variant="firefox"')
        ->not->toContain('firefox_instructions')
        ->toContain('Install Test App')
        ->toContain('data-pwa-install')
        ->toContain('data-pwa-dismiss')
        ->toContain('Not now');
});

it('translates the banner', function () {
    app()->setLocale('de');

    bootPanel();

    $html = (string) FilamentView::renderHook(PanelsRenderHook::BODY_START);

    expect($html)
        ->toContain('Test App installieren')
        ->toContain('Installieren')
        ->toContain('Später');
});

it('hides the banner when disabled', function () {
    config()->set('pwa-for-filament.banner.enabled', false);

    bootPanel();

    expect((string) FilamentView::renderHook(PanelsRenderHook::BODY_START))
        ->not->toContain('pwa-install-banner');
});

it('ships the client config in the head', function () {
    bootPanel();

    $html = (string) FilamentView::renderHook(PanelsRenderHook::HEAD_END);

    expect($html)
        ->toContain('<link rel="manifest" href="/admin/manifest.webmanifest"')
        ->toContain('id="pwa-config"')
        ->toContain('"swUrl":"/admin/sw.js"')
        ->toContain('"scope":"/admin/"')
        ->toContain('name="theme-color"');
});

it('never shows a banner on firefox — there is nothing to install there', function () {
    $js = file_get_contents(__DIR__ . '/../resources/dist/pwa.js');

    expect($js)->not->toContain("show('firefox'")
        ->and($js)->not->toContain('isFirefox');
});
