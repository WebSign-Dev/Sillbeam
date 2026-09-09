<?php

declare(strict_types=1);

namespace Nexum\Management\Http;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Kernel\Module\ModuleRegistryInterface;
use Nexum\Management\Module\ModuleAction;
use Nexum\Management\Module\ModuleConfigurationManager;
use Nexum\Security\Authorization\SecurityContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final readonly class ModuleController
{
    public function __construct(
        private ModuleRegistryInterface $modules,
        private ModuleConfigurationManager $manager,
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
        ], array_values($this->modules->all()));

        return $this->responses->success($items);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorize();
        $action = $request->isMethod('DELETE') ? ModuleAction::Remove : $this->requestedAction($request);
        try {
            $this->manager->apply($id, $action);
        } catch (\OutOfBoundsException) {
            throw new ApiProblemException(404, 'module_not_found', 'Module not found', 'The requested module does not exist.');
        } catch (\DomainException $exception) {
            throw new ApiProblemException(409, 'module_dependency_conflict', 'Module dependency conflict', $exception->getMessage());
        }

        return $this->responses->noContent();
    }

    private function authorize(): void
    {
        if (!$this->security->isGranted('nexum.manage')) {
            throw new ApiProblemException(403, 'access_denied', 'Access denied', 'The nexum.manage permission is required.');
        }
    }

    private function requestedAction(Request $request): ModuleAction
    {
        try {
            $enabled = $request->toArray()['enabled'] ?? null;
        } catch (\Throwable) {
            $enabled = null;
        }
        if (!\is_bool($enabled)) {
            throw new ApiProblemException(400, 'invalid_request', 'Invalid request', 'The enabled field must be boolean.');
        }

        return $enabled ? ModuleAction::Enable : ModuleAction::Disable;
    }
}
