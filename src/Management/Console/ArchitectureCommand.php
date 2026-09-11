<?php

declare(strict_types=1);

namespace Nexum\Management\Console;

use Nexum\Module\ModuleRegistryInterface;
use Nexum\Project\ProjectRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'nexum:architecture', description: 'Show the compiled Nexum projects and modules.')]
final class ArchitectureCommand extends Command
{
    public function __construct(
        private readonly ModuleRegistryInterface $modules,
        private readonly ProjectRegistryInterface $projects,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Nexum architecture');
        $projects = $this->projects->all();
        if ([] === $projects) {
            $io->section('Application modules');
            $this->renderModules($io, $this->modules->forApplication());
        } else {
            foreach ($projects as $project) {
                $io->section(\sprintf('%s (%s)', $project->id, implode(', ', $project->hosts) ?: 'all hosts'));
                $this->renderModules($io, $this->modules->forProject($project->class));
            }
        }

        return self::SUCCESS;
    }

    /** @param array<class-string, \Nexum\Module\ModuleDefinition> $modules */
    private function renderModules(SymfonyStyle $io, array $modules): void
    {
        $io->table(['ID', 'Class', 'Scope', 'Dependencies'], array_map(static fn ($module): array => [
            $module->id,
            $module->class,
            $module->isPrivate() ? 'private' : 'shared',
            implode(', ', $module->dependencies),
        ], array_values($modules)));
    }
}
