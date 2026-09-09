<?php

declare(strict_types=1);

namespace Nexum\Reporting;

interface ReportRendererInterface
{
    public function mediaType(): string;

    public function render(Report $report): string;
}
