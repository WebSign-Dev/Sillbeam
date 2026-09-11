<?php

declare(strict_types=1);

namespace ModulesOnlyApp\Module\Archived\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArchivedController
{
    #[Route('/archived', name: 'archived_index')]
    public function __invoke(): Response
    {
        return new Response('archived');
    }
}
