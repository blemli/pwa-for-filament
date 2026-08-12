<?php

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

it('renders the install banner with both variants into the panel body', function () {
    bootPanel();

    $html = (string) FilamentView::renderHook(PanelsRenderHook::BODY_START);

    expect($html)
        ->toContain('id="pwa-install-banner"')
        ->toContain('data-pwa-variant="native"')
        ->toContain('data-pwa-variant="firefox"')
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
