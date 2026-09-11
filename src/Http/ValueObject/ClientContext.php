<?php

declare(strict_types=1);

namespace Nexum\Http\ValueObject;

final readonly class ClientContext
{
    public function __construct(
        public ?string $ip,
        public ?string $userAgent,
        public ?string $locale,
        public string $requestId,
    ) {
    }
}
