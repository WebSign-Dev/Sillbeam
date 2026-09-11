<?php

declare(strict_types=1);

namespace SingleProjectApp\Module\Profile\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController
{
    #[Route('/profile', name: 'profile')]
    public function __invoke(): Response
    {
        return new Response('profile');
    }
}
