<?php

declare(strict_types=1);

namespace SymfonyOnlyApp\Service;

final class Greeting
{
    public function message(): string
    {
        return 'standard-symfony';
    }
}
