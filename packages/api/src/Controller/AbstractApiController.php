<?php

declare(strict_types=1);

namespace Nexum\Api\Controller;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Error\ValidationProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(protected readonly ApiResponseFactoryInterface $responses)
    {
    }

    /** @param array<string, mixed> $meta */
    final protected function success(mixed $data, array $meta = []): JsonResponse
    {
        return $this->responses->success($data, meta: $meta);
    }

    final protected function created(mixed $data, ?string $location = null): JsonResponse
    {
        return $this->responses->success(
            $data,
            status: 201,
            headers: null === $location ? [] : ['Location' => $location],
        );
    }

    final protected function accepted(mixed $data = null): JsonResponse
    {
        return $this->responses->success($data, status: 202);
    }

    final protected function noContent(): JsonResponse
    {
        return $this->responses->noContent();
    }

    final protected function badRequest(string $detail, string $code = 'invalid_request'): JsonResponse
    {
        return $this->responses->problem(ApiProblemException::badRequest($detail, $code));
    }

    final protected function forbidden(string $detail = 'You are not allowed to perform this operation.'): JsonResponse
    {
        return $this->responses->problem(new ApiProblemException(403, 'forbidden', 'Forbidden', $detail));
    }

    final protected function notFound(string $resource = 'Resource'): JsonResponse
    {
        return $this->responses->problem(ApiProblemException::notFound($resource));
    }

    final protected function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        return $this->responses->problem(new ValidationProblemException($violations));
    }
}
