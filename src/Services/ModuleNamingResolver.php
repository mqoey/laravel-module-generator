<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Services;

use Illuminate\Support\Str;
use Mqondisi\ModuleGenerator\Contracts\ResolvesModuleGenerationSpec;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Default {@see ResolvesModuleGenerationSpec}: Laravel-friendly Studly / snake / plural table naming.
 */
final class ModuleNamingResolver implements ResolvesModuleGenerationSpec
{
    public function resolve(
        string $name,
        bool $api,
        bool $inertia,
        bool $tenant,
        bool $withDao,
        bool $force,
    ): ModuleGenerationSpec {
        $inputName = trim($name);
        $modelName = Str::studly($inputName);

        $tableName = Str::snake(Str::pluralStudly($modelName));
        $repositoryName = $modelName.'Repository';
        $interfaceName = $repositoryName.'Interface';
        $dtoName = $modelName.'Dto';
        $controllerName = $modelName.'Controller';
        $daoName = $withDao ? $modelName.'Dao' : null;
        $daoInterfaceName = $withDao ? $modelName.'DaoInterface' : null;

        return new ModuleGenerationSpec(
            inputName: $inputName,
            modelName: $modelName,
            tableName: $tableName,
            repositoryName: $repositoryName,
            interfaceName: $interfaceName,
            dtoName: $dtoName,
            controllerName: $controllerName,
            api: $api,
            inertia: $inertia,
            tenant: $tenant,
            withDao: $withDao,
            daoName: $daoName,
            daoInterfaceName: $daoInterfaceName,
            force: $force,
        );
    }
}
