<?php

declare(strict_types=1);

namespace SymfonyOnlyApp\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyOnlyApp\Service\Greeting;

final class HomeController
{
    #[Route('/', name: 'symfony_home')]
    public function __invoke(Greeting $greeting): Response
    {
        return new Response($greeting->message());
    }
}
