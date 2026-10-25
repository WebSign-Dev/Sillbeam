<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ArchitectureCommand;
use Sillbeam\Core\Architecture\Console\ReportCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Use in the existing bin/console bootstrap to make reporting independent of boot. */
final class Application extends SymfonyApplication
{
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $command = $this->architectureCommand();
        $definition = clone $this->getDefinition();
        foreach ($command->getDefinition()->getOptions() as $option) {
            $definition->addOption($option);
        }
        $definition->addOption($this->reportCommand()->getDefinition()->getOption('output'));
        try {
            $input->bind($definition);
        } catch (\Exception) {
            // The actual command validates usage and returns a structured error below.
        }
        $name = $input->getFirstArgument();
        if (!\in_array($name, ['sillbeam:architecture', 'sillbeam:report'], true)) {
            return parent::doRun($input, $output);
        }
        $command = 'sillbeam:report' === $name ? $this->reportCommand() : $command;
        if ($input->hasParameterOption(['--help', '-h'], true)) {
            // HelpCommand has its own --format option: avoid merging report options into it.
            (new DescriptorHelper())->describe($output, $command, ['format' => 'txt']);

            return 0;
        }
        $definition = clone $this->getDefinition();
        foreach ($command->getDefinition()->getOptions() as $option) {
            $definition->addOption($option);
        }
        $application = new ConsoleApplication('Sillbeam');
        $application->setDefinition($definition);
        $application->addCommand($command);
        try {
            return $application->doRun($input, $output);
        } catch (\Throwable $error) {
            return ArchitectureCommand::usageError($error, 'sillbeam:architecture' === $name && 'json' === $input->getParameterOption('--format'), $input, $output);
        }
    }

    public function find(string $name): Command
    {
        return match ($name) {
            'sillbeam:architecture' => $this->architectureCommand(),
            'sillbeam:report' => $this->reportCommand(),
            default => parent::find($name),
        };
    }

    public function get(string $name): Command
    {
        return match ($name) {
            'sillbeam:architecture' => $this->architectureCommand(),
            'sillbeam:report' => $this->reportCommand(),
            default => parent::get($name),
        };
    }

    private function reportCommand(): ReportCommand
    {
        $command = new ReportCommand(fn (): ArchitectureReport => (new KernelArchitectureReporter())->report($this->getKernel()), $this->getKernel()->getProjectDir());
        $command->setApplication($this);

        return $command;
    }

    private function architectureCommand(): ArchitectureCommand
    {
        $command = new ArchitectureCommand(fn (): ArchitectureReport => (new KernelArchitectureReporter())->report($this->getKernel()));
        $command->setApplication($this);

        return $command;
    }
}
