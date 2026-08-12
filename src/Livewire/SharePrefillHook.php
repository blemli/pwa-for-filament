<?php

namespace Blemli\Pwa\Livewire;

use Blemli\Pwa\Http\Controllers\ShareTargetController;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Livewire\ComponentHook;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * After a share-target redirect, attaches the parked temporary uploads to the
 * configured FileUpload field on the matching CreateRecord page. The file is
 * stored through the field's own saveUploadedFile() (its disk, directory and
 * image handling), because Filament renders raw TemporaryUploadedFile state
 * invisibly — a stored path shows up as a regular uploaded file.
 *
 * Runs on render (not mount) so it lands after Filament's form->fill().
 */
class SharePrefillHook extends ComponentHook
{
    public function render($view, $data): void
    {
        if (! $this->component instanceof CreateRecord) {
            return;
        }

        $shared = session()->get(ShareTargetController::SESSION_KEY);

        if (blank($shared) || blank($shared['files'] ?? null)) {
            return;
        }

        if (! is_a($this->component::getResource(), $shared['resource'], true)) {
            return;
        }

        session()->forget(ShareTargetController::SESSION_KEY);

        try {
            $field = $shared['field'];
            $upload = $this->uploadComponent($field);

            $state = collect($shared['files'])
                ->map(fn (string $filename): TemporaryUploadedFile => TemporaryUploadedFile::createFromLivewire($filename))
                ->mapWithKeys(function (TemporaryUploadedFile $file) use ($upload): array {
                    $value = $upload?->saveUploadedFile($file) ?? $file;

                    return [(string) Str::uuid() => $value];
                })
                ->all();

            data_set($this->component, "data.{$field}", $state);
        } catch (Throwable) {
            // Prefill is best-effort: a failure must never break the create page.
        }
    }

    protected function uploadComponent(string $field): ?BaseFileUpload
    {
        foreach (['getForm', 'getSchema'] as $accessor) {
            if (! method_exists($this->component, $accessor)) {
                continue;
            }

            $upload = rescue(
                fn (): mixed => $this->component->{$accessor}('form')?->getFlatFields(withHidden: true)[$field] ?? null,
                report: false,
            );

            if ($upload instanceof BaseFileUpload) {
                return $upload;
            }
        }

        return null;
    }
}
