<?php

declare(strict_types=1);

namespace Sillbeam\Architecture\Console;

use Sillbeam\Module\ModuleRegistryInterface;
use Sillbeam\Project\ProjectRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sillbeam:architecture', description: 'Inspect the discovered module catalog and compiled architecture.')]
final class ArchitectureCommand extends Command
{
    public function __construct(private readonly ModuleRegistryInterface $modules, private readonly ProjectRegistryInterface $projects)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json.', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        if (!\in_array($format, ['text', 'json'], true)) {
            (new SymfonyStyle($input, $output))->error('Supported formats: text, json.');

            return self::INVALID;
        }
        $active = $this->modules->active();
        $modules = [];
        foreach ($this->modules->all() as $module) {
            $used = isset($active[$module->class]);
            $modules[] = [
                'id' => $module->id, 'class' => $module->class, 'scope' => $module->isPrivate() ? 'private' : 'shared',
                'project' => $module->projectClass, 'enabled' => $module->enabled, 'active' => $used,
                'inactive_reason' => $used ? null : ($module->enabled ? 'unassigned' : 'disabled'),
                'directory' => $module->directory, 'descriptor' => $module->descriptorFile,
                'declared_dependencies' => $module->dependencies, 'observed_dependencies' => $module->observedDependencies,
                'references' => $module->dependencyReferences, 'activation_reasons' => $module->activationReasons,
                'controllers' => $module->controllerDirectories, 'entities' => $module->entityDirectories,
            ];
        }
        $projects = array_values(array_map(static fn ($project): array => get_object_vars($project), $this->projects->all()));
        if ('json' === $format) {
            $output->writeln(json_encode(['modules' => $modules, 'projects' => $projects], \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        } else {
            $io = new SymfonyStyle($input, $output);
            $io->table(['Module', 'Scope', 'State', 'Declared / observed', 'Directory'], array_map(static fn (array $module): array => [
                $module['id'], $module['scope'], $module['active'] ? 'active' : $module['inactive_reason'],
                \count($module['declared_dependencies']).' / '.\count($module['observed_dependencies']), $module['directory'],
            ], $modules));
            foreach ($this->projects->all() as $project) {
                $io->writeln($project->id.': '.implode(', ', $project->hosts).' ('.\count($this->modules->forProject($project->class)).' modules)');
            }
        }

        return self::SUCCESS;
    }
}
