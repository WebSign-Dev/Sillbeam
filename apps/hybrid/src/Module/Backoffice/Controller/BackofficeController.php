<?php

declare(strict_types=1);

namespace HybridApp\Module\Backoffice\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BackofficeController
{
    #[Route('/backoffice', name: 'backoffice')]
    public function __invoke(): Response
    {
        return new Response('backoffice');
    }
}
