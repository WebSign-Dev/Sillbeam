<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

use Sillbeam\Core\Architecture\ArchitectureFinding;

/** @internal An evidence-backed relationship, or an explicitly ungrouped original finding. */
final readonly class Diagnosis
{
    /** @param 'finding'|'declared_cycle'|'declared_dependency'|'observed_reference' $basis
     * @param list<int>                   $findingIndexes positions in DiagnosticCollection::findings
     * @param list<ArchitectureFinding>   $findings       original entries, without rewriting or deduplication
     * @param list<class-string>          $modules        directed endpoints or a closed, ordered cycle witness
     * @param array<class-string, string> $descriptors
     * @param list<Resolution>            $resolutions
     */
    public function __construct(
        public string $id,
        public string $basis,
        public string $explanation,
        public array $findingIndexes,
        public array $findings,
        public array $modules = [],
        public array $descriptors = [],
        public array $resolutions = [],
    ) {
    }
}
