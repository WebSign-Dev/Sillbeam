<?php

declare(strict_types=1);

namespace Nexum\Security\Http;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final readonly class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private ApiResponseFactoryInterface $responses)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->responses->problem(new ApiProblemException(
            401,
            'authentication_required',
            'Authentication required',
            'A valid Bearer access token is required.',
            ['WWW-Authenticate' => 'Bearer realm="Nexum"'],
        ));
    }
}
