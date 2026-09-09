<?php

declare(strict_types=1);

namespace Nexum\Api\Serializer;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Error\ValidationProblemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class ApiResponseFactory implements ApiResponseFactoryInterface
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    public function success(mixed $data, int $status = 200, array $meta = [], array $context = [], array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->normalizer->normalize($data, 'json', $context),
            'meta' => (object) $meta,
            'errors' => [],
        ], $status, $headers);
    }

    public function problem(ApiProblemException $problem): JsonResponse
    {
        $errors = [[
            'status' => (string) $problem->status,
            'code' => $problem->problemCode,
            'title' => $problem->title,
            'detail' => $problem->getMessage(),
        ]];

        if ($problem instanceof ValidationProblemException) {
            $errors = [];

            foreach ($problem->violations as $violation) {
                $errors[] = [
                    'status' => '422',
                    'code' => 'validation_failed',
                    'title' => 'Validation failed',
                    'detail' => (string) $violation->getMessage(),
                    'source' => ['pointer' => $violation->getPropertyPath()],
                ];
            }
        }

        return new JsonResponse([
            'data' => null,
            'meta' => new \stdClass(),
            'errors' => $errors,
        ], $problem->status, $problem->headers);
    }

    public function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }
}
