<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

/**
 * Loads `.stub` files from disk and replaces longest placeholder keys first to avoid partial collisions.
 */
final class StubRenderer
{
    public function __construct(
        private readonly string $stubDirectory,
    ) {
    }

    /**
     * @param  array<string, string>  $replacements  Token (e.g. `{{ model }}`) => value.
     */
    public function render(string $stubFileName, array $replacements): string
    {
        $path = $this->stubDirectory.DIRECTORY_SEPARATOR.$stubFileName;
        $template = (string) file_get_contents($path);

        $keys = array_keys($replacements);
        usort($keys, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $sorted = [];
        foreach ($keys as $key) {
            $sorted[$key] = $replacements[$key];
        }

        return str_replace(array_keys($sorted), array_values($sorted), $template);
    }
}
