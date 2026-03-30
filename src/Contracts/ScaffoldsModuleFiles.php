<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Contracts;

use Mqondisi\ModuleGenerator\Generators\GenerationReport;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Writes module artifacts (stubs) into the host Laravel application and updates bindings.
 *
 * Implementations should record each path outcome on {@see GenerationReport}.
 */
interface ScaffoldsModuleFiles
{
    public function generate(ModuleGenerationSpec $spec, GenerationReport $report): void;
}
