<?php

declare(strict_types=1);

namespace Nexum\Client;

use Nexum\Http\Request\ClientContextResolverInterface;

final readonly class ClientProvider
{
    public function __construct(private ClientContextResolverInterface $contexts)
    {
    }

    public function current(): Client
    {
        $context = $this->contexts->resolve();

        return new Client($context->ip, $context->userAgent, $context->locale, $context->requestId);
    }
}
