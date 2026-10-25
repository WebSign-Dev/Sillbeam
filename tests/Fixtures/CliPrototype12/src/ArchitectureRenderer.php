<?php

declare(strict_types=1);

namespace SillbeamPrototype;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prototype 1.2: deterministic presentation layer on top of Symfony Console.
 * Fixtures contain all meanings and solutions; this renderer performs NO analysis.
 */
final class ArchitectureRenderer
{
    private const MARGIN = '  ';
    private const BODY = '    ';
    private const NESTED = '      ';
    private int $width;

    public function __construct(
        private readonly OutputInterface $output,
        int $terminalWidth = 84,
        private readonly bool $verbose = false,
        private readonly bool $all = false,
        private readonly ?int $issueNumber = null,
        private readonly bool $showCouplings = false,
    ) {
        $this->width = max(52, min(90, $terminalWidth));
    }

    /** @param array<string, mixed> $scenario */
    public function render(array $scenario): void
    {
        $issues = $scenario['issues'];
        $count = count($issues);
        $findings = (int) $scenario['findings'];
        if ($this->issueNumber !== null && ($this->issueNumber < 1 || $this->issueNumber > $count)) {
            throw new \InvalidArgumentException('No diagnosis '.$this->issueNumber.'. Choose a number from 1 to '.$count.'.');
        }
        if ($this->issueNumber !== null && $this->all) {
            throw new \InvalidArgumentException('Choose either --issue=N or --all.');
        }

        $this->output->writeln('');
        $this->output->writeln(self::MARGIN.'<options=bold>SILLBEAM CORE</>  <fg=gray>/ Architecture</>');
        $this->rule();
        if ($findings === 0) {
            $this->text('✓  Architecture valid', self::MARGIN, 'fg=green;options=bold');
            $this->text($scenario['modules'].' modules  ·  '.$scenario['projects'].' projects  ·  0 violations', self::MARGIN, 'fg=gray');
            if ($this->showCouplings) {
                $this->applicationCouplings();
            }
            $this->output->writeln('');
            return;
        }

        $this->text('✕  Architecture violations found', self::MARGIN, 'fg=red;options=bold');
        $this->text($findings.' '.($findings === 1 ? 'finding' : 'findings').'  ·  '.$count.' '.($count === 1 ? 'diagnosis' : 'diagnoses').'  ·  '.$scenario['modules'].' modules  ·  '.$scenario['projects'].' projects', self::MARGIN, 'fg=gray');
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
            $number = sprintf('%02d', $i + 1);
            $current = ($i + 1) === ($this->issueNumber ?? 1) && !$this->all;
            $prefix = $current ? '<fg=red;options=bold>'.$number.'</>' : '<fg=gray>'.$number.'</>';
            $this->output->writeln(self::BODY.$prefix.'  <options=bold>'.self::escape($issue['title']).'</> <fg=gray>('.$issue['findings'].')</>');
            $this->text($issue['relation'], self::NESTED, 'fg=cyan');
        }
    }

    /** @param array<string,mixed> $issue */
    private function diagnosis(int $number, array $issue): void
    {
        $this->rule();
        $this->text(sprintf('%02d  %s', $number, $issue['title']), self::MARGIN, 'fg=red;options=bold');
        $this->text($issue['findings'].' '.($issue['findings'] === 1 ? 'finding' : 'findings').'  ·  '.$issue['category'], self::MARGIN, 'fg=gray');
        $this->output->writeln('');
        $this->text($issue['relation'], self::BODY, 'fg=cyan;options=bold');
        $primary = $issue['references'][0] ?? null;
        if ($primary !== null) {
            $this->text(self::shortLocation($primary), self::BODY, 'fg=gray');
            if (!$this->verbose && count($issue['references']) > 1) {
                $more = count($issue['references']) - 1;
                $this->text('+'.$more.' related '.($more === 1 ? 'location' : 'locations').' (see -v)', self::BODY, 'fg=gray');
            }
        }
        $this->output->writeln('');
        $this->text('WHAT IS WRONG', self::BODY, 'options=bold');
        $this->text($issue['summary'], self::NESTED);
        if ($issue['caution'] !== null) {
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
                $this->text($reference['file'].':'.$reference['line'], self::NESTED, 'fg=gray');
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
        $this->text('Admin → App\\Entity\\AuditLog', self::BODY, 'fg=cyan');
        $this->text('Admin references application code outside its module.', self::BODY);
        if ($this->verbose) {
            $this->text('src/Module/Admin/Service/AuditWriter.php:21', self::BODY, 'fg=gray');
        }
    }

    private static function shortLocation(array $reference): string
    {
        $file = str_replace('\\', '/', $reference['file']);
        $file = preg_replace('~^src/Module/~', '', $file) ?? $file;
        return $file.':'.$reference['line'];
    }

    private function rule(): void
    {
        $this->output->writeln(self::MARGIN.'<fg=gray>'.str_repeat('─', $this->width - 4).'</>');
    }

    private static function escape(string $text): string
    {
        return OutputFormatter::escape($text);
    }

    private function text(string $text, string $indent, ?string $style = null): void
    {
        $available = $this->width - self::displayWidth($indent) - 2;
        foreach (self::wrapText($text, $available) as $line) {
            $content = self::escape($line);
            if ($style !== null) {
                $content = '<'.$style.'>'.$content.'</>';
            }
            $this->output->writeln($indent.$content);
        }
    }

    /** @return list<string> */
    private static function wrapText(string $text, int $width): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $wordLen = self::displayWidth($word);
            if ($wordLen > $width) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                while (count($characters) > $width) {
                    $lines[] = implode('', array_splice($characters, 0, $width));
                }
                $current = implode('', $characters);
                continue;
            }
            if ($current !== '' && self::displayWidth($current) + 1 + $wordLen > $width) {
                $lines[] = $current;
                $current = '';
            }
            $current .= ($current === '' ? '' : ' ').$word;
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        return $lines ?: [''];
    }

    private static function displayWidth(string $text): int
    {
        if (function_exists('mb_strwidth')) {
            return mb_strwidth($text, 'UTF-8');
        }
        preg_match_all('/./us', $text, $matches);
        return count($matches[0]);
    }
}
