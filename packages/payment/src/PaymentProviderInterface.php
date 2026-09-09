<?php

declare(strict_types=1);

namespace Nexum\Payment;

interface PaymentProviderInterface
{
    public function charge(PaymentRequest $request): PaymentResult;
}
