<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Architecture\Diagnostic\DiagnosticContext;

/**
 * Container-independent entry point for source discovery, graph validation and static dependency analysis.
 *
 * Expected source and architecture failures become structured findings. Application descriptor
 * code may execute during discovery; arbitrary application exceptions and programming errors propagate.
 *
 * @api
 */
final class ArchitectureAnalyzer
{
    /**
     * Optionally reuses a build-scoped source index; the default creates a fresh index per analyze() call.
     *
     * @param SourceInspector|null $inspector internal index injection for one build; omit for independent runs
     */
    public function __construct(private readonly ?SourceInspector $inspector = null)
    {
    }

    /**
     * Reads configured source roots, instantiates descriptors and collects available findings.
     *
     * Returns structured expected validation failures rather than throwing them. Unexpected
     * application exceptions propagate; option/configuration resolution happens before this call.
     * Does not compile a Symfony container, activate services or write source files.
     *
     * @param DiscoveryOptions     $options normalized roots, namespaces and exclusion patterns
     * @param array<string, mixed> $config  resolved sillbeam configuration, including module overrides and Project hosts
     *
     * @return ArchitectureAnalysisResult findings, available source evidence and a graph only when valid
     */
    public function analyze(DiscoveryOptions $options, array $config = []): ArchitectureAnalysisResult
    {
        // A fresh index and collector prevent stale sources or findings between runs.
        $inspector = $this->inspector ?? new SourceInspector();
        $findings = new ArchitectureFindings();
        $architecture = null;
        $references = [];
        $applicationReferences = [];
        $cause = null;
        $discovery = null;
        try {
            $discovery = (new ArchitectureDiscovery($inspector))->discover($options);
            $architecture = (new ArchitectureCompiler())->collect($discovery, $config, $findings);
            if (!$findings->hasFatal()) {
                $references = (new CodeDependencyAnalyzer($inspector))->collect($discovery, $architecture, $options, $findings, $applicationReferences);
            }
        } catch (ArchitectureValidationException $error) {
            foreach ($error->result->findings as $finding) {
                $findings->add($finding);
            }
            $cause = $error->getPrevious();
        }

        $diagnosticContext = DiagnosticContext::fromEvidence($discovery, $architecture, !$findings->hasFatal(), $references, $applicationReferences);

        return new ArchitectureAnalysisResult($findings->all(), $architecture, $references, $cause, $discovery, $applicationReferences, $diagnosticContext);
    }
}
