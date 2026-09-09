<?php

declare(strict_types=1);

namespace Nexum\Payment;

final readonly class PaymentResult
{
    public function __construct(public string $providerId, public string $status)
    {
    }
}
