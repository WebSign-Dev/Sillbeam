<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'name' => 'Sillbeam Test Application',
            'status' => 'ok',
        ]);
    }
}
