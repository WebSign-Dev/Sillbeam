<?php

declare(strict_types=1);

namespace SingleProjectApp\Project\Portal\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortalController
{
    #[Route('/portal', name: 'home')]
    public function __invoke(): Response
    {
        return new Response('portal');
    }
}
