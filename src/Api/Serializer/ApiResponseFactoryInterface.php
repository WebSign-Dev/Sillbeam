<?php

declare(strict_types=1);

namespace Nexum\Api\Serializer;

use Nexum\Api\Error\ApiProblemException;
use Symfony\Component\HttpFoundation\JsonResponse;

interface ApiResponseFactoryInterface
{
    /**
     * @param array<string, mixed>  $meta
     * @param array<string, mixed>  $context
     * @param array<string, string> $headers
     */
    public function success(mixed $data, int $status = 200, array $meta = [], array $context = [], array $headers = []): JsonResponse;

    public function problem(ApiProblemException $problem): JsonResponse;

    public function noContent(): JsonResponse;
}
