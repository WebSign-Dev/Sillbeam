<?php

declare(strict_types=1);

namespace Nexum\Http\Request;

use Nexum\Http\ValueObject\ClientContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final readonly class ClientContextResolver implements ClientContextResolverInterface
{
    public function __construct(private RequestStack $requests)
    {
    }

    public function resolve(?Request $request = null): ClientContext
    {
        $request ??= $this->requests->getCurrentRequest();
        $candidate = $request?->headers->get('X-Request-Id');
        $requestId = \is_string($candidate) && 1 === preg_match('/^[A-Za-z0-9._-]{1,64}$/', $candidate)
            ? $candidate
            : Uuid::v7()->toRfc4122();

        return new ClientContext(
            $request?->getClientIp(),
            self::limited($request?->headers->get('User-Agent'), 512),
            self::limited($request?->getPreferredLanguage(), 32),
            $requestId,
        );
    }

    private static function limited(?string $value, int $length): ?string
    {
        return null === $value ? null : substr($value, 0, $length);
    }
}
