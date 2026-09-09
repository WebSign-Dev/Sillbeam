<?php

declare(strict_types=1);

namespace Nexum\Security\Http;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class ApiAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private ApiResponseFactoryInterface $responses)
    {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->responses->problem(new ApiProblemException(
            401,
            'authentication_failed',
            'Authentication failed',
            'The supplied credentials are invalid.',
            ['WWW-Authenticate' => 'Bearer realm="Nexum"'],
        ));
    }
}
