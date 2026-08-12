<?php

use Blemli\Pwa\Tests\Fixtures\User;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function seedUnreadNotifications(User $user, int $count): void
{
    foreach (range(1, $count) as $index) {
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'data' => '{}',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

it('emits the unread notification count for the badge', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);

    seedUnreadNotifications($user, 2);

    $this->actingAs($user);

    bootPanel();

    expect((string) FilamentView::renderHook(PanelsRenderHook::BODY_END))
        ->toContain('id="pwa-badge-count"')
        ->toContain('data-count="2"');
});

it('emits nothing for guests', function () {
    bootPanel();

    expect((string) FilamentView::renderHook(PanelsRenderHook::BODY_END))
        ->not->toContain('pwa-badge-count');
});

it('emits nothing when the badge is disabled', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);

    $this->actingAs($user);

    config()->set('pwa-for-filament.badge.enabled', false);

    bootPanel();

    expect((string) FilamentView::renderHook(PanelsRenderHook::BODY_END))
        ->not->toContain('pwa-badge-count');
});
