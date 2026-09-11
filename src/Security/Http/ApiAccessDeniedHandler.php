<?php

declare(strict_types=1);

namespace Nexum\Security\Http;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final readonly class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private ApiResponseFactoryInterface $responses)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return $this->responses->problem(new ApiProblemException(
            403,
            'permission_denied',
            'Permission denied',
            'You are not allowed to perform this operation.',
        ));
    }
}
