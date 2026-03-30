<?php

declare(strict_types=1);

namespace Mqondisi\ModuleGenerator\Generators;

/**
 * Result of attempting to write a single path during module scaffolding.
 */
enum FileWriteOutcome: string
{
    /** Path did not exist before this run. */
    case Created = 'created';

    /** Path already existed and `--force` was not used. */
    case Skipped = 'skipped';

    /** Path existed and was replaced because `--force` was set. */
    case Overwritten = 'overwritten';
}
