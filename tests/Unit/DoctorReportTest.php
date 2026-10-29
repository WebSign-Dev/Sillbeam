<?php

declare(strict_types=1);

namespace Sillbeam\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sillbeam\Core\Console\Doctor\DoctorReport;

final class DoctorReportTest extends TestCase
{
    public function testAllStatusesAndCombinedRowsKeepTheOriginalPriorityAndCounts(): void
    {
        foreach ([
            [['verified'], 0, 'verified'],
            [['action required'], 1, 'action required'],
            [['not verified'], 2, 'not verified'],
            [['verified', 'action required'], 1, 'action required'],
            [['verified', 'action required', 'not verified'], 2, 'not verified'],
        ] as [$statuses, $code, $status]) {
            $checks = array_map(static fn (string $value): array => ['id' => 'services.imports', 'label' => 'Service imports', 'status' => $value, 'detail' => 'original evidence'], $statuses);
            $report = DoctorReport::fromChecks($checks);
            $data = json_decode($report->toJson(), true, flags: \JSON_THROW_ON_ERROR);
            self::assertSame($code, $report->exitCode());
            self::assertSame($code, $data['exit_code']);
            self::assertSame($status, $data['status']);
            self::assertSame(array_replace(['verified' => 0, 'action required' => 0, 'not verified' => 0], array_count_values($statuses)), $data['summary']);
            self::assertSame($statuses, array_column($data['checks'], 'status'));
            self::assertSame(array_fill(0, \count($statuses), 'original evidence'), array_column($data['checks'], 'detail'));
            self::assertSame([], $data['errors']);
        }
    }

    public function testFailuresUseTheSameEnvelopeWithoutFabricatedCheckResults(): void
    {
        foreach (['doctor.usage', 'doctor.execution'] as $code) {
            $report = DoctorReport::failure(new \RuntimeException('Bad <error>literal</error>'."\033[2J\xFF"), $code);
            $json = $report->toJson();
            self::assertStringNotContainsString("\033", $json);
            $data = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
            self::assertSame(2, $report->exitCode());
            self::assertSame('not verified', $data['status']);
            self::assertSame(['verified' => 0, 'action required' => 0, 'not verified' => 0], $data['summary']);
            self::assertSame([], $data['checks']);
            self::assertSame($code, $data['errors'][0]['code']);
            self::assertStringContainsString('<error>literal</error>', $data['errors'][0]['message']);
            self::assertStringContainsString("\u{FFFD}", $data['errors'][0]['message']);
        }
    }
}
