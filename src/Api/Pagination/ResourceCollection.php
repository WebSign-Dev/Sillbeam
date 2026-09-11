<?php

declare(strict_types=1);

namespace Nexum\Api\Pagination;

use Nexum\Entity\Contract\EntityInterface;

final readonly class ResourceCollection
{
    /**
     * @param list<EntityInterface> $items
     * @param array<string, mixed>  $meta
     */
    public function __construct(
        public array $items,
        public array $meta,
    ) {
    }
}
