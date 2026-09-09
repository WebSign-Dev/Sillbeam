<?php

declare(strict_types=1);

namespace Nexum\Reporting;

final readonly class Report
{
    /** @param list<string> $columns
     * @param list<array<string, scalar|null>> $rows
     */
    public function __construct(public array $columns, public array $rows)
    {
        if ([] === $columns || \count($columns) !== \count(array_unique($columns))) {
            throw new \InvalidArgumentException('Report columns must be non-empty and unique.');
        }
    }
}
