<?php

declare(strict_types=1);

namespace Nexum\Management\Console;

use Nexum\Management\Module\ModuleAction;
use Nexum\Management\Module\ModuleConfigurationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'nexum:module', description: 'Enable, disable or remove a Nexum module configuration.')]
final class ModuleCommand extends Command
{
    public function __construct(private readonly ModuleConfigurationManager $manager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'enable, disable or remove')
            ->addArgument('module', InputArgument::REQUIRED, 'Module identifier');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = ModuleAction::tryFrom((string) $input->getArgument('action'));
        if (null === $action) {
            $output->writeln('<error>Action must be enable, disable or remove.</error>');

            return self::INVALID;
        }
        try {
            $this->manager->apply((string) $input->getArgument('module'), $action);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return self::FAILURE;
        }
        $output->writeln(\sprintf('<info>Module configuration updated: %s.</info>', $action->value));

        return self::SUCCESS;
    }
}
