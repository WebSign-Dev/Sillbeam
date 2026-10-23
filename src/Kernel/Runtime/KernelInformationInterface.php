<?php

declare(strict_types=1);

namespace Sillbeam\Core\Kernel\Runtime;

interface KernelInformationInterface
{
    /** @return array<string, string|int> */
    public function all(): array;
}
