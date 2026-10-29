<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Architecture\Diagnostic\DiagnosticContext;

/**
 * Immutable analysis outcome with deterministic, deduplicated findings and source evidence.
 *
 * Any finding suppresses the runtime graph. Fatal findings mean incomplete analysis;
 * nonfatal errors can describe a complete but invalid architecture. Diagnostic evidence
 * must never be treated as a validated activation graph.
 *
 * @api
 */
final readonly class ArchitectureAnalysisResult
{
    /**
     * Sorted, deduplicated rule violations with original source evidence.
     *
     * @var list<ArchitectureFinding>
     */
    public array $findings;
    /** Validated graph, or null whenever findings exist or no graph was supplied. */
    public ?CompiledArchitecture $architecture;

    /**
     * Sorts and deduplicates findings and suppresses the supplied graph whenever findings exist.
     *
     * @param list<ArchitectureFinding>                                                                      $findings              available findings; sorted and deduplicated on construction
     * @param array<class-string, list<array{class: string, file: string, line: int, target_file: string}>>  $applicationReferences informational references to indexed classes outside modules; never activation edges or violations
     * @param array<class-string, list<array{module: class-string, class: string, file: string, line: int}>> $references            observed module edges keyed by source module; each item retains target class and source location
     * @param CompiledArchitecture|null                                                                      $architecture          candidate graph retained only for a valid result
     * @param \Throwable|null                                                                                $cause                 original fatal failure retained by throwIfInvalid()
     * @param DiscoveryResult|null                                                                           $discovery             available catalog evidence, possibly absent after discovery failure
     * @param DiagnosticContext|null                                                                         $diagnosticContext     internal explanatory evidence; never a runtime graph
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

    /**
     * Reports whether analysis has no fatal findings; a complete result may still contain errors.
     */
    public function isComplete(): bool
    {
        foreach ($this->findings as $finding) {
            if ('fatal' === $finding->severity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reports whether there are no findings; does not assert that a graph was supplied to the constructor.
     */
    public function isValid(): bool
    {
        return [] === $this->findings;
    }

    /**
     * Rejects any finding while retaining the complete result and original fatal cause.
     *
     * @throws ArchitectureValidationException if the result contains any error or fatal finding
     */
    public function throwIfInvalid(): void
    {
        if (!$this->isValid()) {
            throw new ArchitectureValidationException($this, $this->cause);
        }
    }
}
