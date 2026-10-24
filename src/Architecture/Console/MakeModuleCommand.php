<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Architecture\DescriptorCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sillbeam:make:module', description: 'Create a minimal Sillbeam module descriptor.')]
final class MakeModuleCommand extends Command
{
    public function __construct(private readonly DescriptorCreator $creator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('Name', InputArgument::REQUIRED, 'Module name.');
        $this->addOption('project', null, InputOption::VALUE_REQUIRED, 'Existing project name, id or descriptor class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $name = $input->getArgument('Name');
            $file = $this->creator->create($name, owner: $input->getOption('project'));
            $io->success('Created '.$file);

            return Command::SUCCESS;
        } catch (\InvalidArgumentException|\RuntimeException $error) {
            $io->error($error->getMessage());

            return Command::FAILURE;
        }
    }
}
