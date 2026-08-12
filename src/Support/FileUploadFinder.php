<?php

namespace Blemli\Pwa\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Discovers FileUpload fields across a panel's resources without mutating
 * anything: a configureUsing() collector scoped to each resource's form
 * build, which is the introspection pattern that survives closure-defined
 * schemas (walking a built Schema collapses them).
 */
class FileUploadFinder
{
    /** @return array<array{resource: class-string, field: string, label: string, accept: array<string> | null, multiple: bool}> */
    public static function find(Panel $panel): array
    {
        if (! class_exists(Schema::class)) {
            return [];
        }

        $found = [];

        foreach ($panel->getResources() as $resource) {
            try {
                $collected = [];

                FileUpload::configureUsing(
                    function (BaseFileUpload $component) use (&$collected): void {
                        $collected[] = $component;
                    },
                    during: fn (): Schema => $resource::form(Schema::make()),
                );
            } catch (Throwable) {
                continue;
            }

            foreach ($collected as $component) {
                $name = $component->getName();

                $found[] = [
                    'resource' => $resource,
                    'field' => $name,
                    'label' => rescue(fn (): string => (string) $component->getLabel(), '', report: false) ?: Str::headline($name),
                    'accept' => rescue(fn (): ?array => $component->getAcceptedFileTypes(), report: false),
                    'multiple' => (bool) rescue(fn (): bool => $component->isMultiple(), false, report: false),
                ];
            }
        }

        return $found;
    }
}
