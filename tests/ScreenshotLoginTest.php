<?php

use Blemli\Pwa\Tests\Fixtures\User;
use Illuminate\Support\Facades\URL;

function signedScreenshotLoginUrl(int | string $userId, ?string $theme = null): string
{
    return URL::temporarySignedRoute(
        'filament.admin.pwa.screenshot-login',
        now()->addMinutes(10),
        array_filter(['user' => $userId, 'pwa-theme' => $theme]),
        absolute: false,
    );
}

it('rejects unsigned requests', function () {
    config()->set('app.debug', true);

    $this->get('/admin/pwa/screenshot-login?user=1')->assertForbidden();
});

it('is inert in production', function () {
    config()->set('app.debug', false);

    $user = User::forceCreate(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);

    $this->get(signedScreenshotLoginUrl($user->getKey()))->assertNotFound();
});

it('logs the browser in and forwards to the panel with capture params', function () {
    config()->set('app.debug', true);

    $user = User::forceCreate(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);

    $response = $this->get(signedScreenshotLoginUrl($user->getKey(), theme: 'dark'));

    $response->assertRedirect();

    expect($response->headers->get('Location'))
        ->toContain('pwa-screenshot=1')
        ->toContain('pwa-theme=dark');

    $this->assertAuthenticatedAs($user);
});
