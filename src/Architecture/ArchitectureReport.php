<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

use Sillbeam\Core\Architecture\Console\ArchitecturePresentation;

final readonly class ArchitectureReport
{
    /** @param array<string, mixed> $data
     * @param array<string, mixed> $presentation internal scalar view, never serialized to JSON
     */
    public function __construct(public array $data, public array $presentation = [])
    {
    }

    /** @param array<string, mixed> $config */
    public static function fromAnalysis(ArchitectureAnalysisResult $analysis, array $config = [], ?string $projectDir = null): self
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
            $applicationReferences = $analysis->applicationReferences[$module->class] ?? [];
            if ([] !== $applicationReferences) {
                $index = array_key_last($modules);
                $modules[$index]['application_dependencies'] = array_values(array_unique(array_column($applicationReferences, 'class')));
                $modules[$index]['application_references'] = $applicationReferences;
            }
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
            'findings' => array_map(static function (ArchitectureFinding $finding): array {
                $data = get_object_vars($finding);
                if (null !== ($suggestion = $finding->suggestion())) {
                    $data['context']['suggestion'] = $suggestion;
                }

                return $data;
            }, $analysis->findings),
            'modules' => $modules, 'projects' => $projectData,
        ], ArchitecturePresentation::fromAnalysis($analysis, $projectDir));
    }

    public static function unavailable(\Throwable $error): self
    {
        return self::fromAnalysis(new ArchitectureAnalysisResult([
            new ArchitectureFinding('report.execution', $error->getMessage(), ['exception' => $error::class], 'fatal'),
        ]));
    }

    /** @param self|\Closure(): self $provider */
    public static function collect(self|\Closure $provider): self
    {
        $level = ob_get_level();
        ob_start();
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            if ($severity & (\E_DEPRECATED | \E_USER_DEPRECATED | \E_NOTICE | \E_USER_NOTICE)) {
                return true;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            return $provider instanceof \Closure ? $provider() : $provider;
        } catch (\Throwable $error) {
            return self::unavailable($error);
        } finally {
            restore_error_handler();
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
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
