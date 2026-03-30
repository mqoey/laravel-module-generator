<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator;

/**
 * Immutable description of one `make:module` run: derived names and feature toggles.
 *
 * All flags are independent and may be combined (e.g. API + tenant + DAO).
 */
final class ModuleGenerationSpec
{
    /**
     * @param  string  $inputName  Trimmed argument as entered on the CLI.
     * @param  string  $modelName  StudlyCase singular (e.g. Customer).
     * @param  string  $tableName  Snake plural table name (e.g. customers).
     * @param  string  $repositoryName  Concrete repository class short name.
     * @param  string  $interfaceName  Repository contract short name (…RepositoryInterface).
     * @param  string  $dtoName  Spatie Data DTO short name.
     * @param  string  $controllerName  HTTP controller short name (shared basename for Api and web when both are generated).
     * @param  bool  $api  Emit `app/Http/Controllers/Api/{Controller}.php`.
     * @param  bool  $inertia  Emit web `Controller` + page stub under `resources/js/Pages`.
     * @param  string  $inertiaStack  When `inertia` is true: `vue`, `react`, or `svelte` (file extension and stub). Ignored when `inertia` is false.
     * @param  bool  $tenant  Team column, model scopes, and tenant-aware docblocks.
     * @param  bool  $withDao  Use DAO layer; repository depends on DAO only (no BaseRepository path).
     * @param  string|null  $daoName  Short class name of DAO implementation, or null when `withDao` is false.
     * @param  string|null  $daoInterfaceName  Short name of DAO contract, or null when `withDao` is false.
     * @param  bool  $force  When true, replace files that already exist on disk.
     */
    public function __construct(
        public readonly string $inputName,
        public readonly string $modelName,
        public readonly string $tableName,
        public readonly string $repositoryName,
        public readonly string $interfaceName,
        public readonly string $dtoName,
        public readonly string $controllerName,
        public readonly bool $api,
        public readonly bool $inertia,
        public readonly string $inertiaStack,
        public readonly bool $tenant,
        public readonly bool $withDao,
        public readonly ?string $daoName,
        public readonly ?string $daoInterfaceName,
        public readonly bool $force,
    ) {
    }
}
