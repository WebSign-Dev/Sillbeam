<?php

declare(strict_types=1);

namespace Nexum\Api\Controller;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Error\ValidationProblemException;
use Nexum\Api\Metadata\ResourceMetadata;
use Nexum\Api\Metadata\ResourceMetadataFactory;
use Nexum\Api\Operation\Operation;
use Nexum\Api\Pagination\ApiQueryParser;
use Nexum\Api\Persistence\DoctrineResourceGateway;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Nexum\Api\Serializer\ResourcePayloadMapper;
use Nexum\Doctrine\Store\EntityStoreInterface;
use Nexum\Entity\Contract\EntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractResourceController extends AbstractApiController
{
    public function __construct(
        ApiResponseFactoryInterface $responses,
        private readonly ResourceMetadataFactory $metadataFactory,
        private readonly ApiQueryParser $queryParser,
        private readonly DoctrineResourceGateway $resources,
        private readonly ResourcePayloadMapper $payloadMapper,
        private readonly ValidatorInterface $validator,
        private readonly EntityStoreInterface $store,
    ) {
        parent::__construct($responses);
    }

    #[Route('', name: 'list', methods: ['GET'], defaults: ['_nexum_api' => true])]
    final public function index(Request $request): JsonResponse
    {
        $metadata = $this->metadata(Operation::INDEX);
        $this->authorize($metadata, Operation::INDEX, $metadata->entity);
        $collection = $this->resources->collection($metadata, $this->queryParser->parse($request, $metadata));

        return $this->responses->success($collection->items, meta: $collection->meta, context: $metadata->normalizationContext);
    }

    #[Route('/{id}', name: 'read', methods: ['GET'], defaults: ['_nexum_api' => true])]
    final public function read(string $id): JsonResponse
    {
        $metadata = $this->metadata(Operation::READ);
        $resource = $this->find($metadata, $id);
        $this->authorize($metadata, Operation::READ, $resource);

        return $this->responses->success($resource, context: $metadata->normalizationContext);
    }

    #[Route('', name: 'create', methods: ['POST'], defaults: ['_nexum_api' => true])]
    final public function create(Request $request): JsonResponse
    {
        $metadata = $this->metadata(Operation::CREATE);
        $this->authorize($metadata, Operation::CREATE, $metadata->entity);
        $resource = $this->payloadMapper->map($request, $metadata, Operation::CREATE);
        $this->resources->initializeScope($metadata, $resource);
        $this->validate($resource);
        $this->beforeWrite($resource, Operation::CREATE);
        $this->store->persist($resource);
        $this->store->flush();
        $this->afterWrite($resource, Operation::CREATE);

        return $this->responses->success(
            $resource,
            status: 201,
            context: $metadata->normalizationContext,
            headers: ['Location' => rtrim($request->getUri(), '/').'/'.$this->identifier($resource)],
        );
    }

    #[Route('/{id}', name: 'replace', methods: ['PUT'], defaults: ['_nexum_api' => true])]
    final public function replace(Request $request, string $id): JsonResponse
    {
        return $this->updateResource($request, $id, Operation::REPLACE);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'], defaults: ['_nexum_api' => true])]
    final public function update(Request $request, string $id): JsonResponse
    {
        return $this->updateResource($request, $id, Operation::UPDATE);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], defaults: ['_nexum_api' => true])]
    final public function delete(string $id): JsonResponse
    {
        $metadata = $this->metadata(Operation::DELETE);
        $resource = $this->find($metadata, $id);
        $this->authorize($metadata, Operation::DELETE, $resource);
        $this->beforeWrite($resource, Operation::DELETE);
        $this->store->delete($resource);
        $this->store->flush();
        $this->afterWrite($resource, Operation::DELETE);

        return $this->responses->noContent();
    }

    protected function beforeWrite(EntityInterface $resource, Operation $operation): void
    {
    }

    protected function afterWrite(EntityInterface $resource, Operation $operation): void
    {
    }

    private function updateResource(Request $request, string $id, Operation $operation): JsonResponse
    {
        $metadata = $this->metadata($operation);
        $resource = $this->find($metadata, $id);
        $this->authorize($metadata, $operation, $resource);

        try {
            $resource = $this->payloadMapper->map($request, $metadata, $operation, $resource);
            $this->validate($resource);
        } catch (\Throwable $throwable) {
            $this->resources->refresh($metadata, $resource);

            throw $throwable;
        }

        $this->beforeWrite($resource, $operation);
        $this->store->flush();
        $this->afterWrite($resource, $operation);

        return $this->responses->success($resource, context: $metadata->normalizationContext);
    }

    private function metadata(Operation $operation): ResourceMetadata
    {
        $metadata = $this->metadataFactory->forController(static::class);

        if (!$metadata->supports($operation)) {
            throw ApiProblemException::operationNotAllowed($operation->value);
        }

        return $metadata;
    }

    private function find(ResourceMetadata $metadata, string $id): EntityInterface
    {
        return $this->resources->find($metadata, $id) ?? throw ApiProblemException::notFound($metadata->entity);
    }

    private function validate(EntityInterface $resource): void
    {
        $violations = $this->validator->validate($resource);

        if (\count($violations) > 0) {
            throw new ValidationProblemException($violations);
        }
    }

    private function authorize(ResourceMetadata $metadata, Operation $operation, object|string $subject): void
    {
        if (null !== $metadata->permissionPrefix) {
            $this->denyAccessUnlessGranted($metadata->permissionPrefix.'.'.$operation->permission(), $subject);
        }
    }

    private function identifier(EntityInterface $resource): string
    {
        if (!method_exists($resource, 'getId')) {
            throw new \LogicException(\sprintf('API resource "%s" must expose getId().', $resource::class));
        }

        $identifier = $resource->getId();

        if (!\is_scalar($identifier) && !$identifier instanceof \Stringable) {
            throw new \LogicException('API resource identifiers must be scalar or stringable.');
        }

        return (string) $identifier;
    }
}
