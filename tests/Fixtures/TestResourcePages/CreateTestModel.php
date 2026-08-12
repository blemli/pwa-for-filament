<?php

namespace Blemli\Pwa\Tests\Fixtures\TestResourcePages;

use Blemli\Pwa\Tests\Fixtures\TestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestModel extends CreateRecord
{
    protected static string $resource = TestResource::class;
}
