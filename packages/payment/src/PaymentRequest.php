<?php

declare(strict_types=1);

namespace Nexum\Payment;

final readonly class PaymentRequest
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(public string $idempotencyKey, public Money $amount, public string $reference, public array $metadata = [])
    {
        if (1 !== preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $idempotencyKey) || '' === trim($reference)) {
            throw new \InvalidArgumentException('Invalid payment request.');
        }
    }
}
