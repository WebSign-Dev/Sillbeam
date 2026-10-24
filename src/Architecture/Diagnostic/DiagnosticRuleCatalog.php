<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Diagnostic;

use Sillbeam\Core\Architecture\ArchitectureFinding;

/** @internal Communication only: never changes rules, Findings, runtime graphs or reports. */
final readonly class DiagnosticRuleCatalog
{
    public function __construct(private DiagnosticContext $context, private bool $analysisComplete)
    {
    }

    /** @param list<class-string> $cycle Verified ordered witness, never sorted Finding participants. */
    public function explain(ArchitectureFinding $finding, array $cycle = []): FindingExplanation
    {
        [$problem, $impact, $step] = $this->wording($finding->rule);
        $files = $this->files($finding);
        $resolutions = [new Resolution('technical', [$step.' Rerun architecture analysis after correcting the reported issue.'], $files)];
        if ('Unknown rule.' === $problem) {
            $problem = $finding->message;
        }
        $module = $this->text($finding, 'module');
        $target = $this->text($finding, 'target');
        $project = $this->text($finding, 'project');
        $class = $this->text($finding, 'class');
        if (null !== $class) {
            $problem .= ' Reported class: '.$class.'.';
        }
        if (null !== $module) {
            $problem .= ' Module: '.$module.'.';
        }
        if (null !== $target) {
            $problem .= ' Target: '.$target.'.';
        }
        if (null !== $project) {
            $problem .= ' Project: '.$project.'.';
        }

        switch ($finding->rule) {
            case 'code.undeclared_dependency':
                [$impact, $resolutions] = $this->undeclared($finding, $impact, $resolutions);
                break;
            case 'dependency.cycle':
                if ([] !== $cycle) {
                    $problem = 'Declared dependencies form a cycle: '.implode(' → ', $cycle).'.';
                    $files = array_values($this->context->graph()->sourceDescriptors($cycle));
                }
                $impact = 'The declared graph must be acyclic. Sillbeam cannot determine which edge is wrong for your domain.';
                $resolutions = [new Resolution('option', ['Review the involved descriptors'.([] === $files ? '' : ': '.implode(', ', $files)).'. Choose the intended dependency direction; remove or redirect a coupling, or extract shared contracts. Rerun architecture analysis.'], $files)];
                break;
            case 'dependency.private':
            case 'code.private':
            case 'project.private_module':
                [$impact, $resolutions] = $this->privateAccess($finding, $files);
                break;
            case 'dependency.disabled':
            case 'code.inactive':
                [$problem, $impact, $resolutions] = $this->inactive($finding, $problem, $files);
                break;
            case 'dependency.unknown':
            case 'project.unknown_module':
                $descriptor = 'dependency.unknown' === $finding->rule ? ($this->context->modules[$module ?? '']->descriptorFile ?? null) : ($this->context->projects[$project ?? '']->descriptorFile ?? null);
                $resolutions = [new Resolution('technical', ['Verify '.($target ?? 'the reported target class').' in '.($descriptor ?? 'the reported descriptor').' against the intended descriptor and configured discovery roots/excludes. Correct the declaration or restore the intended descriptor, then rerun architecture analysis.'], $files)];
                break;
            case 'module.unknown_override':
                $key = $this->text($finding, 'override');
                if (null !== $key) {
                    $address = 'sillbeam.modules["'.$key.'"]';
                    $problem .= ' Configured key: '.$address.'.';
                    $resolutions = [new Resolution('technical', ['Compare '.$address.' with discovered module keys (shared:<id> or project:<project-id>:<module-id>); correct the key or remove the stale override, then rerun architecture analysis.'], $files, [$address])];
                }
                break;
            case 'project.unknown_configuration':
                if (null !== $project) {
                    $address = 'sillbeam.projects["'.$project.'"]';
                    $resolutions = [new Resolution('technical', ['Compare '.$address.' with discovered Project IDs; correct the ID or remove the stale configuration, then rerun architecture analysis.'], $files, [$address])];
                }
                break;
            case 'project.host_invalid':
            case 'project.host_required':
            case 'project.host_duplicate':
                [$problem, $impact, $resolutions] = $this->hosts($finding, $problem, $impact, $step, $files);
                break;
            case 'module.duplicate_key':
            case 'project.duplicate_id':
                [$problem, $resolutions] = $this->duplicate($finding, $problem, $files);
                break;
        }
        if ('fatal' === $finding->severity && $problem !== $finding->message) {
            // Parser/descriptor/execution messages may carry evidence absent from scalar context.
            $problem .= ' Reported error: '.$finding->message;
        }
        if (!$this->analysisComplete) {
            $impact .= ' Analysis incomplete: resolve the fatal blocker and rerun before relying on graph or activation conclusions.';
            if ('fatal' !== $finding->severity) {
                $resolutions = [new Resolution('technical', ['Resolve the fatal analysis blockers first, then rerun architecture analysis to reassess this finding.'], $files)];
            }
        }

        return new FindingExplanation($finding, $problem, $impact, $resolutions);
    }

    /** @return array{string, string, string} */
    private function wording(string $rule): array
    {
        return match ($rule) {
            'dependency.unknown' => ['A declared dependency target was not discovered.', 'Without a discovered module descriptor, its identity and activation cannot be validated.', 'Verify the target class and descriptor against configured discovery roots and excludes.'],
            'project.unknown_module' => ['A module required by a Project activation graph was not discovered.', 'The required module has no discovered descriptor to validate or activate.', 'Verify the referenced module class in the relevant descriptor and discovery configuration.'],
            'code.undeclared_dependency' => ['Code crosses a module boundary without a declared direct dependency.', 'A transitive dependency does not permit direct code access. Verify the declarations and reference evidence before changing the graph.', 'Verify the source and target descriptors and the reported reference.'],
            'dependency.private', 'code.private', 'project.private_module' => ['Access crosses a private Project boundary.', 'Declaring a dependency does not grant access to another Project\'s private module.', 'Keep private implementation within its owning Project or deliberately move reusable contracts into a shared module.'],
            'dependency.cycle' => ['Declared module dependencies contain a cycle.', 'The declared graph must be acyclic; a valid dependency direction requires a domain decision.', 'Review the involved descriptor declarations and choose the intended dependency direction.'],
            'dependency.disabled' => ['An active graph requires a disabled module.', 'A required module cannot be activated while disabled; its current configuration address is unknown without identity evidence.', 'Verify the module identity and enabled override before deciding whether to enable it or remove the requirement.'],
            'code.inactive' => ['Code references an inactive module.', 'The target is outside the analyzed active graph; whether it is disabled or unassigned is unknown without activation evidence.', 'Verify the target enabled state and Project assignment before changing activation or references.'],
            'code.unknown_class' => ['A referenced application-owned class cannot be resolved by the bounded source/Composer check.', 'The reference cannot be matched to indexed, loaded or mapped application source. This is not a check of all PHP autoloading, external Composer types or Symfony autowiring.', 'Check the reported class spelling/import, intended source file and application Composer PSR-4 mapping; regenerate the autoloader if that mapping changed.'],
            'module.unknown_override' => ['A module configuration override matches no discovered module.', 'The configured key cannot select a module in its declared scope.', 'Compare the override key with discovered shared:<id> and project:<project-id>:<module-id> keys; correct it or remove the stale override.'],
            'project.unknown_configuration' => ['Project configuration matches no discovered Project.', 'The configured Project ID cannot select a discovered Project.', 'Compare the configured ID with discovered Project IDs; correct it or remove stale configuration.'],
            'project.host_invalid' => ['A configured Project host is not an exact hostname.', 'Project host routing requires an exact hostname without a scheme, port, path or wildcard.', 'Replace the invalid value with the intended exact hostname, without a scheme, port, path or wildcard.'],
            'project.host_required' => ['A Project has no configured host in a multi-Project application.', 'Every Project needs at least one exact host so request selection is defined.', 'Choose an exact host for this Project and configure its hosts list.'],
            'project.host_duplicate' => ['An exact host is assigned to more than one Project.', 'Host-based Project selection is ambiguous.', 'Decide which Project should own the host and remove that host from the other Project configurations.'],
            'module.duplicate_key' => ['Multiple modules have the same logical key.', 'Module IDs must be unique within their shared or private Project scope; overrides and activation are ambiguous.', 'Choose unique IDs within the affected scope and update the affected configuration keys.'],
            'project.duplicate_id' => ['Multiple Projects have the same ID.', 'Project configuration cannot unambiguously select one of these Projects.', 'Choose unique Project IDs and update affected Project configuration and private module keys.'],
            'module.owner_missing' => ['A private module has no discovered owning Project.', 'The declared Project ownership cannot be resolved, so scope and activation are unknown.', 'Correct the module ownership/layout so its owning Project is discoverable, or deliberately place it in a configured shared module root.'],
            'discovery.owner_missing' => ['A module in a Project discovery root has no owning Project.', 'Its directory is not inside a discovered Project, so private ownership cannot be established.', 'Restore a discoverable owning Project or deliberately move the module into a configured shared root; verify ownership and discovery boundaries.'],
            'discovery.module_boundary' => ['A descriptor lies outside allowed module discovery boundaries.', 'Discovery cannot assign this descriptor to an allowed shared module or private Project area.', 'Move the descriptor into the intended configured module root or owning Project area, or correct the discovery root configuration.'],
            'discovery.project_boundary' => ['A descriptor lies outside allowed Project discovery boundaries.', 'Discovery cannot establish a permitted Project area.', 'Move the descriptor into the intended configured Project root or correct that discovery root configuration.'],
            'discovery.module_overlap' => ['Module areas overlap.', 'Nested or equal module areas make source ownership ambiguous.', 'Separate the reported module areas so no module contains another module descriptor.'],
            'discovery.project_overlap' => ['Project areas overlap.', 'Nested or equal Project areas make ownership ambiguous.', 'Separate the reported Project areas so no Project contains another Project descriptor.'],
            'discovery.project_inside_module' => ['A Project area is inside a module area.', 'A module cannot contain a Project; these ownership areas conflict.', 'Separate the reported Project and module areas and verify their configured roots.'],
            'discovery.descriptor_class' => ['A descriptor class does not match its expected PSR-4 class.', 'Discovery requires the declared class to match the filename and configured namespace for its directory.', 'Align the descriptor namespace, short class name, filename and configured Composer/discovery mapping with the expected class in the original finding.'],
            'discovery.descriptor_file' => ['A descriptor class resolves to a different file.', 'The loaded declaration does not belong to the reported descriptor file; its metadata cannot be trusted for that file.', 'Inspect the class declaration and Composer mappings, remove unintended duplicate mappings/declarations and ensure the reported class belongs to the intended descriptor file.'],
            'discovery.descriptor_metadata' => ['The Sillbeam descriptor attribute cannot be instantiated or read.', 'A missing attribute or invalid constructor arguments prevent reliable descriptor metadata.', 'Check the reported attribute and its constructor arguments, types and explicit ID; use the original error to identify the invalid value.'],
            'discovery.descriptor_id' => ['Discovery cannot derive a valid ID from the descriptor class.', 'The default class-derived identifier is not a valid lowercase Sillbeam ID.', 'Set an explicit attribute id matching [a-z][a-z0-9-]*, or rename the descriptor so a valid ID can be derived.'],
            'discovery.multiple_descriptors' => ['A file contains multiple descriptors of the requested type.', 'Discovery cannot select one unambiguous descriptor for this file.', 'Keep one descriptor of the requested type per file and align each class with its PSR-4 filename.'],
            'discovery.multiple_attributes' => ['A class has multiple Sillbeam descriptor attributes.', 'The class cannot unambiguously represent a single Module or Project descriptor.', 'Keep exactly one intended Sillbeam descriptor attribute on the reported class.'],
            'source.parse' => ['A source file contains a PHP syntax error.', 'The parser cannot provide reliable declarations or references for this file.', 'Fix the syntax error at the reported file/line using the original parser message.'],
            'source.read' => ['A source file could not be read.', 'Unreadable source is not evidence of an empty or valid architecture.', 'Check the reported file exists and the analysis process has read permissions; restore the file or access.'],
            'source.duplicate_class' => ['A class is declared more than once in the module source index.', 'Case-insensitive class ownership is ambiguous; reference analysis cannot continue reliably.', 'Locate the competing declarations of the reported class and remove or rename the unintended declaration; update affected references.'],
            'report.execution' => ['Architecture report execution failed.', 'The execution failure does not establish architecture validity.', 'Inspect the original exception and correct the reported execution/configuration failure, then rerun the report.'],
            default => ['Unknown rule.', 'No rule-specific explanation is available; inspect the original message and context.', 'Inspect the original finding and available source evidence before choosing a correction.'],
        };
    }

    /** @param list<Resolution> $fallback
     * @return array{string, list<Resolution>}
     */
    private function undeclared(ArchitectureFinding $finding, string $impact, array $fallback): array
    {
        $source = $this->text($finding, 'module');
        $target = $this->text($finding, 'target');
        if (!$this->analysisComplete || !$this->context->complete || null === $source || null === $target || null === $this->context->relationship($source, $target)) {
            return [$impact.' Diagnostic evidence is incomplete; no dependency edit is established.', $fallback];
        }
        $assessment = $this->context->assessDependency($source, $target);
        if (false === $assessment->privacyAllowed) {
            return $this->privateAccess($finding, $this->files($finding));
        }
        if (null !== $assessment->returnPath) {
            $chain = [$source, ...$assessment->returnPath];
            $files = array_values(array_unique([...$this->files($finding), ...array_values($this->context->graph()->sourceDescriptors($assessment->returnPath))]));

            return ['Declaring this direct dependency would close a cycle: '.implode(' → ', $chain).'. Existing return path: '.implode(' → ', $assessment->returnPath).'.', [new Resolution('option', ['Choose the intended dependency direction; remove or redirect the reported code reference or extract a shared contract. Do not add the edge that closes this cycle. Review '.implode(', ', $files).' and rerun architecture analysis.'], $files)]];
        }
        $resolution = $assessment->resolution();
        if (null !== $resolution) {
            return [$impact.' The declared graph has no witnessed return path or private-boundary blocker under complete evidence; this remains a domain choice.', [$resolution]];
        }

        $reason = true === $assessment->alreadyDeclared
            ? 'The available descriptor already declares this dependency; rerun analysis before editing it.'
            : (\in_array('unresolved_dependencies', $assessment->blockers, true)
                ? 'Some declared targets are unresolved, so a return path cannot be ruled out.'
                : 'Activation or endpoint evidence does not establish a dependency edit; verify the target state first.');

        return [$impact.' '.$reason, $fallback];
    }

    /** @param list<string> $files
     * @return array{string, list<Resolution>}
     */
    private function privateAccess(ArchitectureFinding $finding, array $files): array
    {
        $target = $this->text($finding, \in_array($finding->rule, ['project.private_module', 'dependency.disabled'], true) ? 'module' : 'target');
        $owner = null === $target ? null : ($this->context->modules[$target]->projectClass ?? null);
        $impact = (null === $owner ? 'The target is private to its owning Project.' : $target.' is private to Project '.$owner.'.').' Declaring a dependency does not grant access across this boundary.';

        return [$impact, [new Resolution('option', ['Keep the implementation within its owning Project, or deliberately move reusable contracts into a shared module and update the reported reference/requirement. Rerun architecture analysis.'], $files)]];
    }

    /** @param list<string> $files
     * @return array{string, string, list<Resolution>}
     */
    private function inactive(ArchitectureFinding $finding, string $problem, array $files): array
    {
        $target = $this->text($finding, 'dependency.disabled' === $finding->rule ? 'module' : 'target');
        $state = null === $target || !$this->analysisComplete ? 'unknown' : $this->context->state($target);
        $definition = null === $target ? null : ($this->context->modules[$target] ?? null);
        $requestingProject = $this->text($finding, 'project');
        $source = $this->text($finding, 'module');
        $forbidden = 'dependency.disabled' === $finding->rule
            ? null !== $definition?->projectClass && $definition->projectClass !== $requestingProject
            : (null !== $source && null !== $target && false === $this->context->assessDependency($source, $target)->privacyAllowed);
        if ($this->analysisComplete && $this->context->complete && $forbidden && null !== $target) {
            [$impact, $options] = $this->privateAccess($finding, $files);
            $key = $this->context->moduleConfigurationKey($target);
            $address = null === $key ? null : 'sillbeam.modules["'.$key.'"].enabled';
            $impact .= ' The target is disabled; enabling it does not permit this Project access.'.(null === $address ? '' : ' Owner configuration: '.$address.'.');
            $resolutions = array_map(static fn (Resolution $option): Resolution => new Resolution($option->kind, $option->steps, $option->files, null === $address ? [] : [$address]), $options);

            return [$problem, $impact, $resolutions];
        }
        if ('disabled' === $state && null !== $target) {
            $key = $this->context->moduleConfigurationKey($target);
            $address = null === $key ? null : 'sillbeam.modules["'.$key.'"].enabled';
            $step = null === $address ? 'Verify the target module override and decide whether it should be enabled.' : 'If this module is required, set '.$address.' to true; otherwise remove the requirement/reference.';

            return [$problem, $target.' is disabled by its resolved module configuration. Selecting it in a Project or declaring a dependency does not enable it.', [new Resolution('option', [$step.' Rerun architecture analysis and review the resulting activation.'], $files, null === $address ? [] : [$address])]];
        }
        if ('unassigned' === $state && null !== $target) {
            $source = $this->text($finding, 'module');
            $projects = null === $source ? [] : array_keys($this->context->assessDependency($source, $target)->targetProjectStates);
            $projectFiles = [];
            foreach ($projects as $project) {
                $projectFiles[] = $this->context->projects[$project]->descriptorFile;
            }
            $step = [] === $projectFiles ? 'Choose which Project should activate '.$target.' and update that Project descriptor\'s modules list.' : 'If the feature belongs in the source Project graph, select '.$target.'::class in the SillbeamProject modules list of '.implode(' or ', $projectFiles).'.';

            return [$problem, $target.' is enabled but unassigned to the analyzed Project graphs; it is not disabled.', [new Resolution('option', [$step.' Otherwise remove or restructure the reference. Rerun architecture analysis and review activation.'], array_values(array_unique([...$files, ...$projectFiles])))]];
        }

        return [$problem, 'The finding reports inactive/disabled graph use, but the current activation state is unknown or unavailable. Do not assume the module is unassigned or change an enabled override without verification.', [new Resolution('technical', ['Verify the target identity, enabled configuration and Project assignment; rerun complete architecture analysis before changing activation.'], $files)]];
    }

    /** @param list<string> $files
     * @return array{string, string, list<Resolution>}
     */
    private function hosts(ArchitectureFinding $finding, string $problem, string $impact, string $step, array $files): array
    {
        $project = $this->text($finding, 'project');
        $id = null === $project ? null : $this->context->projectConfigurationId($project);
        $address = null === $id ? null : 'sillbeam.projects["'.$id.'"].hosts';
        $addresses = null === $address ? [] : [$address];
        $host = $this->text($finding, 'host');
        if (null !== $host) {
            $problem .= ' Reported host: "'.$host.'".';
        }
        if ('project.host_duplicate' === $finding->rule && null !== $host) {
            $owners = $this->context->projectsForHost($host);
            foreach ($owners as $owner) {
                $files[] = $owner->descriptorFile;
                $ownerId = $this->context->projectConfigurationId($owner->class);
                if (null !== $ownerId) {
                    $addresses[] = 'sillbeam.projects["'.$ownerId.'"].hosts';
                }
            }
            if ([] !== $owners) {
                $impact .= ' Configured Projects: '.implode(', ', array_column($owners, 'class')).'.';
            }
        }
        $addresses = array_values(array_unique($addresses));
        sort($addresses, \SORT_STRING);
        $files = array_values(array_unique($files));
        sort($files, \SORT_STRING);
        $step .= ([] === $addresses ? '' : ' Review '.implode(', ', array_unique($addresses)).'.').' Rerun architecture analysis.';

        return [$problem, $impact, [new Resolution('option', [$step], array_values(array_unique($files)), array_values(array_unique($addresses)))]];
    }

    /** @param list<string> $files
     * @return array{string, list<Resolution>}
     */
    private function duplicate(ArchitectureFinding $finding, string $problem, array $files): array
    {
        if ('module.duplicate_key' === $finding->rule) {
            $module = $this->context->modules[$this->text($finding, 'module') ?? ''] ?? null;
            if (null !== $module) {
                foreach ($this->context->modules as $other) {
                    if ($other->id === $module->id && $other->projectClass === $module->projectClass) {
                        $files[] = $other->descriptorFile;
                    }
                }
                $problem .= ' ID: '.$module->id.'; scope: '.($module->projectClass ?? 'shared').'.';
            }
            $step = 'Choose unique module IDs within this scope, edit the affected descriptor attributes and update their scoped configuration keys.';
        } else {
            $project = $this->context->projects[$this->text($finding, 'project') ?? ''] ?? null;
            if (null !== $project) {
                foreach ($this->context->projects as $other) {
                    if ($other->id === $project->id) {
                        $files[] = $other->descriptorFile;
                    }
                }
                $problem .= ' ID: '.$project->id.'.';
            }
            $step = 'Choose unique Project IDs, edit the affected descriptors and update Project configuration and private module keys.';
        }

        $files = array_values(array_unique($files));
        sort($files, \SORT_STRING);

        return [$problem, [new Resolution('option', [$step.' Review '.([] === $files ? 'the reported descriptors' : implode(', ', array_unique($files))).' and rerun architecture analysis.'], array_values(array_unique($files)))]];
    }

    private function text(ArchitectureFinding $finding, string $key): ?string
    {
        $value = $finding->context[$key] ?? null;

        return \is_string($value) && '' !== $value ? $value : null;
    }

    /** @return list<string> */
    private function files(ArchitectureFinding $finding): array
    {
        $files = [];
        $file = $this->text($finding, 'file');
        if (null !== $file) {
            $files[] = $file;
        }
        foreach (['module', 'target', 'project', 'class'] as $key) {
            $class = $this->text($finding, $key);
            if (null !== $class) {
                $descriptor = $this->context->modules[$class]->descriptorFile ?? $this->context->projects[$class]->descriptorFile ?? null;
                if (null !== $descriptor) {
                    $files[] = $descriptor;
                }
            }
        }

        return array_values(array_unique($files));
    }
}
