<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Contracts;

use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Builds an immutable {@see ModuleGenerationSpec} from CLI input and feature flags.
 *
 * Replace this binding in a service provider to customize naming rules or defaults.
 */
interface ResolvesModuleGenerationSpec
{
    /**
     * @param  string  $name  Raw module name argument (e.g. "customer", "blog-post").
     * @param  string  $inertiaStack  `vue`, `react`, or `svelte` — used when generating Inertia page stubs.
     */
    public function resolve(
        string $name,
        bool $api,
        bool $inertia,
        string $inertiaStack,
        bool $tenant,
        bool $withDao,
        bool $force,
    ): ModuleGenerationSpec;
}
