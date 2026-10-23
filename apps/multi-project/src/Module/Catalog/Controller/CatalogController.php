<?php

declare(strict_types=1);

namespace ProjectApp\Module\Catalog\Controller;

use Sillbeam\Core\Project\ProjectContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CatalogController
{
    public function __construct(private ProjectContextInterface $project)
    {
    }

    #[Route('/catalog', name: 'catalog')]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['project' => $this->project->id()]);
    }
}
