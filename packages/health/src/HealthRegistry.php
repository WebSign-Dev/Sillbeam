<?php

declare(strict_types=1);

namespace Nexum\Health;

final readonly class HealthRegistry
{
    /** @param iterable<HealthCheckInterface> $checks */
    public function __construct(private iterable $checks)
    {
    }

    /** @return array{status: HealthStatus, checks: list<HealthResult>} */
    public function check(): array
    {
        $results = [];
        $status = HealthStatus::Healthy;
        foreach ($this->checks as $check) {
            try {
                $result = $check->check();
            } catch (\Throwable $exception) {
                $result = new HealthResult($check::class, HealthStatus::Unhealthy, ['error' => $exception::class]);
            }
            $results[] = $result;
            if (HealthStatus::Unhealthy === $result->status) {
                $status = HealthStatus::Unhealthy;
            } elseif (HealthStatus::Degraded === $result->status && HealthStatus::Healthy === $status) {
                $status = HealthStatus::Degraded;
            }
        }

        return ['status' => $status, 'checks' => $results];
    }
}
