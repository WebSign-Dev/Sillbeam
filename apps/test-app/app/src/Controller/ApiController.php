<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    #[Route('/api', name: 'app_api_index', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'service' => 'backend',
            'message' => 'Fabryq Harbor Symfony backend is reachable.',
        ]);
    }
}
