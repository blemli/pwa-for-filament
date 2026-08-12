<?php

use Blemli\Pwa\Support\FileUploadFinder;
use Blemli\Pwa\Tests\Fixtures\TestResource;

it('discovers FileUpload fields across panel resources', function () {
    $found = FileUploadFinder::find(bootPanel());

    expect($found)->toHaveCount(1)
        ->and($found[0]['resource'])->toBe(TestResource::class)
        ->and($found[0]['field'])->toBe('attachment')
        ->and($found[0]['accept'])->toBe(['image/*'])
        ->and($found[0]['multiple'])->toBeFalse();
});
