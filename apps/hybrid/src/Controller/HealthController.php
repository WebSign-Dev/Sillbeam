<?php

declare(strict_types=1);

namespace HybridApp\Controller;

use HybridApp\Service\ApplicationStatus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/health', name: 'hybrid_health')]
    public function __invoke(ApplicationStatus $status): Response
    {
        return new Response($status->value());
    }
}
