<?php

declare(strict_types=1);

namespace Sillbeam\Core\Kernel\Command;

use Sillbeam\Core\Console\Style\ConsoleStyle;
use Sillbeam\Core\Kernel\Runtime\KernelInformationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sillbeam:about', description: 'Display the important Sillbeam runtime and module information.')]
final class AboutCommand extends Command
{
    public function __construct(private readonly KernelInformationInterface $information)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleStyle($output);
        $io->heading('About');
        foreach ($this->information->all() as $key => $value) {
            $io->text(ucwords(str_replace('_', ' ', $key)).': '.$value);
        }

        return self::SUCCESS;
    }
}
