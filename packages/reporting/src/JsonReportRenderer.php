<?php

declare(strict_types=1);

namespace Nexum\Reporting;

final class JsonReportRenderer implements ReportRendererInterface
{
    public function mediaType(): string
    {
        return 'application/json';
    }

    public function render(Report $report): string
    {
        return json_encode(['columns' => $report->columns, 'rows' => $report->rows], \JSON_THROW_ON_ERROR);
    }
}
