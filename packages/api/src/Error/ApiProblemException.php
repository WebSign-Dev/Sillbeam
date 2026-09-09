<?php

declare(strict_types=1);

namespace Nexum\Api\Error;

use Nexum\Core\Exception\NexumExceptionInterface;

class ApiProblemException extends \RuntimeException implements NexumExceptionInterface
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $problemCode,
        public readonly string $title,
        string $detail,
        public readonly array $headers = [],
    ) {
        parent::__construct($detail);
    }

    public static function badRequest(string $detail, string $code = 'invalid_request'): self
    {
        return new self(400, $code, 'Invalid request', $detail);
    }

    public static function notFound(string $resource): self
    {
        return new self(404, 'resource_not_found', 'Resource not found', \sprintf('%s was not found.', $resource));
    }

    public static function operationNotAllowed(string $operation): self
    {
        return new self(405, 'operation_not_allowed', 'Operation not allowed', \sprintf('The "%s" operation is disabled for this resource.', $operation));
    }
}
