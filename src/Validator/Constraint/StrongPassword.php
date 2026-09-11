<?php

declare(strict_types=1);

namespace Nexum\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class StrongPassword extends Constraint
{
    public string $message = 'The password must be at least 12 characters and contain upper-case, lower-case, numeric and symbol characters.';

    public function __construct(public int $minLength = 12, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct([], $groups, $payload);
    }
}
