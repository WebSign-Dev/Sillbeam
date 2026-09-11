<?php

declare(strict_types=1);

namespace HybridApp\Module\Common\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommonController
{
    #[Route('/common', name: 'common')]
    public function __invoke(): Response
    {
        return new Response('common');
    }
}
