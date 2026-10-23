<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ArchitectureCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Use in the existing bin/console bootstrap to make reporting independent of boot. */
final class Application extends SymfonyApplication
{
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $command = $this->architectureCommand();
        $definition = clone $this->getDefinition();
        $definition->addOption($command->getDefinition()->getOption('format'));
        try {
            $input->bind($definition);
        } catch (\Exception) {
            // The actual command validates usage and returns a structured error below.
        }
        if ('sillbeam:architecture' !== $input->getFirstArgument()) {
            return parent::doRun($input, $output);
        }
        $application = new ConsoleApplication('Sillbeam');
        $application->setDefinition($definition);
        $application->addCommand($command);
        try {
            return $application->doRun($input, $output);
        } catch (\Throwable $error) {
            return ArchitectureCommand::render(ArchitectureReport::unavailable($error), 'json' === $input->getParameterOption('--format') ? 'json' : 'text', $input, $output);
        }
    }

    public function find(string $name): Command
    {
        return 'sillbeam:architecture' === $name ? $this->architectureCommand() : parent::find($name);
    }

    public function get(string $name): Command
    {
        return 'sillbeam:architecture' === $name ? $this->architectureCommand() : parent::get($name);
    }

    private function architectureCommand(): ArchitectureCommand
    {
        $command = new ArchitectureCommand(fn (): ArchitectureReport => (new KernelArchitectureReporter())->report($this->getKernel()));
        $command->setApplication($this);

        return $command;
    }
}
