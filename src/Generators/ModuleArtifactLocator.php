<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

use Illuminate\Support\Str;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Resolves absolute filesystem paths for every artifact that {@see ModuleGenerator} may emit.
 */
final class ModuleArtifactLocator
{
    public function __construct(
        private readonly string $applicationBasePath,
        private readonly ModuleGenerationSpec $spec,
    ) {
    }

    public function migrationAbsolutePath(): string
    {
        $existing = $this->findMigrationForTable();
        if ($existing !== null) {
            return $existing;
        }

        $timestamp = date('Y_m_d_His');
        $table = $this->spec->tableName;

        return $this->join('database', 'migrations', $timestamp.'_create_'.$table.'_table.php');
    }

    public function modelAbsolutePath(): string
    {
        return $this->join('app', 'Models', $this->spec->modelName.'.php');
    }

    public function daoInterfaceAbsolutePath(): string
    {
        if ($this->spec->daoInterfaceName === null) {
            throw new \LogicException('DAO interface path requested without daoInterfaceName on spec.');
        }

        return $this->join('app', 'DAO', 'Interfaces', $this->spec->daoInterfaceName.'.php');
    }

    public function daoImplementationAbsolutePath(): string
    {
        if ($this->spec->daoName === null) {
            throw new \LogicException('DAO implementation path requested without daoName on spec.');
        }

        return $this->join('app', 'DAO', $this->spec->daoName.'.php');
    }

    public function repositoryInterfaceAbsolutePath(): string
    {
        return $this->join('app', 'Repositories', 'Interfaces', $this->spec->interfaceName.'.php');
    }

    public function baseRepositoryAbsolutePath(): string
    {
        return $this->join('app', 'Repositories', 'BaseRepository.php');
    }

    public function repositoryImplementationAbsolutePath(): string
    {
        return $this->join('app', 'Repositories', $this->spec->repositoryName.'.php');
    }

    public function dtoAbsolutePath(): string
    {
        return $this->join('app', 'Data', $this->spec->dtoName.'.php');
    }

    public function apiControllerAbsolutePath(): string
    {
        return $this->join('app', 'Http', 'Controllers', 'Api', $this->spec->controllerName.'.php');
    }

    /**
     * Web stack controller used for Inertia (non-Api namespace).
     */
    public function inertiaControllerAbsolutePath(): string
    {
        return $this->join('app', 'Http', 'Controllers', $this->spec->controllerName.'.php');
    }

    public function webControllerAbsolutePath(): string
    {
        return $this->inertiaControllerAbsolutePath();
    }

    /**
     * Inertia page path: extension matches {@see ModuleGenerationSpec::$inertiaStack} (vue / jsx / svelte).
     */
    public function inertiaPageAbsolutePath(): string
    {
        $plural = Str::pluralStudly($this->spec->modelName);
        $extension = match ($this->spec->inertiaStack) {
            'react' => 'jsx',
            'svelte' => 'svelte',
            default => 'vue',
        };

        return $this->join('resources', 'js', 'Pages', $plural, 'Index.'.$extension);
    }

    private function join(string ...$segments): string
    {
        return $this->applicationBasePath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);
    }

    private function findMigrationForTable(): ?string
    {
        $glob = $this->join('database', 'migrations', '*_create_'.$this->spec->tableName.'_table.php');
        $files = glob($glob);

        if ($files === false || $files === []) {
            return null;
        }

        return $files[0];
    }
}
