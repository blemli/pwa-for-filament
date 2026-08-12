<?php

namespace Blemli\Pwa\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Blemli\Pwa\PwaServiceProvider;
use Blemli\Pwa\Tests\Fixtures\AdminPanelProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function tearDown(): void
    {
        // Anything the suite writes into the shared testbench skeleton must
        // go — leftovers poison every later test.
        foreach ([
            public_path('pwa'),
            public_path('js/blemli'),
            public_path('css/blemli'),
            config_path('pwa-for-filament.php'),
            lang_path('vendor/pwa-for-filament'),
            resource_path('views/vendor/pwa-for-filament'),
        ] as $path) {
            File::isDirectory($path) ? File::deleteDirectory($path) : File::delete($path);
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        $providers = [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            PwaServiceProvider::class,
        ];

        sort($providers);

        return [...$providers, AdminPanelProvider::class];
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', Fixtures\User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();

        Schema::create('notifications', function ($table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('test_models', function ($table): void {
            $table->id();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }
}
