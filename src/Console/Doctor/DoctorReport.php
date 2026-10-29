<?php

declare(strict_types=1);

namespace Sillbeam\Core\Console\Doctor;

/**
 * Schema-version-1 presentation of existing Inspector results, never a second inspection.
 *
 * Repeated check IDs retain independent observations, including mixed statuses.
 * Usage/execution failures have explicit errors rather than fabricated checks.
 *
 * @internal
 */
final readonly class DoctorReport
{
    /** @param array<string, mixed> $data */
    private function __construct(public array $data)
    {
    }

    /**
     * Maps Inspector rows and applies the existing exit priority: unknown, action, verified.
     *
     * @param list<array{id: string, label: string, status: string, detail: string}> $checks
     */
    public static function fromChecks(array $checks): self
    {
        $counts = array_replace(['verified' => 0, 'action required' => 0, 'not verified' => 0], array_count_values(array_column($checks, 'status')));
        $exit = $counts['not verified'] > 0 ? 2 : ($counts['action required'] > 0 ? 1 : 0);

        return new self([
            'schema_version' => 1,
            'status' => ['verified', 'action required', 'not verified'][$exit],
            'exit_code' => $exit,
            'summary' => $counts,
            'checks' => array_map(static fn (array $check): array => [
                'id' => $check['id'],
                'status' => $check['status'],
                'description' => $check['label'],
                'detail' => $check['detail'],
            ], $checks),
            'errors' => [],
        ]);
    }

    /** @param 'doctor.usage'|'doctor.execution' $code */
    public static function failure(\Throwable $error, string $code): self
    {
        return new self([
            'schema_version' => 1,
            'status' => 'not verified',
            'exit_code' => 2,
            'summary' => ['verified' => 0, 'action required' => 0, 'not verified' => 0],
            'checks' => [],
            'errors' => [['code' => $code, 'message' => $error->getMessage()]],
        ]);
    }

    public function exitCode(): int
    {
        return $this->data['exit_code'];
    }

    /**
     * Encodes raw data without terminal styling; invalid UTF-8 is replaced.
     *
     * @throws \JsonException if report values cannot be encoded
     */
    public function toJson(): string
    {
        return json_encode($this->data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
