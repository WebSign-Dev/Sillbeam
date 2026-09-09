<?php

declare(strict_types=1);

namespace Nexum\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }
        if (null === $value || '' === $value) {
            return;
        }
        if (!\is_string($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();

            return;
        }
        $valid = \strlen($value) >= $constraint->minLength
            && 1 === preg_match('/[a-z]/', $value)
            && 1 === preg_match('/[A-Z]/', $value)
            && 1 === preg_match('/\d/', $value)
            && 1 === preg_match('/[^A-Za-z0-9]/', $value);
        if (!$valid) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
