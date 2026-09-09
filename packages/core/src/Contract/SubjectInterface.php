<?php

declare(strict_types=1);

namespace Nexum\Core\Contract;

interface SubjectInterface
{
    /** @return non-empty-string */
    public function getSubjectIdentifier(): string;
}
