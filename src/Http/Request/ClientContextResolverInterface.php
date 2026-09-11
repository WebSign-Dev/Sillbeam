<?php

declare(strict_types=1);

namespace Nexum\Http\Request;

use Nexum\Http\ValueObject\ClientContext;
use Symfony\Component\HttpFoundation\Request;

interface ClientContextResolverInterface
{
    public function resolve(?Request $request = null): ClientContext;
}
