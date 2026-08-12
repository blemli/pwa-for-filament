<?php

use Blemli\Pwa\Http\Controllers\ShareTargetController;
use Blemli\Pwa\Tests\Fixtures\TestResource;
use Blemli\Pwa\Tests\Fixtures\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

beforeEach(function () {
    config()->set('pwa-for-filament.share_target', [
        'resource' => TestResource::class,
        'field' => 'attachment',
        'accept' => ['image/*'],
    ]);

    Storage::fake(FileUploadConfiguration::disk());
});

it('is not routable without a configured share target', function () {
    config()->set('pwa-for-filament.share_target', null);

    $this->post('/admin/pwa/share')->assertNotFound();
});

it('accepts an OS share sheet post without a csrf token and parks the file', function () {
    $user = User::forceCreate(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);

    $response = $this->actingAs($user)->post('/admin/pwa/share', [
        'title' => 'Shared title',
        'file' => UploadedFile::fake()->image('shared.png'),
    ]);

    $response->assertRedirectContains('/create');

    $shared = session()->get(ShareTargetController::SESSION_KEY);

    expect($shared['resource'])->toBe(TestResource::class)
        ->and($shared['field'])->toBe('attachment')
        ->and($shared['files'])->toHaveCount(1)
        ->and($shared['title'])->toBe('Shared title');

    Storage::disk(FileUploadConfiguration::disk())
        ->assertExists(FileUploadConfiguration::path($shared['files'][0]));
});

it('sends guests through login and resumes into the create page', function () {
    $this->post('/admin/pwa/share', ['file' => UploadedFile::fake()->image('shared.png')])
        ->assertRedirect('http://localhost/admin/login');

    expect(session()->get('url.intended'))->toContain('/admin/pwa/share/resume');

    $user = User::forceCreate(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);

    $this->actingAs($user)
        ->get('/admin/pwa/share/resume')
        ->assertRedirectContains('/create');
});
