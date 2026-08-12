<?php

namespace Blemli\Pwa\Http\Controllers;

use Blemli\Pwa\PwaPlugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

/**
 * Receives Web Share Target POSTs from the OS share sheet. The shared files
 * are parked as Livewire temporary uploads, then the user lands on the target
 * resource's create page where SharePrefillHook attaches them to the
 * configured FileUpload field.
 */
class ShareTargetController
{
    public const SESSION_KEY = 'pwa-for-filament.shared';

    public function store(Request $request): RedirectResponse
    {
        $panel = Filament::getCurrentPanel();

        abort_unless($panel !== null, 404);

        /** @var PwaPlugin $plugin */
        $plugin = $panel->getPlugin('pwa-for-filament');
        $target = $plugin->getShareTarget($panel);

        abort_unless($target !== null, 404);

        $stored = [];

        foreach (Arr::wrap($request->file('file')) as $file) {
            $stored[] = basename(FileUploadConfiguration::storeTemporaryFile($file, FileUploadConfiguration::disk()));
        }

        session()->put(self::SESSION_KEY, [
            'resource' => $target['resource'],
            'field' => $target['field'],
            'files' => $stored,
            'title' => $request->input('title'),
            'text' => $request->input('text'),
            'url' => $request->input('url'),
        ]);

        if (! Filament::auth()->check()) {
            session()->put('url.intended', $panel->route('pwa.share.resume'));

            return redirect()->to($panel->getLoginUrl() ?? $panel->getUrl());
        }

        return $this->redirectToCreatePage($panel, $target['resource']);
    }

    public function resume(): RedirectResponse
    {
        $panel = Filament::getCurrentPanel();

        abort_unless($panel !== null, 404);

        $shared = session()->get(self::SESSION_KEY);

        if (blank($shared)) {
            return redirect()->to($panel->getUrl());
        }

        return $this->redirectToCreatePage($panel, $shared['resource']);
    }

    /** @param  class-string  $resource */
    protected function redirectToCreatePage(Panel $panel, string $resource): RedirectResponse
    {
        $url = rescue(fn (): string => $resource::getUrl('create', panel: $panel->getId()), report: false);

        return redirect()->to($url ?? $panel->getUrl());
    }
}
