<?php

namespace Platform\Printing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use Platform\Printing\Contracts\PrintingServiceInterface;
use Platform\Printing\Services\PrintingService;
use Platform\Printing\Livewire\Dashboard;
use Platform\Printing\Livewire\Sidebar;
use Platform\Printing\Livewire\Printers\Index as PrintersIndex;
use Platform\Printing\Livewire\Printers\Show as PrintersShow;
use Platform\Printing\Livewire\Groups\Index as GroupsIndex;
use Platform\Printing\Livewire\Groups\Show as GroupsShow;
use Platform\Printing\Livewire\Jobs\Index as JobsIndex;
use Platform\Printing\Livewire\Jobs\Show as JobsShow;

class PrintingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Config laden
        $this->mergeConfigFrom(__DIR__ . '/../config/printing.php', 'printing');

        // Service Bindings
        $this->app->bind(PrintingServiceInterface::class, PrintingService::class);

        // Singleton für Hauptservice
        $this->app->singleton(PrintingService::class, function ($app) {
            return new PrintingService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Schritt 1: Config laden
        $this->mergeConfigFrom(__DIR__.'/../config/printing.php', 'printing');
        
        // Schritt 2: Existenzprüfung (config jetzt verfügbar)
        if (
            config()->has('printing.routing') &&
            config()->has('printing.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'printing',
                'title'      => 'Printing',
                'routing'    => config('printing.routing'),
                'guard'      => config('printing.guard'),
                'navigation' => config('printing.navigation'),
                'sidebar'    => config('printing.sidebar'),
            ]);
        }

        // Schritt 3: Wenn Modul registriert, Routes laden
        if (PlatformCore::getModule('printing')) {
            ModuleRouter::group('printing', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });

            // API Routes für CloudPRNT
            if (config('printing.api.cloudprnt.enabled')) {
                ModuleRouter::group('printing', function () {
                    $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
                }, requireAuth: false);
            }
        }

        // Schritt 4: Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Schritt 5: Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/printing.php' => config_path('printing.php'),
        ], 'printing-config');

        // Schritt 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'printing');

        // Livewire Components registrieren
        Livewire::component('printing.dashboard', Dashboard::class);
        Livewire::component('printing.sidebar', Sidebar::class);
        Livewire::component('printing.printers.index', PrintersIndex::class);
        Livewire::component('printing.printers.show', PrintersShow::class);
        Livewire::component('printing.groups.index', GroupsIndex::class);
        Livewire::component('printing.groups.show', GroupsShow::class);
        Livewire::component('printing.jobs.index', JobsIndex::class);
        Livewire::component('printing.jobs.show', JobsShow::class);

        // Navigation registrieren
        $this->registerNavigation();
    }

    /**
     * Navigation registrieren
     */
    protected function registerNavigation(): void
    {
        // Navigation wird über die Config gesteuert
        // Siehe: config/printing.php -> navigation
    }
}
