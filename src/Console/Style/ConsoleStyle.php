<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console\Style;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/** @internal Prototype 1.2 output primitives shared by Core commands. */
final readonly class ConsoleStyle
{
    public int $width;

    public function __construct(private OutputInterface $output, ?int $terminalWidth = null)
    {
        $width = $terminalWidth ?? (new Terminal())->getWidth();
        $this->width = max(52, min(90, $width > 0 ? $width : 80));
    }

    public function heading(string $section): void
    {
        $this->output->writeln('');
        $this->output->writeln('  <options=bold>SILLBEAM CORE</>  <fg=gray>/ '.OutputFormatter::escape(TerminalText::clean($section)).'</>');
        $this->rule();
    }

    public function rule(): void
    {
        $this->output->writeln('  <fg=gray>'.str_repeat('─', $this->width - 4).'</>');
    }

    public function text(string $text, string $indent = '  ', ?string $style = null): void
    {
        foreach (TerminalText::wrap($text, $this->width - Helper::width($indent) - 2) as $line) {
            $content = OutputFormatter::escape($line);
            $this->output->writeln($indent.(null === $style ? $content : '<'.$style.'>'.$content.'</>'));
        }
    }

    public function success(string $message): void
    {
        $this->text('✓  '.$message, style: 'fg=green;options=bold');
    }

    public function error(string $message): void
    {
        $this->text('✕  '.$message, style: 'fg=red;options=bold');
    }

    public function note(string $message): void
    {
        $this->text('!  '.$message, style: 'fg=yellow');
    }
}
