<?php

declare(strict_types=1);

namespace Nexum\Kernel\Command;

use Nexum\Kernel\Runtime\KernelInformationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'nexum:about', description: 'Display the important Nexum runtime and module information.')]
final class AboutCommand extends Command
{
    public function __construct(private readonly KernelInformationInterface $information)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Nexum');
        $io->definitionList(...array_map(
            static fn (string|int $value, string $key): array => [ucwords(str_replace('_', ' ', $key)) => (string) $value],
            $this->information->all(),
            array_keys($this->information->all()),
        ));

        return self::SUCCESS;
    }
}
