<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

/**
 * Collects per-file outcomes for console reporting after a scaffold run.
 */
final class GenerationReport
{
    /**
     * @var list<array{path: string, outcome: FileWriteOutcome, note: ?string}>
     */
    private array $entries = [];

    /**
     * @param  string  $relativePath  Project-relative path using forward slashes (e.g. `app/Models/Foo.php`).
     */
    public function record(string $relativePath, FileWriteOutcome $outcome, ?string $note = null): void    {
        $this->entries[] = [
            'path' => $relativePath,
            'outcome' => $outcome,
            'note' => $note,
        ];
    }

    /**
     * @return list<array{path: string, outcome: FileWriteOutcome, note: ?string}>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return array{created: int, skipped: int, overwritten: int}
     */
    public function counts(): array
    {
        $created = 0;
        $skipped = 0;
        $overwritten = 0;

        foreach ($this->entries as $entry) {
            match ($entry['outcome']) {
                FileWriteOutcome::Created => $created++,
                FileWriteOutcome::Skipped => $skipped++,
                FileWriteOutcome::Overwritten => $overwritten++,
            };
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'overwritten' => $overwritten,
        ];
    }
}
