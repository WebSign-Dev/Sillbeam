<?php

declare(strict_types=1);

namespace ProjectApp\Project\Admin\Module\Checkout\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController
{
    #[Route('/checkout', name: 'checkout')]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['scope' => 'admin']);
    }
}
