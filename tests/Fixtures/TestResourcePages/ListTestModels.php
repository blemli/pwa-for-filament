<?php

namespace Blemli\Pwa\Tests\Fixtures\TestResourcePages;

use Blemli\Pwa\Tests\Fixtures\TestResource;
use Filament\Resources\Pages\ListRecords;

class ListTestModels extends ListRecords
{
    protected static string $resource = TestResource::class;
}
