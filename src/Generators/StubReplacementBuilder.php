<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

use Illuminate\Support\Str;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Produces placeholder maps for stub templates (e.g. `{{ model }}` → `Customer`).
 */
final class StubReplacementBuilder
{
    /**
     * @return array<string, string> Placeholder => replacement (stub file tokens).
     */
    public function build(ModuleGenerationSpec $spec): array
    {
        $modelVariable = Str::camel($spec->modelName);
        $pluralModel = Str::pluralStudly($spec->modelName);

        return [
            '{{ model }}' => $spec->modelName,
            '{{ table }}' => $spec->tableName,
            '{{ repository }}' => $spec->repositoryName,
            '{{ interface }}' => $spec->interfaceName,
            '{{ controller }}' => $spec->controllerName,
            '{{ dto }}' => $spec->dtoName,
            '{{ dao }}' => $spec->daoName ?? '',
            '{{ dao_interface }}' => $spec->daoInterfaceName ?? '',
            '{{ model_variable }}' => $modelVariable,
            '{{ plural_model }}' => $pluralModel,
            '{{ inertia_page }}' => $pluralModel.'/Index',
            '{{ migration_columns }}' => $this->migrationColumns($spec),
            '{{ fillable }}' => $this->fillableArray($spec),
            '{{ tenant_imports }}' => $this->tenantImports($spec),
            '{{ tenant_booted }}' => $this->tenantBooted($spec),
            '{{ tenant_scope }}' => $this->tenantScope($spec),
            '{{ repository_tenant_docblock }}' => $this->tenantClassDocblock($spec),
            '{{ dao_tenant_docblock }}' => $this->tenantClassDocblock($spec),
        ];
    }

    private function migrationColumns(ModuleGenerationSpec $spec): string
    {
        if ($spec->tenant) {
            return <<<'PHP'
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
PHP;
        }

        return <<<'PHP'
            $table->id();
            $table->string('name');
            $table->timestamps();
PHP;
    }

    private function fillableArray(ModuleGenerationSpec $spec): string
    {
        if ($spec->tenant) {
            return "'name', 'team_id'";
        }

        return "'name'";
    }

    private function tenantImports(ModuleGenerationSpec $spec): string
    {
        if (! $spec->tenant) {
            return '';
        }

        return 'use Illuminate\Database\Eloquent\Builder;';
    }

    private function tenantBooted(ModuleGenerationSpec $spec): string
    {
        if (! $spec->tenant) {
            return '';
        }

        return <<<'PHP'


    protected static function booted(): void
    {
        static::addGlobalScope('team', function (Builder $query): void {
            if (! auth()->check()) {
                return;
            }

            $teamId = data_get(auth()->user(), 'team_id');
            if ($teamId === null || $teamId === '') {
                return;
            }

            $query->where($query->getModel()->getTable().'.team_id', $teamId);
        });
    }
PHP;
    }

    private function tenantScope(ModuleGenerationSpec $spec): string
    {
        if (! $spec->tenant) {
            return '';
        }

        return <<<'PHP'


    public function scopeTeam(Builder $query, int|string $teamId): void
    {
        $query->where($query->getModel()->getTable().'.team_id', $teamId);
    }
PHP;
    }

    private function tenantClassDocblock(ModuleGenerationSpec $spec): string
    {
        if (! $spec->tenant) {
            return '';
        }

        $model = $spec->modelName;

        return <<<DOC
/**
 * Tenant: queries use {$model}; a team global scope is registered in booted() when the authenticated user has a non-empty team_id (via data_get, no concrete User type required).
 */

DOC;
    }
}
