<?php

declare(strict_types=1);

namespace Nexum\Api\Pagination;

enum PaginationMode: string
{
    case PAGE = 'page';
    case CURSOR = 'cursor';
}
