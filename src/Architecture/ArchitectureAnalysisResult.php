<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Architecture\Diagnostic\DiagnosticContext;

final readonly class ArchitectureAnalysisResult
{
    /** @var list<ArchitectureFinding> */
    public array $findings;
    public ?CompiledArchitecture $architecture;

    /** @param list<ArchitectureFinding> $findings
     * @param array<class-string, list<array{class: string, file: string, line: int, target_file: string}>>  $applicationReferences
     * @param array<class-string, list<array{module: class-string, class: string, file: string, line: int}>> $references
     */
    public function __construct(
        array $findings = [],
        ?CompiledArchitecture $architecture = null,
        public array $references = [],
        private ?\Throwable $cause = null,
        public ?DiscoveryResult $discovery = null,
        public array $applicationReferences = [],
        /** @internal Evidence only; never a runtime graph. */
        public ?DiagnosticContext $diagnosticContext = null,
    ) {
        $collection = new ArchitectureFindings();
        foreach ($findings as $finding) {
            $collection->add($finding);
        }
        $this->findings = $collection->all();
        // Invalid candidate graphs are never exposed for runtime use.
        $this->architecture = [] === $this->findings ? $architecture : null;
    }

    public function isComplete(): bool
    {
        foreach ($this->findings as $finding) {
            if ('fatal' === $finding->severity) {
                return false;
            }
        }

        return true;
    }

    public function isValid(): bool
    {
        return [] === $this->findings;
    }

    public function throwIfInvalid(): void
    {
        if (!$this->isValid()) {
            throw new ArchitectureValidationException($this, $this->cause);
        }
    }
}
