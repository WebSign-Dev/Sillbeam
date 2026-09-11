<?php

declare(strict_types=1);

namespace Nexum\Health;

interface HealthCheckInterface
{
    public function check(): HealthResult;
}
