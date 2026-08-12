<?php

use Blemli\Pwa\Support\InstallState;

it('serves the service worker uncached at the panel scope', function () {
    $response = $this->get('/admin/sw.js');

    $response->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/javascript')
        ->and($response->headers->get('Cache-Control'))->toContain('no-cache')
        ->and($response->getContent())
        ->toContain('"cachePrefix":"pwa-admin-"')
        ->toContain('"version":"dev"')
        ->toContain('/livewire/')
        ->toContain('/admin/pwa/offline');
});

it('busts caches with the install version', function () {
    InstallState::write('admin', ['version' => '20260812']);

    expect($this->get('/admin/sw.js')->getContent())->toContain('"version":"20260812"');
});
