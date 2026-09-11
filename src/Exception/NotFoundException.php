<?php

declare(strict_types=1);

namespace Nexum\Exception;

final class NotFoundException extends DomainException
{
    public function __construct(string $resource, string $identifier)
    {
        parent::__construct(\sprintf('%s "%s" was not found.', $resource, $identifier));
    }
}
