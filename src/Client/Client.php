<?php

declare(strict_types=1);

namespace Nexum\Client;

final readonly class Client
{
    public function __construct(public ?string $ip, public ?string $userAgent, public ?string $locale, public string $requestId)
    {
    }
}
