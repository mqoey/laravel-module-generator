<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

use Illuminate\Filesystem\Filesystem;
use Mqondisi\ModuleGenerator\ModuleGenerationSpec;

/**
 * Creates or updates `RepositoryServiceProvider` bindings and optionally registers the provider in `config/app.php`.
 *
 * Duplicate interface bindings are skipped. Provider registration uses conservative string insertion (Laravel 10 style).
 */
final class RepositoryServiceProviderRegistrar
{
    private const PROVIDER_PATH = 'app/Providers/RepositoryServiceProvider.php';

    private const CONFIG_APP_PATH = 'config/app.php';

    public function __construct(
        private readonly Filesystem $files,
        private readonly StubRenderer $stubRenderer,
    ) {
    }

    public function register(string $basePath, ModuleGenerationSpec $spec, GenerationReport $report): void
    {
        $providerAbsolute = $basePath.DIRECTORY_SEPARATOR.self::PROVIDER_PATH;
        $providerRelative = self::PROVIDER_PATH;

        if (! $this->files->exists($providerAbsolute)) {
            $bindings = $this->buildBindingBody($spec);
            $content = $this->stubRenderer->render('repository-service-provider.stub', [
                '{{ bindings }}' => $bindings,
            ]);
            $this->files->ensureDirectoryExists(dirname($providerAbsolute));
            $this->files->put($providerAbsolute, $content);
            $report->record($providerRelative, FileWriteOutcome::Created);

            $this->registerProviderInConfigApp($basePath, $report);

            return;
        }

        $content = (string) $this->files->get($providerAbsolute);
        $blocks = $this->bindingBlocksToAppend($spec, $content);
        if ($blocks === []) {
            $report->record($providerRelative, FileWriteOutcome::Skipped, 'bindings already registered');
        } else {
            $snippet = implode("\n\n", $blocks);
            $updated = $this->appendInsideRegister($content, $snippet);
            if ($updated !== null) {
                $this->files->put($providerAbsolute, $updated);
                $report->record($providerRelative, FileWriteOutcome::Overwritten, 'bindings merged');
            } else {
                $report->record($providerRelative, FileWriteOutcome::Skipped, 'could not insert into register()');
            }
        }

        $this->registerProviderInConfigApp($basePath, $report);
    }

    /**
     * @return list<string>
     */
    private function bindingBlocksToAppend(ModuleGenerationSpec $spec, string $existing): array
    {
        $blocks = [];

        if ($spec->withDao && $spec->daoInterfaceName !== null && $spec->daoName !== null) {
            $daoInterface = 'App\\DAO\\Interfaces\\'.$spec->daoInterfaceName;
            if (! $this->bindingAlreadyRegistered($existing, $daoInterface)) {
                $blocks[] = $this->formatMultilineBind(
                    $daoInterface,
                    'App\\DAO\\'.$spec->daoName
                );
            }
        }

        $repositoryInterface = 'App\\Repositories\\Interfaces\\'.$spec->interfaceName;
        if (! $this->bindingAlreadyRegistered($existing, $repositoryInterface)) {
            $blocks[] = $this->formatMultilineBind(
                $repositoryInterface,
                'App\\Repositories\\'.$spec->repositoryName
            );
        }

        return $blocks;
    }

    private function bindingAlreadyRegistered(string $fileContent, string $interfaceFqn): bool
    {
        return str_contains($fileContent, '\\'.$interfaceFqn.'::class')
            || str_contains($fileContent, $interfaceFqn.'::class');
    }

    private function formatMultilineBind(string $interfaceFqn, string $implementationFqn): string
    {
        return sprintf(
            '$this->app->bind('."\n".'    \\%s::class,'."\n".'    \\%s::class'."\n".')',
            $interfaceFqn,
            $implementationFqn
        );
    }

    private function buildBindingBody(ModuleGenerationSpec $spec): string
    {
        $blocks = $this->bindingBlocksToAppend($spec, '');
        if ($blocks === []) {
            return '        //';
        }

        return $this->indentBindingBlocks($blocks, '        ');
    }

    /**
     * @param  list<string>  $blocks
     */
    private function indentBindingBlocks(array $blocks, string $indent): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            $lines = explode("\n", $block);
            $parts[] = implode("\n", array_map(static fn (string $l): string => $indent.$l, $lines));
        }

        return implode("\n\n", $parts);
    }

    private function appendInsideRegister(string $php, string $snippet): ?string
    {
        $needle = 'function register';
        $pos = strpos($php, $needle);
        if ($pos === false) {
            return null;
        }

        $braceOpen = strpos($php, '{', $pos);
        if ($braceOpen === false) {
            return null;
        }

        $close = $this->findMatchingClosingBrace($php, $braceOpen);
        if ($close === null) {
            return null;
        }

        $indent = '        ';
        $trimmed = trim($snippet);
        if ($trimmed === '') {
            return $php;
        }

        $block = "\n".$indent.str_replace("\n", "\n".$indent, $trimmed)."\n".$indent;

        return substr($php, 0, $close).$block.substr($php, $close);
    }

    private function findMatchingClosingBrace(string $content, int $openBrace): ?int
    {
        $depth = 0;
        $len = strlen($content);
        for ($i = $openBrace; $i < $len; $i++) {
            $c = $content[$i];
            if ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function registerProviderInConfigApp(string $basePath, GenerationReport $report): void
    {
        $configRelative = self::CONFIG_APP_PATH;
        $path = $basePath.DIRECTORY_SEPARATOR.self::CONFIG_APP_PATH;
        if (! $this->files->exists($path)) {
            $report->record($configRelative, FileWriteOutcome::Skipped, 'file not found');

            return;
        }

        $content = (string) $this->files->get($path);
        if (preg_match('/\bRepositoryServiceProvider::class\b/', $content) === 1) {
            $report->record($configRelative, FileWriteOutcome::Skipped, 'provider already listed');

            return;
        }

        $providerLine = 'App\\Providers\\RepositoryServiceProvider::class';

        $markers = [
            'App\\Providers\\AppServiceProvider::class,',
        ];

        foreach ($markers as $marker) {
            if (str_contains($content, $marker)) {
                $replacement = $marker."\n        ".$providerLine.',';
                $count = 0;
                $content = str_replace($marker, $replacement, $content, $count);
                if ($count > 0) {
                    $this->files->put($path, $content);
                    $report->record($configRelative, FileWriteOutcome::Overwritten, 'provider registered');
                } else {
                    $report->record($configRelative, FileWriteOutcome::Skipped, 'no change');
                }

                return;
            }
        }

        $fallbackMarkers = [
            'App\\Providers\\RouteServiceProvider::class,',
            'App\\Providers\\AuthServiceProvider::class,',
        ];

        foreach ($fallbackMarkers as $marker) {
            if (str_contains($content, $marker)) {
                $replacement = $marker."\n        ".$providerLine.',';
                $count = 0;
                $content = str_replace($marker, $replacement, $content, $count);
                if ($count > 0) {
                    $this->files->put($path, $content);
                    $report->record($configRelative, FileWriteOutcome::Overwritten, 'provider registered');
                } else {
                    $report->record($configRelative, FileWriteOutcome::Skipped, 'no change');
                }

                return;
            }
        }

        $report->record($configRelative, FileWriteOutcome::Skipped, 'no insertion point found');
    }
}
