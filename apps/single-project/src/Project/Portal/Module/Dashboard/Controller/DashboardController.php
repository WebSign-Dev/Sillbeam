<?php

declare(strict_types=1);

namespace SingleProjectApp\Project\Portal\Module\Dashboard\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function __invoke(): Response
    {
        return new Response('dashboard');
    }
}
