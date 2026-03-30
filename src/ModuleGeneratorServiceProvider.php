<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Mqondisi\ModuleGenerator\Commands\MakeModuleCommand;
use Mqondisi\ModuleGenerator\Contracts\ResolvesModuleGenerationSpec;
use Mqondisi\ModuleGenerator\Contracts\ScaffoldsModuleFiles;
use Mqondisi\ModuleGenerator\Generators\ModuleGenerator;
use Mqondisi\ModuleGenerator\Generators\RepositoryServiceProviderRegistrar;
use Mqondisi\ModuleGenerator\Generators\StubReplacementBuilder;
use Mqondisi\ModuleGenerator\Generators\StubRenderer;
use Mqondisi\ModuleGenerator\Services\ModuleNamingResolver;

/**
 * Registers package services, console command, and optional contract bindings for extension/testing.
 *
 * Compatible with Laravel 10.x and 11.x (`illuminate/*` ^10|^11).
 */
class ModuleGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Filesystem::class, static fn (): Filesystem => new Filesystem());

        $this->app->singleton(StubRenderer::class, function (): StubRenderer {
            return new StubRenderer(__DIR__.DIRECTORY_SEPARATOR.'Stubs');
        });

        $this->app->singleton(StubReplacementBuilder::class);

        $this->app->singleton(ModuleNamingResolver::class);
        $this->app->singleton(ResolvesModuleGenerationSpec::class, ModuleNamingResolver::class);

        $this->app->singleton(RepositoryServiceProviderRegistrar::class);

        $this->app->singleton(ModuleGenerator::class);
        $this->app->singleton(ScaffoldsModuleFiles::class, ModuleGenerator::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModuleCommand::class,
            ]);
        }
    }
}
