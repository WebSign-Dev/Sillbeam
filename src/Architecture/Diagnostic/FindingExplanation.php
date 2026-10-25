<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

use Sillbeam\Core\Architecture\ArchitectureFinding;

/** @internal Rule communication alongside the unchanged original evidence. */
final readonly class FindingExplanation
{
    /** @param list<Resolution> $resolutions */
    public function __construct(public ArchitectureFinding $finding, public string $problem, public string $impact, public array $resolutions, public bool $caution = false)
    {
    }

    public function location(): ?string
    {
        $file = $this->finding->context['file'] ?? null;
        if (!\is_string($file) || '' === $file) {
            return null;
        }
        $line = $this->finding->context['line'] ?? null;

        return $file.(\is_int($line) && $line > 0 ? ':'.$line : '');
    }
}
