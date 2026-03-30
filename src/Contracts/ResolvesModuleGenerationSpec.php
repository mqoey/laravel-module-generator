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
     */
    public function resolve(
        string $name,
        bool $api,
        bool $inertia,
        bool $tenant,
        bool $withDao,
        bool $force,
    ): ModuleGenerationSpec;
}
