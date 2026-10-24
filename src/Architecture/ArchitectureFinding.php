<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture;

final readonly class ArchitectureFinding
{
    /** @param array<string, string|int|null> $context
     * @param 'error'|'fatal' $severity
     */
    public function __construct(
        public string $rule,
        public string $message,
        public array $context = [],
        public string $severity = 'error',
    ) {
    }

    public function suggestion(): ?string
    {
        return match ($this->rule) {
            'dependency.unknown', 'project.unknown_module' => 'Correct the referenced module class or add its descriptor inside the configured discovery roots.',
            'module.unknown_override', 'project.unknown_configuration' => 'Use the ID of a discovered module or project, or remove the stale configuration key.',
            'dependency.private', 'code.private', 'project.private_module' => 'Keep private modules within their owning Project; move reusable contracts or code into a shared module.',
            'dependency.cycle' => 'Remove a dependency in the reported cycle or extract shared contracts to break it.',
            'dependency.disabled', 'code.inactive' => 'Enable and activate the required module in this graph, or remove the dependency/reference.',
            'code.unknown_class' => 'Correct the class name or import, restore the class, or configure its Composer autoload mapping and regenerate the autoloader.',
            'code.undeclared_dependency' => 'Declare the target module as a direct dependency in the source module descriptor.',
            'module.duplicate_key', 'project.duplicate_id' => 'Give the descriptors unique IDs within their scope.',
            'module.owner_missing', 'discovery.owner_missing' => 'Place the module inside its owning Project or a configured shared-module root.',
            'project.host_invalid' => 'Use an exact hostname without scheme, port, path or wildcard.',
            'project.host_required' => 'Configure at least one exact host for each Project in a multi-Project application.',
            'project.host_duplicate' => 'Assign each exact host to only one Project.',
            'source.parse' => 'Fix the reported PHP syntax error before rebuilding.',
            'source.read' => 'Restore the source file and ensure the build process can read it.',
            'source.duplicate_class' => 'Remove or rename the duplicate PHP class declaration.',
            'discovery.descriptor_metadata', 'discovery.descriptor_id' => 'Correct the descriptor attribute arguments and use a valid lowercase ID, or a class name from which one can be derived.',
            'discovery.descriptor_class', 'discovery.descriptor_file' => 'Align the descriptor class, filename and Composer PSR-4 namespace.',
            'discovery.module_overlap', 'discovery.project_overlap', 'discovery.project_inside_module' => 'Separate the reported architecture areas so ownership is unambiguous.',
            'discovery.module_boundary', 'discovery.project_boundary' => 'Move the descriptor into the matching configured discovery root.',
            'discovery.multiple_descriptors', 'discovery.multiple_attributes' => 'Declare one unambiguous Module or Project descriptor per descriptor file/class.',
            'report.execution' => 'Correct the reported configuration or execution failure and rerun the architecture report.',
            default => null,
        };
    }

    public function describe(): string
    {
        $context = [];
        foreach (['module', 'project', 'file', 'line'] as $key) {
            if (isset($this->context[$key])) {
                $context[] = $key.'='.$this->context[$key];
            }
        }
        $description = '['.$this->rule.'] '.$this->message;
        if ([] !== $context) {
            $description .= ' ('.implode(', ', $context).')';
        }
        if (null !== ($suggestion = $this->suggestion())) {
            $description .= "\nHint: ".$suggestion;
        }

        return $description;
    }
}
