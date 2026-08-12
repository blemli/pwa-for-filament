<?php

namespace Blemli\Pwa\Tests\Fixtures;

use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class TestResource extends Resource
{
    protected static ?string $model = TestModel::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('attachment')
                ->acceptedFileTypes(['image/*']),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TestResourcePages\ListTestModels::route('/'),
            'create' => TestResourcePages\CreateTestModel::route('/create'),
        ];
    }
}
