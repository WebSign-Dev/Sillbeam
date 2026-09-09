<?php

declare(strict_types=1);

namespace Nexum\Api\Scope;

enum ResourceScope: string
{
    case DENY = 'deny';
    case GLOBAL = 'global';
    case SUBJECT = 'subject';
}
