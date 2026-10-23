<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

/** Shared analysis entry point, independent of the container and console. */
final class ArchitectureAnalyzer
{
    public function __construct(private readonly ?SourceInspector $inspector = null)
    {
    }

    /** @param array<string, mixed> $config */
    public function analyze(DiscoveryOptions $options, array $config = []): ArchitectureAnalysisResult
    {
        // A fresh index and collector prevent stale sources or findings between runs.
        $inspector = $this->inspector ?? new SourceInspector();
        $findings = new ArchitectureFindings();
        $architecture = null;
        $references = [];
        $cause = null;
        $discovery = null;
        try {
            $discovery = (new ArchitectureDiscovery($inspector))->discover($options);
            $architecture = (new ArchitectureCompiler())->collect($discovery, $config, $findings);
            if (!$findings->hasFatal()) {
                $references = (new CodeDependencyAnalyzer($inspector))->collect($discovery, $architecture, $options, $findings);
            }
        } catch (ArchitectureValidationException $error) {
            foreach ($error->result->findings as $finding) {
                $findings->add($finding);
            }
            $cause = $error->getPrevious();
        }

        return new ArchitectureAnalysisResult($findings->all(), $architecture, $references, $cause, $discovery);
    }
}
