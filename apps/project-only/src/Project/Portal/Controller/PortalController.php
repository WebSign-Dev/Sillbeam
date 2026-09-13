<?php

declare(strict_types=1);

namespace ProjectOnlyApp\Project\Portal\Controller;

use ProjectOnlyApp\Project\Portal\Service\ProjectGreeting;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortalController
{
    #[Route('/portal', name: 'home')]
    public function __invoke(ProjectGreeting $greeting): Response
    {
        return new Response($greeting->message());
    }
}
