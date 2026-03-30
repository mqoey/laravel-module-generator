<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Mqondisi\ModuleGenerator\Contracts\ResolvesModuleGenerationSpec;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Default {@see ResolvesModuleGenerationSpec}: Laravel-friendly Studly / snake / plural table naming.
 */
final class ModuleNamingResolver implements ResolvesModuleGenerationSpec
{
    /** @var list<string> */
    private const INERTIA_STACKS = ['vue', 'react', 'svelte'];

    public function resolve(
        string $name,
        bool $api,
        bool $inertia,
        string $inertiaStack,
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

        $normalizedStack = strtolower(trim($inertiaStack));
        if ($normalizedStack === '') {
            $normalizedStack = 'vue';
        }
        if ($inertia && ! in_array($normalizedStack, self::INERTIA_STACKS, true)) {
            throw new InvalidArgumentException(
                'inertia-stack must be one of: '.implode(', ', self::INERTIA_STACKS)."; got [{$inertiaStack}]."
            );
        }
        if (! $inertia) {
            $normalizedStack = 'vue';
        }

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
            inertiaStack: $normalizedStack,
            tenant: $tenant,
            withDao: $withDao,
            daoName: $daoName,
            daoInterfaceName: $daoInterfaceName,
            force: $force,
        );
    }
}
