<?php

declare(strict_types=1);

namespace Sillbeam\Core\Kernel\Runtime;

/**
 * Provides scalar runtime metadata for the about command without exposing container internals.
 *
 * @api
 */
interface KernelInformationInterface
{
    /**
     * Returns application/environment/version metadata and discovered/active module counts.
     *
     * @return array<string, string|int>
     */
    public function all(): array;
}
