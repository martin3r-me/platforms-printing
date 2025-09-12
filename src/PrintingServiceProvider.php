<?php

namespace Platform\Printing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use Platform\Printing\Contracts\PrintingServiceInterface;
use Platform\Printing\Services\PrintingService;
use Platform\Printing\Http\Middleware\VerifyPrinterBasicAuth;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
        // Modul-Registrierung nur, wenn Config & Tabelle vorhanden
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

        // API Routes für CloudPRNT - außerhalb des Modul-Systems registrieren
        if (config('printing.api.cloudprnt.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        // Web-Routen nur laden, wenn das Modul registriert wurde
        if (PlatformCore::getModule('printing')) {
            ModuleRouter::group('printing', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/printing.php' => config_path('printing.php'),
        ], 'printing-config');

        // Middleware registrieren
        $this->app['router']->aliasMiddleware('verify.printer.basic', VerifyPrinterBasicAuth::class);

        // CloudPRNT Log-Kanal registrieren
        $this->app->make('log')->extend('cloudprnt', function ($app, $config) {
            return new \Monolog\Logger('cloudprnt', [
                new \Monolog\Handler\StreamHandler(
                    storage_path('logs/cloudprnt.log'),
                    \Monolog\Logger::INFO
                ),
            ]);
        });

        // Migrations, Views, Livewire-Komponenten
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'printing');
        $this->registerLivewireComponents();
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Printing\\Livewire';
        $prefix = 'printing';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!is_string($class) || !class_exists($class)) {
                continue;
            }

            // printing.dashboard aus printing + dashboard.php
            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
