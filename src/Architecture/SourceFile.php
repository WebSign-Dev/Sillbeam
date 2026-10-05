<?php

declare(strict_types=1);

namespace Nexum\Architecture;

final readonly class SourceFile
{
    /** @param list<class-string> $classes
     * @param array<class-string, string>           $descriptors
     * @param list<array{class: string, line: int}> $references
     */
    public function __construct(public string $path, public array $classes, public array $descriptors, public array $references = [])
    {
    }
}
