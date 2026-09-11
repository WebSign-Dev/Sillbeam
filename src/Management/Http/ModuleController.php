<?php

declare(strict_types=1);

namespace Nexum\Management\Http;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Module\ModuleRegistryInterface;
use Nexum\Security\Authorization\SecurityContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ModuleController
{
    public function __construct(
        private ModuleRegistryInterface $modules,
        private SecurityContextInterface $security,
        private ApiResponseFactoryInterface $responses,
    ) {
    }

    public function list(): JsonResponse
    {
        $this->authorize();
        $items = array_map(static fn ($module): array => [
            'id' => $module->id,
            'class' => $module->class,
            'description' => $module->description,
            'dependencies' => $module->dependencies,
            'enabled' => $module->enabled,
            'scope' => $module->isPrivate() ? 'private' : 'shared',
            'project' => $module->projectClass,
        ], array_values($this->modules->all()));

        return $this->responses->success($items);
    }

    private function authorize(): void
    {
        if (!$this->security->isGranted('nexum.manage')) {
            throw new ApiProblemException(403, 'access_denied', 'Access denied', 'The nexum.manage permission is required.');
        }
    }
}
