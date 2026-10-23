<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

final readonly class ArchitectureReport
{
    /** @param array<string, mixed> $data */
    public function __construct(public array $data)
    {
    }

    /** @param array<string, mixed> $config */
    public static function fromAnalysis(ArchitectureAnalysisResult $analysis, array $config = []): self
    {
        $graph = $analysis->architecture;
        $catalog = $graph->modules ?? $analysis->discovery->modules ?? [];
        $projects = $graph->projects ?? $analysis->discovery->projects ?? [];
        ksort($catalog);
        ksort($projects);
        $active = array_fill_keys($graph?->usedModules() ?? [], true);
        $modules = [];
        foreach ($catalog as $module) {
            $projectId = null === $module->projectClass ? null : ($projects[$module->projectClass]->id ?? null);
            $override = $config['modules'][$module->key($projectId)] ?? (null === $module->projectClass ? ($config['modules'][$module->id] ?? []) : []);
            $enabled = null !== $graph ? $module->enabled : ($analysis->isComplete() ? (bool) ($override['enabled'] ?? true) : null);
            $used = null === $graph ? null : isset($active[$module->class]);
            $references = $analysis->references[$module->class] ?? [];
            $modules[] = [
                'id' => $module->id, 'class' => $module->class, 'scope' => $module->isPrivate() ? 'private' : 'shared',
                'project' => $module->projectClass, 'enabled' => $enabled, 'active' => $used,
                'inactive_reason' => null === $used ? 'unknown' : ($used ? null : ($enabled ? 'unassigned' : 'disabled')),
                'directory' => $module->directory, 'descriptor' => $module->descriptorFile,
                'declared_dependencies' => $module->dependencies,
                'observed_dependencies' => array_values(array_unique(array_column($references, 'module'))),
                'references' => $references,
                'activation_reasons' => $graph?->activationReasons($module->class) ?? [],
                'controllers' => $module->controllerDirectories, 'entities' => $module->entityDirectories,
            ];
        }
        $projectData = [];
        foreach ($projects as $project) {
            $data = get_object_vars($project);
            if (null === $graph) {
                $data['modules'] = null;
                $data['hosts'] = array_map(static fn ($host): string => (string) $host, $config['projects'][$project->id]['hosts'] ?? []);
            }
            $projectData[] = $data;
        }

        return new self([
            'schema_version' => 1,
            'status' => !$analysis->isComplete() ? 'unavailable' : ($analysis->isValid() ? 'valid' : 'violations'),
            'valid' => $analysis->isValid(), 'complete' => $analysis->isComplete(),
            'findings' => array_map(static fn (ArchitectureFinding $finding): array => get_object_vars($finding), $analysis->findings),
            'modules' => $modules, 'projects' => $projectData,
        ]);
    }

    public static function unavailable(\Throwable $error): self
    {
        return self::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('report.execution', $error->getMessage(), ['exception' => $error::class], 'fatal'),
        ]));
    }

    public function exitCode(): int
    {
        return match ($this->data['status']) {
            'valid' => 0,
            'violations' => 1,
            default => 2,
        };
    }

    public function toJson(): string
    {
        $data = $this->data;
        foreach ($data['findings'] as &$finding) {
            $finding['context'] = (object) $finding['context'];
        }
        unset($finding);

        return json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
