<?php

declare(strict_types=1);

namespace Nexum\Api\Error;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationProblemException extends ApiProblemException
{
    public function __construct(public readonly ConstraintViolationListInterface $violations)
    {
        parent::__construct(422, 'validation_failed', 'Validation failed', 'The submitted resource is invalid.');
    }
}
