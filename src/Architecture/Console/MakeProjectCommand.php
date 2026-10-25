<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\DescriptorCreator;
use Sillbeam\Core\Console\Style\ConsoleStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sillbeam:make:project', description: 'Create a minimal Sillbeam project descriptor.')]
final class MakeProjectCommand extends Command
{
    public function __construct(private readonly DescriptorCreator $creator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('Name', InputArgument::REQUIRED, 'Project name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($output);
        try {
            $name = $input->getArgument('Name');
            $file = $this->creator->create($name, project: true);
            $io->success('Created '.$file);
            $io->note('If multiple projects exist, configure sillbeam.projects.<id>.hosts for every project before the next application boot. No configuration files were created.');

            return Command::SUCCESS;
        } catch (\InvalidArgumentException|\RuntimeException $error) {
            $io->error($error->getMessage());

            return Command::FAILURE;
        }
    }
}
