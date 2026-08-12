<?php

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
