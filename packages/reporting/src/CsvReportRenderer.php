<?php

declare(strict_types=1);

namespace Nexum\Reporting;

final class CsvReportRenderer implements ReportRendererInterface
{
    public function mediaType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function render(Report $report): string
    {
        $stream = fopen('php://temp/maxmemory:2097152', 'w+');
        if (false === $stream) {
            throw new \RuntimeException('Unable to open the report buffer.');
        }
        try {
            fputcsv($stream, $report->columns, escape: '');
            foreach ($report->rows as $row) {
                fputcsv($stream, array_map($this->safeCell(...), array_map(static fn (string $column): mixed => $row[$column] ?? null, $report->columns)), escape: '');
            }
            rewind($stream);
            $csv = stream_get_contents($stream);

            return false === $csv ? throw new \RuntimeException('Unable to read the report buffer.') : $csv;
        } finally {
            fclose($stream);
        }
    }

    private function safeCell(mixed $value): string
    {
        $value = null === $value ? '' : (string) $value;

        return 1 === preg_match('/^[=+\-@]/', ltrim($value)) ? "'".$value : $value;
    }
}
