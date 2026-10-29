<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console\Doctor;

use Sillbeam\Core\Console\Style\ConsoleStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Runs installation diagnostics and writes a console report without changing application configuration.
 *
 * @internal
 */
#[AsCommand(name: 'sillbeam:doctor', description: 'Check static Symfony installation evidence without changing files.')]
final class DoctorCommand extends Command
{
    public function __construct(private readonly string $projectDir, private readonly string $environment, private readonly ?KernelInterface $kernel = null)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Reads installation files only; never boots the Kernel or executes application configuration. Checks bundle declarations, route imports, Console adapter, service import overlap and discovery consistency. Unknown PHP/XML, imports, custom Kernel hooks and dynamic settings require manual verification. Verified means static file evidence, not effective runtime configuration. Does not replace sillbeam:architecture, PHPStan or Symfony configuration checks. Exit 0: all static checks verified; 1: action required; 2: incomplete/unexecutable (takes precedence). No files are written.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new ConsoleStyle($output);
        $style->heading('Installation');
        $checks = (new InstallationInspector())->inspect($this->projectDir, $this->environment, null === $this->kernel ? null : ((new \ReflectionClass($this->kernel))->getFileName() ?: ''));
        $counts = array_count_values(array_column($checks, 'status'));
        $style->text(\sprintf('%d verified · %d action required · %d not verified', $counts['verified'] ?? 0, $counts['action required'] ?? 0, $counts['not verified'] ?? 0));
        $style->text('Static file evidence only; effective runtime configuration is not certified.');
        foreach ($checks as $check) {
            $message = $check['label'].': '.$check['status'];
            match ($check['status']) {
                'verified' => $style->success($message),
                'action required' => $style->error($message),
                default => $style->note($message),
            };
            if ('' !== $check['detail']) {
                $style->text($check['detail'], '     ');
            }
        }
        $style->text(isset($counts['not verified']) || isset($counts['action required'])
            ? 'Next: make the reviewed changes yourself, resolve unverified checks manually, then rerun php bin/console sillbeam:doctor.'
            : 'Next: run php bin/console sillbeam:architecture to validate descriptors and dependencies.');

        return isset($counts['not verified']) ? 2 : (isset($counts['action required']) ? 1 : 0);
    }
}
