<?php

declare(strict_types=1);

namespace Nexum\Payment;

final readonly class Money
{
    public function __construct(public int $minorAmount, public string $currency)
    {
        if ($minorAmount < 0 || 1 !== preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Money requires a non-negative minor amount and ISO-style currency code.');
        }
    }
}
