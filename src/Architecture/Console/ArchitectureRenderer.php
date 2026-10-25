<?php

declare(strict_types=1);

namespace Sillbeam\Core\Architecture\Console;

use Sillbeam\Core\Console\Style\ConsoleStyle;
use Sillbeam\Core\Console\Style\TerminalText;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal Faithful prototype 1.2 layout; all meanings come from supplied Core data. */
final class ArchitectureRenderer
{
    private const MARGIN = '  ';
    private const BODY = '    ';
    private const NESTED = '      ';
    private int $width;
    private ConsoleStyle $style;
    /** @var list<array<string, mixed>> */
    private array $couplings = [];

    public function __construct(
        private readonly OutputInterface $output,
        ?int $terminalWidth = null,
        private readonly bool $verbose = false,
        private readonly bool $all = false,
        private readonly ?int $issueNumber = null,
        private readonly bool $showCouplings = false,
    ) {
        $this->style = new ConsoleStyle($output, $terminalWidth);
        $this->width = $this->style->width;
    }

    /** @param array<string, mixed> $scenario */
    public function render(array $scenario): void
    {
        $this->couplings = $scenario['couplings'] ?? [];
        $issues = $scenario['issues'];
        $count = \count($issues);
        $findings = (int) $scenario['findings'];
        if (null !== $this->issueNumber && ($this->issueNumber < 1 || $this->issueNumber > $count)) {
            throw new \InvalidArgumentException('No diagnosis '.$this->issueNumber.'. Choose a number from 1 to '.$count.'.');
        }
        if (null !== $this->issueNumber && $this->all) {
            throw new \InvalidArgumentException('Choose either --issue=N or --all.');
        }

        $this->style->heading('Architecture');
        $unavailable = 'unavailable' === ($scenario['status'] ?? null);
        if (0 === $findings && !$unavailable) {
            $this->text('✓  Architecture valid', self::MARGIN, 'fg=green;options=bold');
            $this->text($scenario['modules'].' modules  ·  '.$scenario['projects'].' projects  ·  0 violations', self::MARGIN, 'fg=gray');
            if ($this->showCouplings) {
                $this->applicationCouplings();
            }
            $this->output->writeln('');

            return;
        }

        $this->text($unavailable ? '✕  Architecture analysis unavailable' : '✕  Architecture violations found', self::MARGIN, 'fg=red;options=bold');
        $this->text($findings.' '.(1 === $findings ? 'finding' : 'findings').'  ·  '.$count.' '.(1 === $count ? 'diagnosis' : 'diagnoses').'  ·  '.$scenario['modules'].' modules  ·  '.$scenario['projects'].' projects', self::MARGIN, 'fg=gray');
        if ($unavailable) {
            $this->text('Analysis incomplete. Counts reflect available evidence only.', self::MARGIN, 'fg=yellow');
        }
        $this->output->writeln('');

        if ($count > 1) {
            $this->index($issues);
            $this->output->writeln('');
        }

        if ($this->all) {
            foreach ($issues as $i => $issue) {
                $this->diagnosis($i + 1, $issue);
            }
        } else {
            $current = $this->issueNumber ?? 1;
            $this->diagnosis($current, $issues[$current - 1]);
        }

        if ($this->showCouplings) {
            $this->applicationCouplings();
        }

        $this->output->writeln('');
        $this->rule();
        if ($count > 1 && !$this->all) {
            $this->text('View another: --issue=N   ·   Expand all: --all', self::MARGIN, 'fg=gray');
        }
        $this->text('Technical context: -v   ·   No source files were changed.', self::MARGIN, 'fg=gray');
        $this->output->writeln('');
    }

    /** @param list<array<string,mixed>> $issues */
    private function index(array $issues): void
    {
        $this->text('DIAGNOSIS OVERVIEW', self::MARGIN, 'options=bold');
        $this->text('In recommended review order. Select a number for its full explanation.', self::MARGIN, 'fg=gray');
        $this->output->writeln('');
        foreach ($issues as $i => $issue) {
            $number = \sprintf('%02d', $i + 1);
            $current = ($i + 1) === ($this->issueNumber ?? 1) && !$this->all;
            $prefix = $current ? '<fg=red;options=bold>'.$number.'</>' : '<fg=gray>'.$number.'</>';
            $plain = $number.'  '.$issue['title'].' ('.$issue['findings'].')';
            if (Helper::width(TerminalText::clean($plain)) + 4 <= $this->width) {
                $this->output->writeln(self::BODY.$prefix.'  <options=bold>'.self::escape($issue['title']).'</> <fg=gray>('.$issue['findings'].')</>');
            } else {
                $this->text($plain, self::BODY, $current ? 'fg=red;options=bold' : 'options=bold');
            }
            $this->text($issue['relation'], self::NESTED, 'fg=cyan');
        }
    }

    /** @param array<string,mixed> $issue */
    private function diagnosis(int $number, array $issue): void
    {
        $this->rule();
        $this->text(\sprintf('%02d  %s', $number, $issue['title']), self::MARGIN, 'fg=red;options=bold');
        $this->text($issue['findings'].' '.(1 === $issue['findings'] ? 'finding' : 'findings').'  ·  '.$issue['category'], self::MARGIN, 'fg=gray');
        $this->output->writeln('');
        $this->text($issue['relation'], self::BODY, 'fg=cyan;options=bold');
        $primary = $issue['references'][0] ?? null;
        if (null !== $primary) {
            $this->text(self::shortLocation($primary), self::BODY, 'fg=gray');
            if (!$this->verbose && \count($issue['references']) > 1) {
                $more = \count($issue['references']) - 1;
                $this->text('+'.$more.' related '.(1 === $more ? 'location' : 'locations').' (see -v)', self::BODY, 'fg=gray');
            }
        }
        $this->output->writeln('');
        $this->text('WHAT IS WRONG', self::BODY, 'options=bold');
        $this->text($issue['summary'], self::NESTED);
        if (null !== $issue['caution']) {
            $this->output->writeln('');
            $this->text('!  IMPORTANT CONTEXT', self::BODY, 'fg=yellow;options=bold');
            $this->text($issue['caution'], self::NESTED, 'fg=yellow');
        }
        $this->output->writeln('');
        $this->text('POSSIBLE NEXT STEPS', self::BODY, 'options=bold');
        foreach ($issue['steps'] as $i => $step) {
            $this->text(($i + 1).'. '.$step, self::NESTED);
        }
        if ($this->verbose) {
            $this->output->writeln('');
            $this->text('TECHNICAL DETAILS', self::BODY, 'fg=gray;options=bold');
            $this->text('Rule: '.$issue['rule'], self::NESTED, 'fg=gray');
            $this->text($issue['detail'], self::NESTED, 'fg=gray');
            foreach ($issue['references'] as $reference) {
                $this->text($reference['file'].(null === ($reference['line'] ?? null) ? '' : ':'.$reference['line']), self::NESTED, 'fg=gray');
            }
        }
        if (($this->verbose || $this->all || null !== $this->issueNumber) && [] !== ($issue['originals'] ?? [])) {
            $this->output->writeln('');
            $this->text('ORIGINAL FINDINGS', self::BODY, 'fg=gray;options=bold');
            foreach ($issue['originals'] as $original) {
                $this->text($this->verbose ? $original['technical'] : $original['readable'], self::NESTED, 'fg=gray');
            }
        }
    }

    private function applicationCouplings(): void
    {
        $this->output->writeln('');
        $this->rule();
        $this->text('APPLICATION COUPLINGS', self::MARGIN, 'options=bold');
        $this->text('Informational only – these are not architecture violations.', self::MARGIN, 'fg=gray');
        $this->output->writeln('');
        if ([] === $this->couplings) {
            $this->text('No application couplings were observed in the available evidence.', self::BODY, 'fg=gray');
        }
        foreach ($this->couplings as $coupling) {
            $this->text($coupling['relation'], self::BODY, 'fg=cyan');
            $this->text($coupling['summary'], self::BODY);
            if ($this->verbose) {
                foreach ($coupling['references'] as $reference) {
                    $this->text($reference['file'].':'.$reference['line'], self::BODY, 'fg=gray');
                    if (isset($reference['target_file'])) {
                        $this->text('Target: '.$reference['target_file'], self::BODY, 'fg=gray');
                    }
                }
            }
        }
    }

    /** @param array<string, mixed> $reference */
    private static function shortLocation(array $reference): string
    {
        if (isset($reference['readable'])) {
            return $reference['readable'];
        }
        $file = str_replace('\\', '/', $reference['file']);
        $file = preg_replace('~^src/Module/~', '', $file) ?? $file;

        return $file.(null === ($reference['line'] ?? null) ? '' : ':'.$reference['line']);
    }

    private function rule(): void
    {
        $this->style->rule();
    }

    private static function escape(string $text): string
    {
        return OutputFormatter::escape(TerminalText::clean($text));
    }

    private function text(string $text, string $indent, ?string $style = null): void
    {
        $this->style->text($text, $indent, $style);
    }
}
