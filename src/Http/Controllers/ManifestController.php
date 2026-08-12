<?php

namespace Blemli\Pwa\Http\Controllers;

use Blemli\Pwa\Support\ManifestBuilder;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;

class ManifestController
{
    public function __invoke(): JsonResponse
    {
        $panel = Filament::getCurrentPanel();

        abort_unless($panel !== null, 404);

        return response()
            ->json(ManifestBuilder::for($panel)->build(), options: JSON_UNESCAPED_SLASHES)
            ->withHeaders([
                'Content-Type' => 'application/manifest+json',
                'Cache-Control' => 'public, max-age=300',
            ]);
    }
}
