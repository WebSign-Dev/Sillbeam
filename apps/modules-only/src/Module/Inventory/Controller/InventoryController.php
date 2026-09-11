<?php

declare(strict_types=1);

namespace ModulesOnlyApp\Module\Inventory\Controller;

use ModulesOnlyApp\Module\Foundation\Service\ModuleClock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InventoryController
{
    public function __construct(private readonly ModuleClock $clock)
    {
    }

    #[Route('/inventory', name: 'inventory_index')]
    public function __invoke(): Response
    {
        return new Response('inventory-'.$this->clock->label());
    }
}
