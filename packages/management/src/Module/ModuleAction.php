<?php

declare(strict_types=1);

namespace Nexum\Management\Module;

enum ModuleAction: string
{
    case Enable = 'enable';
    case Disable = 'disable';
    case Remove = 'remove';
}
