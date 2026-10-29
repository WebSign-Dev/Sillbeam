<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console;

use Sillbeam\Core\Architecture\ArchitectureReport;
use Sillbeam\Core\Architecture\Console\ArchitectureCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Fresh checks before standard build commands can claim success with a warm cache.
 *
 * @internal
 */
final readonly class ArchitectureBuildSubscriber implements EventSubscriberInterface
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => ['validateBuild', 1000]];
    }

    /**
     * Runs fresh architecture validation before cache:clear and cache:warmup, including warm caches.
     *
     * @throws \RuntimeException if the report is invalid or unavailable; includes buffered diagnostics
     */
    public function validateBuild(ConsoleCommandEvent $event): void
    {
        if (!\in_array($event->getCommand()?->getName(), ['cache:clear', 'cache:warmup'], true)) {
            return;
        }
        $command = new ArchitectureCommand(fn (): ArchitectureReport => (new KernelArchitectureReporter())->report($this->kernel));
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $code = $command->run(new ArrayInput(['--all' => true]), $output);
        if (0 !== $code) {
            throw new \RuntimeException($output->fetch(), $code);
        }
    }
}
