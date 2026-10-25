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
        $caution = 'fatal' === $finding->severity || \in_array($finding->rule, ['dependency.cycle', 'dependency.private', 'code.private', 'project.private_module'], true);
        $resolutions = [new Resolution('technical', [$step.' Rerun architecture analysis.'], $files)];
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
                [$impact, $resolutions, $caution] = $this->undeclared($finding, $impact, $resolutions);
                break;
            case 'dependency.cycle':
                if ([] !== $cycle) {
                    $problem = 'Declared dependencies form a cycle: '.implode(' → ', $cycle).'.';
                    $files = array_values($this->context->graph()->sourceDescriptors($cycle));
                }
                $impact = 'Modules cannot depend on each other in a loop. Sillbeam cannot decide which dependency your application should remove.';
                $resolutions = [new Resolution('option', ['Review the involved descriptors'.([] === $files ? '' : ': '.implode(', ', $files)).'. Decide which module should depend on the other. Remove or redirect a dependency, or move shared contracts into a separate module. Rerun architecture analysis.'], $files)];
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
                $resolutions = [new Resolution('technical', ['Check '.($target ?? 'the reported target class').' in '.($descriptor ?? 'the reported descriptor').'. Make sure the descriptor is in a configured discovery directory and is not excluded. Correct the declaration or restore the descriptor, then rerun architecture analysis.'], $files)];
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
            $impact .= ' Analysis incomplete: fix the blocking error and rerun before relying on dependencies or activation.';
            if ('fatal' !== $finding->severity) {
                $resolutions = [new Resolution('technical', ['Fix the errors that stopped the analysis first. Then rerun to check this finding again.'], $files)];
            }
        }

        return new FindingExplanation($finding, $problem, $impact, $resolutions, $caution);
    }

    /** @return array{string, string, string} */
    private function wording(string $rule): array
    {
        return match ($rule) {
            'dependency.unknown' => ['A declared module dependency was not discovered.', 'Sillbeam needs the module descriptor to check this dependency and activate the module.', 'Check the target class and its descriptor. Make sure its directory is included by discovery and is not excluded.'],
            'project.unknown_module' => ['A module required by a Project was not discovered.', 'Sillbeam cannot check or activate the module without its descriptor.', 'Check the module class in the Project descriptor and the configured discovery directories.'],
            'code.undeclared_dependency' => ['Code uses another module without declaring it as a direct dependency.', 'Each module must declare the modules its code uses directly.', 'Check the reported reference and both module descriptors before changing dependencies.'],
            'dependency.private', 'code.private', 'project.private_module' => ['Access crosses a private Project boundary.', 'Declaring a dependency does not grant access to another Project\'s private module.', 'Keep private implementation within its owning Project or deliberately move reusable contracts into a shared module.'],
            'dependency.cycle' => ['Declared module dependencies contain a cycle.', 'Modules cannot depend on each other in a loop. Decide which module should depend on the other.', 'Review the involved descriptor declarations and choose the intended dependency direction.'],
            'dependency.disabled' => ['A required module is disabled.', 'Sillbeam cannot activate a disabled module. Its configuration key is unknown from the available information.', 'Check which module is required and whether its configuration disables it. Decide whether to enable it or remove the requirement.'],
            'code.inactive' => ['Code references an inactive module.', 'The target module is not active. The available information does not show whether it is disabled or unassigned.', 'Check whether the target is enabled and selected by a Project before changing the reference.'],
            'code.unknown_class' => ['A referenced application class could not be found.', 'The class was not found in the application source or loaded/mapped classes available to this check. This does not validate all PHP autoloading, external Composer types or Symfony autowiring.', 'Check the class name/import and its source file. If its Composer PSR-4 mapping changed, update it and regenerate the autoloader.'],
            'module.unknown_override' => ['A module configuration override matches no discovered module.', 'The key does not identify a module in this shared or private Project scope.', 'Compare the override key with discovered shared:<id> and project:<project-id>:<module-id> keys; correct it or remove the stale override.'],
            'project.unknown_configuration' => ['Project configuration matches no discovered Project.', 'The configured Project ID cannot select a discovered Project.', 'Compare the configured ID with discovered Project IDs; correct it or remove stale configuration.'],
            'project.host_invalid' => ['A configured Project host is not an exact hostname.', 'Project host routing requires an exact hostname without a scheme, port, path or wildcard.', 'Replace the invalid value with the intended exact hostname, without a scheme, port, path or wildcard.'],
            'project.host_required' => ['A Project has no configured host in a multi-Project application.', 'Every Project needs at least one exact host so request selection is defined.', 'Choose an exact host for this Project and configure its hosts list.'],
            'project.host_duplicate' => ['An exact host is assigned to more than one Project.', 'Host-based Project selection is ambiguous.', 'Decide which Project should own the host and remove that host from the other Project configurations.'],
            'module.duplicate_key' => ['Multiple modules have the same logical key.', 'Module IDs must be unique in the shared scope or within their owning Project. Otherwise configuration and activation cannot select the right module.', 'Choose unique IDs within the affected scope and update the affected configuration keys.'],
            'project.duplicate_id' => ['Multiple Projects have the same ID.', 'The same ID cannot select two different Projects.', 'Choose unique Project IDs and update affected Project configuration and private module keys.'],
            'module.owner_missing' => ['A private module has no discovered owning Project.', 'Sillbeam cannot find the owning Project, so the module scope and activation are unknown.', 'Correct the module ownership/layout so its owning Project is discoverable, or deliberately place it in a configured shared module root.'],
            'discovery.owner_missing' => ['A module in a Project discovery root has no owning Project.', 'Its directory is not inside a discovered Project. Sillbeam cannot identify its owning Project.', 'Restore a discoverable owning Project or deliberately move the module into a configured shared root; verify ownership and discovery boundaries.'],
            'discovery.module_boundary' => ['A descriptor lies outside allowed module discovery boundaries.', 'Sillbeam cannot assign this descriptor to a configured shared module directory or an owning Project.', 'Move the descriptor into the intended configured module root or owning Project area, or correct the discovery root configuration.'],
            'discovery.project_boundary' => ['A descriptor lies outside allowed Project discovery boundaries.', 'Sillbeam cannot assign this descriptor to a configured Project directory.', 'Move the descriptor into the intended configured Project root or correct that discovery root configuration.'],
            'discovery.module_overlap' => ['Module areas overlap.', 'A source file must belong to one module. Overlapping module directories make that assignment unclear.', 'Separate the reported module areas so no module contains another module descriptor.'],
            'discovery.project_overlap' => ['Project areas overlap.', 'Overlapping Project directories make it unclear which Project owns their code.', 'Separate the reported Project areas so no Project contains another Project descriptor.'],
            'discovery.project_inside_module' => ['A Project area is inside a module area.', 'A module cannot contain a Project; these ownership areas conflict.', 'Separate the reported Project and module areas and verify their configured roots.'],
            'discovery.descriptor_class' => ['A descriptor class does not match its expected PSR-4 location.', 'The namespace and class name must match the filename and configured namespace for that directory.', 'Align the descriptor namespace, short class name, filename and configured Composer/discovery mapping with the expected class in the original finding.'],
            'discovery.descriptor_file' => ['A descriptor class resolves to a different file.', 'The loaded class comes from another file. Its metadata cannot be used for this descriptor.', 'Inspect the class declaration and Composer mappings, remove unintended duplicate mappings/declarations and ensure the reported class belongs to the intended descriptor file.'],
            'discovery.descriptor_metadata' => ['The Sillbeam descriptor attribute cannot be instantiated or read.', 'A missing attribute or invalid arguments prevent Sillbeam from reading the descriptor settings.', 'Check the reported attribute and its constructor arguments, types and explicit ID; use the original error to identify the invalid value.'],
            'discovery.descriptor_id' => ['Discovery cannot derive a valid ID from the descriptor class.', 'The default class-derived identifier is not a valid lowercase Sillbeam ID.', 'Set an explicit attribute id matching [a-z][a-z0-9-]*, or rename the descriptor so a valid ID can be derived.'],
            'discovery.multiple_descriptors' => ['A file contains multiple descriptors of the requested type.', 'Sillbeam needs one descriptor of this type per file.', 'Keep one descriptor of the requested type per file and align each class with its PSR-4 filename.'],
            'discovery.multiple_attributes' => ['A class has multiple Sillbeam descriptor attributes.', 'Sillbeam needs one Module or Project descriptor attribute on this class.', 'Keep exactly one intended Sillbeam descriptor attribute on the reported class.'],
            'source.parse' => ['A source file contains a PHP syntax error.', 'Sillbeam cannot reliably read the classes and references in this file.', 'Fix the syntax error at the reported file/line using the original parser message.'],
            'source.read' => ['A source file could not be read.', 'An unreadable file cannot be treated as empty or valid.', 'Check the reported file exists and the analysis process has read permissions; restore the file or access.'],
            'source.duplicate_class' => ['A class is declared more than once in the checked source.', 'Class names are case-insensitive. Sillbeam cannot reliably assign references to one declaration.', 'Locate the competing declarations of the reported class and remove or rename the unintended declaration; update affected references.'],
            'report.execution' => ['Architecture report execution failed.', 'The check itself failed; this does not establish an architecture violation or a valid result.', 'Read the original exception and fix the reported execution or configuration error.'],
            default => ['Unknown rule.', 'No rule-specific explanation is available. Check the original message and context.', 'Inspect the original finding and available source evidence before choosing a correction.'],
        };
    }

    /** @param list<Resolution> $fallback
     * @return array{string, list<Resolution>, bool}
     */
    private function undeclared(ArchitectureFinding $finding, string $impact, array $fallback): array
    {
        $source = $this->text($finding, 'module');
        $target = $this->text($finding, 'target');
        if (!$this->analysisComplete || !$this->context->complete || null === $source || null === $target || null === $this->context->relationship($source, $target)) {
            return [$impact.' There is not enough information to recommend adding this dependency. Complete the analysis first.', $fallback, true];
        }
        $assessment = $this->context->assessDependency($source, $target);
        $activation = $this->activationNotice($assessment);
        if (false === $assessment->privacyAllowed) {
            [$impact, $resolutions] = $this->privateAccess($finding, $this->files($finding));

            return [$impact.('' === $activation ? '' : ' '.$activation), $resolutions, true];
        }
        if (null !== $assessment->returnPath) {
            $chain = [$source, ...$assessment->returnPath];
            $files = array_values(array_unique([...$this->files($finding), ...array_values($this->context->graph()->sourceDescriptors($assessment->returnPath))]));

            return ['Adding this dependency would create a cycle: '.implode(' → ', $chain).'. Existing dependency chain: '.implode(' → ', $assessment->returnPath).'.'.('' === $activation ? '' : ' '.$activation), [new Resolution('option', ['Decide which module should depend on the other. Remove or redirect the code reference, or move shared contracts into a separate module. Do not add a dependency that creates this cycle. Review '.implode(', ', $files).' and rerun architecture analysis.'], $files)], true];
        }
        $resolution = $assessment->resolution();
        if (null !== $resolution) {
            return [$impact.('' === $activation ? ' No conflicting dependency was detected for this change.' : ' '.$activation), [$resolution], '' !== $activation];
        }

        $reason = true === $assessment->alreadyDeclared
            ? 'The descriptor already declares this dependency. Rerun the check before editing it.'
            : (\in_array('unresolved_dependencies', $assessment->blockers, true)
                ? 'Some declared modules could not be found, so Sillbeam cannot rule out a cycle.'
                : 'The available module or activation information is not enough to recommend this change. Check the target state first.');

        return [$impact.' '.$reason.('' === $activation ? '' : ' '.$activation), $fallback, true];
    }

    private function activationNotice(DependencyAssessment $assessment): string
    {
        $notes = [];
        if ('active' !== $assessment->sourceState) {
            $notes[] = match ($assessment->sourceState) {
                'disabled' => 'The source module is disabled.',
                'unassigned' => 'The source module is enabled but unassigned to a Project.',
                default => 'The source module activation state is unknown.',
            };
        }
        if ('active' !== $assessment->targetState) {
            $notes[] = match ($assessment->targetState) {
                'disabled' => 'The target module is disabled. Check its enabled setting before changing dependencies.',
                'unassigned' => 'The target module is enabled but unassigned to a Project. Adding a dependency may activate it; check which Projects should use it.',
                default => 'The target module activation state is unknown. Check its configuration and Project assignment first.',
            };
        }
        $unassigned = array_keys(array_filter($assessment->targetProjectStates, static fn (string $state): bool => 'unassigned' === $state));
        if ('active' === $assessment->targetState && [] !== $unassigned) {
            $notes[] = 'The target is not active in these Projects that use the source: '.implode(', ', $unassigned).'. Adding this dependency may activate it there.';
        }
        if (\in_array('unknown', $assessment->targetProjectStates, true)) {
            $notes[] = 'Target activation is unknown in some Projects that use the source. Check their module selection first.';
        }

        return implode(' ', $notes);
    }

    /** @param list<string> $files
     * @return array{string, list<Resolution>}
     */
    private function privateAccess(ArchitectureFinding $finding, array $files): array
    {
        $target = $this->text($finding, \in_array($finding->rule, ['project.private_module', 'dependency.disabled'], true) ? 'module' : 'target');
        $owner = null === $target ? null : ($this->context->modules[$target]->projectClass ?? null);
        $impact = (null === $owner ? 'The target is private to its owning Project.' : $target.' is private to Project '.$owner.'.').' Declaring a dependency does not grant access across this boundary.';

        return [$impact, [new Resolution('option', ['Keep the implementation in its owning Project, or move reusable contracts into a shared module and update the reference or dependency. Rerun architecture analysis.'], $files)]];
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

            return [$problem, $target.' is disabled in the module configuration. Selecting it in a Project or declaring a dependency does not enable it.', [new Resolution('option', [$step.' Rerun architecture analysis and check which modules are active.'], $files, null === $address ? [] : [$address])]];
        }
        if ('unassigned' === $state && null !== $target) {
            $source = $this->text($finding, 'module');
            $projects = null === $source ? [] : array_keys($this->context->assessDependency($source, $target)->targetProjectStates);
            $projectFiles = [];
            foreach ($projects as $project) {
                $projectFiles[] = $this->context->projects[$project]->descriptorFile;
            }
            $step = [] === $projectFiles ? 'Choose which Project should use '.$target.' and update that Project descriptor\'s modules list.' : 'If this Project should use the module, select \\'.ltrim($target, '\\').'::class in the SillbeamProject modules list of '.implode(' or ', $projectFiles).'.';

            $impact = $target.' is enabled but unassigned to the checked Projects; it is not disabled.';
            if (null !== $source && false === $this->context->assessDependency($source, $target)->alreadyDeclared) {
                $impact .= ' The source also has no direct dependency declaration for this module. After selecting it, rerun the check before adding that declaration.';
            }

            return [$problem, $impact, [new Resolution('option', [$step.' Otherwise remove or restructure the reference. Rerun architecture analysis and check which modules are active.'], array_values(array_unique([...$files, ...$projectFiles])))]];
        }

        return [$problem, 'The module is reported as inactive, but its activation state is unknown. Do not assume it is disabled or unassigned. Check its configuration before changing it.', [new Resolution('technical', ['Check the target module, its enabled setting and Project assignment. Rerun a complete analysis before changing activation.'], $files)]];
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
