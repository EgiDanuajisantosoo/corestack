<?php

namespace Corestack\ArchSupport;

use Corestack\ArchSupport\Console\Commands\ArchDoctorCommand;
use Corestack\ArchSupport\Console\Commands\ArchInstallCommand;
use Corestack\ArchSupport\Console\Commands\ArchMakeCommand;
use Corestack\ArchSupport\Support\StubPublisher;
use Illuminate\Support\ServiceProvider;

class ArchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arch-support.php', 'arch-support');

        $this->app->singleton(StubPublisher::class, function () {
            return new StubPublisher(__DIR__ . '/../stubs');
        });
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ArchInstallCommand::class,
            ArchDoctorCommand::class,
            ArchMakeCommand::class,
        ]);

        $this->publishes([
            __DIR__ . '/../config/arch-support.php' => config_path('arch-support.php'),
        ], 'arch-support-config');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/corestack-arch-support'),
        ], 'arch-support-stubs');
    }
}
