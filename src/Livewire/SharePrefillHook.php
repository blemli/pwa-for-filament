<?php

namespace Blemli\Pwa\Livewire;

use Blemli\Pwa\Http\Controllers\ShareTargetController;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Livewire\ComponentHook;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * After a share-target redirect, fills the configured FileUpload field on the
 * matching CreateRecord page with the parked temporary uploads — the same
 * state shape a normal FilePond upload produces, so saving goes through the
 * standard pipeline.
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
            $uploads = collect($shared['files'])
                ->mapWithKeys(fn (string $filename): array => [
                    (string) Str::uuid() => TemporaryUploadedFile::createFromLivewire($filename),
                ])
                ->all();

            data_set($this->component, "data.{$shared['field']}", $uploads);
        } catch (Throwable) {
            // Prefill is best-effort: a failure must never break the create page.
        }
    }
}
